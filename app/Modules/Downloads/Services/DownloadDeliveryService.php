<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Services;

use App\Models\User;
use App\Modules\Downloads\Contracts\DownloadAccessDecision;
use App\Modules\Downloads\Contracts\DownloadAccessStrategyInterface;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Servicio central de autorización, protección anti-hotlinking, firma efímera ligada al cliente
 * y entrega binaria optimizada (Streaming o delegación Nginx X-Accel-Redirect).
 */
class DownloadDeliveryService
{
    public function __construct(
        private readonly DownloadAccessStrategyInterface $accessStrategy,
    ) {}

    /**
     * Evalúa si el usuario actual puede acceder al paquete solicitado.
     */
    public function evaluateAccess(DownloadItem $item, ?User $user, Request $request): DownloadAccessDecision
    {
        return $this->accessStrategy->evaluate($item, $user, $request);
    }

    /**
     * Detecta si la petición de descarga proviene de un enlace incrustado en un dominio externo (Hotlinking).
     * Permite clics legítimos del propio dominio (con o sin www) y navegaciones directas sin Referer.
     */
    public function isExternalHotlink(Request $request): bool
    {
        if (!config('downloads.delivery.hotlink_protection', true)) {
            return false;
        }

        $secFetchSite = strtolower(trim((string) $request->header('Sec-Fetch-Site', '')));
        if ($secFetchSite === 'cross-site') {
            return true;
        }

        $referer = trim((string) $request->header('Referer', ''));
        if ($referer === '') {
            return false;
        }

        $refererHost = strtolower((string) parse_url($referer, PHP_URL_HOST));
        if ($refererHost === '') {
            return false;
        }

        $currentHost = strtolower($request->getHost());
        $normReferer = preg_replace('/^www\./i', '', $refererHost);
        $normCurrent = preg_replace('/^www\./i', '', $currentHost);

        return $normReferer !== $normCurrent;
    }

    /**
     * Detecta rastreadores, scrapers e inteligencias artificiales automatizadas (ClaudeBot, GPTBot,
     * meta-externalagent, Googlebot, Ahrefs, etc.) para impedir que descarguen paquetes ZIP e inflen métricas.
     */
    public function isAutomatedBotCrawler(Request $request): bool
    {
        $ua = strtolower(trim((string) $request->userAgent()));
        if ($ua === '') {
            return false;
        }

        $botSignatures = [
            'claudebot',
            'gptbot',
            'chatgpt-user',
            'oai-searchbot',
            'meta-externalagent',
            'facebookexternalhit',
            'bytespider',
            'perplexitybot',
            'googlebot',
            'bingbot',
            'yandexbot',
            'baiduspider',
            'duckduckbot',
            'slurp',
            'ahrefsbot',
            'semrushbot',
            'mj12bot',
            'dotbot',
            'petalbot',
            'applebot',
            'amazonbot',
            'dataforseobot',
        ];

        foreach ($botSignatures as $sig) {
            if (str_contains($ua, $sig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera una URL temporal firmada criptográficamente (vinculada a IP y sesión) para descargar el archivo.
     */
    public function generateSignedStreamUrl(DownloadItem $item, ?Request $request = null, ?User $user = null): string
    {
        $ttlMinutes = max(1, (int) config('downloads.delivery.signed_url_ttl_minutes', 5));

        $params = [
            'downloadItem' => $item->id,
            'v'            => substr((string) ($item->sha256_hash ?: $item->updated_at?->timestamp), 0, 12),
        ];

        if ($request !== null && config('downloads.delivery.bind_signature_to_client', true)) {
            $params['ctx'] = $this->computeClientContextHash($request, $user);
        }

        return URL::temporarySignedRoute(
            'downloads.delivery.stream',
            now()->addMinutes($ttlMinutes),
            $params
        );
    }

    /**
     * Comprueba que quien consume la URL firmada coincide con la IP y sesión para la que se emitió.
     */
    public function hasValidClientContext(Request $request, ?User $user): bool
    {
        if (!config('downloads.delivery.bind_signature_to_client', true)) {
            return true;
        }

        $providedCtx = (string) $request->query('ctx', '');
        if ($providedCtx === '') {
            return false;
        }

        $expectedCtx = $this->computeClientContextHash($request, $user);

        return hash_equals($expectedCtx, $providedCtx);
    }

    /**
     * Registra la descarga tras enviar la respuesta y entrega el binario (X-Accel-Redirect o Streaming).
     */
    public function streamFile(DownloadItem $item, ?User $user, Request $request): Response
    {
        $diskName = $item->vault_disk ?: (string) config('downloads.vault.disk', 'downloads_vault');
        $filePath = (string) $item->private_file_path;

        if ($filePath === '' || !Storage::disk($diskName)->exists($filePath)) {
            abort(404, 'El archivo físico no se encuentra en la bóveda.');
        }

        // Programar telemetría y contador para después de enviar la respuesta HTTP (cero bloqueo al usuario)
        $this->recordDownloadTelemetry($item, $user, $request);

        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '-', (string) ($item->original_filename ?: "{$item->slug}.zip"));
        $mimeType = $item->mime_type ?: 'application/octet-stream';

        $headers = [
            'Content-Type'              => $mimeType,
            'Content-Disposition'       => 'attachment; filename="' . $safeFilename . '"',
            'X-Content-Type-Options'    => 'nosniff',
            'Cache-Control'             => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'                    => 'no-cache',
            'X-Robots-Tag'              => 'noindex, nofollow, noarchive, nosnippet',
        ];

        if ($item->file_size_bytes > 0) {
            $headers['Content-Length'] = (string) $item->file_size_bytes;
        }

        if (!empty($item->sha256_hash)) {
            $headers['X-Checksum-Sha256'] = $item->sha256_hash;
        }

        // Si el servidor tiene configurado X-Accel-Redirect en Nginx, delegar el envío binario en C sin ocupar PHP-FPM
        $accelPrefix = trim((string) config('downloads.delivery.x_accel_redirect_prefix', ''));
        if ($accelPrefix !== '') {
            $internalUri = '/' . trim($accelPrefix, '/') . '/' . ltrim($filePath, '/');
            $headers['X-Accel-Redirect'] = $internalUri;

            return response('', 200, $headers);
        }

        return Storage::disk($diskName)->download($filePath, $safeFilename, $headers);
    }

    /**
     * Huella HMAC corta de contexto cliente (IP + ID de usuario) protegida con APP_KEY.
     */
    private function computeClientContextHash(Request $request, ?User $user): string
    {
        $rawIp = (string) ($request->ip() ?? '0.0.0.0');
        $uid = $user !== null ? (string) $user->getKey() : 'guest';
        $secret = (string) config('app.key', 'cms-downloads');

        return substr(hash_hmac('sha256', "{$rawIp}|{$uid}", $secret), 0, 16);
    }

    /**
     * Registra el evento detallado (con IP real para el administrador y hash HMAC) e incrementa
     * tanto el contador del ítem como el histórico global perpetuo tras enviar la respuesta HTTP (`afterResponse`).
     */
    private function recordDownloadTelemetry(DownloadItem $item, ?User $user, Request $request): void
    {
        $itemId = (int) $item->getKey();
        $itemsTable = $item->getTable();
        $settingsTable = (string) config('downloads.tables.settings', 'download_settings');
        $userId = $user?->id;
        $rawIp = (string) ($request->ip() ?? '0.0.0.0');
        $dailySalt = now()->format('Y-m-d') . '|' . (string) config('app.key', 'cms-downloads');
        $ipHash = hash_hmac('sha256', $rawIp, $dailySalt);
        $userAgent = mb_substr((string) ($request->userAgent() ?? ''), 0, 500);
        $refererUrl = mb_substr(trim((string) ($request->header('Referer') ?? '')), 0, 500) ?: null;
        $version = $item->version;
        $accessSnapshot = $item->access_type;
        $timestamp = now();

        $executed = false;
        app()->terminating(static function () use (&$executed, $itemsTable, $settingsTable, $itemId, $userId, $rawIp, $ipHash, $userAgent, $refererUrl, $version, $accessSnapshot, $timestamp): void {
            if ($executed) {
                return;
            }
            $executed = true;

            DB::table($itemsTable)->where('id', $itemId)->increment('downloads_count');
            DB::table($settingsTable)->increment('total_downloads_all_time');

            DownloadLog::create([
                'download_item_id'     => $itemId,
                'user_id'              => $userId,
                'ip_address'           => $rawIp,
                'ip_hash'              => $ipHash,
                'user_agent'           => $userAgent,
                'referer_url'          => $refererUrl,
                'version_downloaded'   => $version,
                'access_type_snapshot' => $accessSnapshot,
                'created_at'           => $timestamp,
            ]);
        });
    }
}

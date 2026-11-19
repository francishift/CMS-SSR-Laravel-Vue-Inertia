<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Resources;

use App\Modules\Downloads\Models\DownloadLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource administrativo para registros detallados de descargas e IPs (`download_logs`).
 *
 * Exclusivo para el panel privado de administración (`role:admin`).
 *
 * @mixin DownloadLog
 */
class DownloadLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ua = (string) ($this->user_agent ?? '');
        [$navegador, $sistema] = self::resumirAgenteUsuario($ua);

        $refererHost = null;
        if (!empty($this->referer_url)) {
            $parsedHost = parse_url((string) $this->referer_url, PHP_URL_HOST);
            $parsedPath = parse_url((string) $this->referer_url, PHP_URL_PATH);
            $refererHost = $parsedHost
                ? ($parsedHost . ($parsedPath && $parsedPath !== '/' ? $parsedPath : ''))
                : (string) $this->referer_url;
        }

        $rawIp = (string) ($this->ip_address ?? '');
        $esRedPrivada = $rawIp !== '' && filter_var(
            $rawIp,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;

        return [
            'id'                   => $this->id,
            'download_item_id'     => $this->download_item_id,
            'item_title'           => $this->item?->title ?? 'Recurso eliminado',
            'item_slug'            => $this->item?->slug,
            'version_downloaded'   => $this->version_downloaded ?: ($this->item?->version ?? '—'),
            'access_type_snapshot' => $this->access_type_snapshot ?: 'free',
            'ip_address'           => $rawIp !== '' ? $rawIp : 'IP anonimizada (previa)',
            'has_plain_ip'         => $rawIp !== '',
            'is_private_ip'        => $esRedPrivada,
            'ip_hash_short'        => substr((string) ($this->ip_hash ?? ''), 0, 10),
            'browser'              => $navegador,
            'os'                   => $sistema,
            'user_agent'           => $ua,
            'referer_display'      => $refererHost ?: 'Directo / Interno',
            'referer_url'          => $this->referer_url,
            'user_name'            => $this->user?->name,
            'user_email'           => $this->user?->email,
            'created_at_human'     => $this->created_at?->diffForHumans(),
            'created_at_exact'     => $this->created_at?->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Extrae de forma ligera navegador y sistema operativo sin librerías pesadas.
     *
     * @return array{0: string, 1: string}
     */
    public static function resumirAgenteUsuario(string $ua): array
    {
        if ($ua === '') {
            return ['Desconocido', '—'];
        }

        $lower = strtolower($ua);

        if (str_contains($lower, 'bot') || str_contains($lower, 'spider') || str_contains($lower, 'crawl') || str_contains($lower, 'curl')) {
            return ['Bot / CLI', 'Automatizado'];
        }

        $browser = match (true) {
            str_contains($lower, 'edg/')     => 'Edge',
            str_contains($lower, 'opr/')     => 'Opera',
            str_contains($lower, 'firefox/') => 'Firefox',
            str_contains($lower, 'chrome/')  => 'Chrome',
            str_contains($lower, 'safari/')  => 'Safari',
            default                          => 'Navegador',
        };

        $os = match (true) {
            str_contains($lower, 'iphone') || str_contains($lower, 'ipad') => 'iOS',
            str_contains($lower, 'android')                                => 'Android',
            str_contains($lower, 'mac os x') || str_contains($lower, 'macintosh') => 'macOS',
            str_contains($lower, 'windows')                                => 'Windows',
            str_contains($lower, 'linux')                                  => 'Linux',
            default                                                        => 'Otro SO',
        };

        return [$browser, $os];
    }
}

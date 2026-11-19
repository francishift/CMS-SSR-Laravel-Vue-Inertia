<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use App\Modules\Downloads\Services\DownloadDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador blindado para autorizar y servir paquetes de la bóveda privada.
 *
 * Separado de las vistas Inertia para nunca ser cacheado en Redis ni procesado como XHR SPA.
 */
class DownloadFileDeliveryController extends Controller
{
    public function __construct(
        private readonly DownloadDeliveryService $deliveryService,
    ) {}

    /**
     * Redirige de forma permanente a la URL pública activa del catálogo de Descargas.
     */
    public function redirectToCatalog(): RedirectResponse
    {
        return redirect()->to(DownloadSetting::cached()->publicIndexUrl(), 301);
    }

    /**
     * Verifica origen anti-hotlinking, evalúa permisos y redirige a una URL firmada ligada a IP/sesión.
     */
    public function requestDownload(Request $request, string $slug): RedirectResponse
    {
        $item = DownloadItem::where('slug', $slug)->first();
        if (!$item) {
            abort(404);
        }

        // Bloquear bots e IAs de rastreo (ClaudeBot, meta-externalagent, GPTBot, etc.) para que no descarguen binarios ni falseen contadores
        if ($this->deliveryService->isAutomatedBotCrawler($request)) {
            abort(403, 'Descarga binaria reservada a navegadores de usuario.', [
                'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            ]);
        }

        $user = $request->user();
        $decision = $this->deliveryService->evaluateAccess($item, $user, $request);

        if (!$decision->allowed) {
            if ($decision->httpStatus === 404) {
                abort(404);
            }

            if ($decision->reasonCode === 'auth_required') {
                return redirect()->guest(route('login'))->with('error', $decision->message);
            }

            abort($decision->httpStatus, $decision->message);
        }

        // Protección anti-hotlinking: si una web externa enlaza directo al endpoint de descarga,
        // redirigir al visitante a la ficha SEO oficial del recurso en lugar de regalar ancho de banda directo.
        if ($this->deliveryService->isExternalHotlink($request)) {
            return redirect()->to(DownloadSetting::cached()->publicItemUrl($item->slug), 302, [
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            ]);
        }

        $signedUrl = $this->deliveryService->generateSignedStreamUrl($item, $request, $user);

        return redirect()->to($signedUrl, 302, [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Robots-Tag'  => 'noindex, nofollow',
        ]);
    }

    /**
     * Transmite el paquete binario verificando firma criptográfica, contexto IP/sesión y permisos vigentes.
     */
    public function stream(Request $request, int $downloadItem): Response
    {
        $user = $request->user();

        if (!$request->hasValidSignature() || !$this->deliveryService->hasValidClientContext($request, $user)) {
            abort(403, 'El enlace seguro de descarga ha expirado o pertenece a otra sesión.');
        }

        $item = DownloadItem::find($downloadItem);
        if (!$item) {
            abort(404);
        }

        // Defensa en profundidad: revalidar acceso antes de iniciar el streaming binario
        $decision = $this->deliveryService->evaluateAccess($item, $user, $request);
        if (!$decision->allowed) {
            abort($decision->httpStatus, $decision->message ?: 'Acceso denegado.');
        }

        return $this->deliveryService->streamFile($item, $user, $request);
    }
}

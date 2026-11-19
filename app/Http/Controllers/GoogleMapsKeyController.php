<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint seguro para obtener la API key de Google Maps bajo demanda.
 *
 * La clave NO se incluye en el JSON global de siteSettings para evitar
 * exponerla en el HTML de todas las páginas públicas. Solo se sirve
 * cuando un componente de mapa la solicita, protegido por rate-limit.
 */
class GoogleMapsKeyController extends Controller
{
    /**
     * Devolver la API key de Google Maps.
     *
     * Protegida con throttle en la definición de ruta para limitar
     * el abuso por parte de bots o scrapers automatizados.
     */
    public function __invoke(): JsonResponse
    {
        $settings = SiteSetting::cached();
        $key = $settings->google_maps_api_key;

        if (empty($key)) {
            return response()->json(['key' => null], 200);
        }

        return response()->json(['key' => $key], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag'  => 'noindex, nofollow',
        ]);
    }
}

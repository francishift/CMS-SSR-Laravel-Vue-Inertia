<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para el Panel de Control principal (Dashboard) del CMS.
 *
 * Proporciona las métricas y accesos directos principales del sistema,
 * incluyendo el estado de envíos de formularios y alertas de nuevos mensajes.
 */
class DashboardController extends Controller
{
    /**
     * Muestra la vista principal del panel de control.
     */
    public function __invoke(Request $request): Response
    {
        $estadisticasEnvios = [
            'total'        => FormSubmission::count(),
            'no_leidos'    => FormSubmission::unread()->count(),
            'ultimo_envio' => FormSubmission::latest('id')->value('created_at')?->toISOString(),
        ];

        return Inertia::render('Dashboard', [
            'estadisticasEnvios'    => $estadisticasEnvios,
            'estadisticasDescargas' => $this->obtenerEstadisticasDescargas($request),
        ]);
    }

    /**
     * Obtiene métricas ligeras en una sola consulta para el widget de Descargas si el módulo está activo.
     *
     * @return array<string, mixed>|null
     */
    private function obtenerEstadisticasDescargas(Request $request): ?array
    {
        if (
            !class_exists(\App\Modules\Downloads\Models\DownloadItem::class) ||
            !class_exists(\App\Modules\Downloads\Models\DownloadSetting::class)
        ) {
            return null;
        }

        $user = $request->user();
        $adminRole = (string) config('downloads.admin_role', 'admin');
        if ($user === null || !method_exists($user, 'hasRole') || !$user->hasRole($adminRole)) {
            return ['disponible' => false];
        }

        try {
            $totales = \App\Modules\Downloads\Models\DownloadItem::query()
                ->selectRaw('COUNT(*) as total_items')
                ->selectRaw("SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_items")
                ->selectRaw('COALESCE(SUM(downloads_count), 0) as total_downloads')
                ->first();

            $settings = \App\Modules\Downloads\Models\DownloadSetting::cached();

            return [
                'disponible'      => true,
                'titulo'          => $settings->page_title ?: 'Descargas',
                'total_items'     => (int) ($totales->total_items ?? 0),
                'published_items' => (int) ($totales->published_items ?? 0),
                'total_downloads' => (int) ($totales->total_downloads ?? 0),
                'visibilidad'     => $settings->public_visibility,
                'public_url'      => $settings->publicIndexUrl(),
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}

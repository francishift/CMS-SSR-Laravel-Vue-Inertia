<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogResource;
use App\Modules\Downloads\Http\Resources\DownloadLogResource;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadLog;
use App\Modules\Downloads\Models\DownloadSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Controlador del Centro de Actividad, Telemetría IP de Descargas y Auditoría del CMS.
 *
 * Exclusivo para administradores (`role:admin`). Diseñado con Eager Loading estricto,
 * consultas agregadas atómicas, paginación en servidor y API Resources para soportar
 * cientos de miles de eventos sin penalizar memoria ni tiempo de respuesta.
 */
class ActivityCenterController extends Controller
{
    /**
     * Muestra el centro unificado con pestañas de Telemetría de Descargas (IPs + Históricos)
     * y Auditoría del CMS (Spatie Activitylog).
     */
    public function index(Request $request): Response
    {
        $tab = $request->query('tab') === 'auditoria' ? 'auditoria' : 'descargas';

        // 1. Filtros normalizados para Telemetría de Descargas (nunca '' en PrimeVue 4 Selects)
        $dlSearch  = trim((string) $request->query('dl_search', ''));
        $dlItemId  = (string) $request->query('dl_item', 'all');
        $dlPeriodo = (string) $request->query('dl_period', 'all');

        // 2. Filtros normalizados para Auditoría del CMS (Spatie)
        $actSearch = trim((string) $request->query('act_search', ''));
        $actModulo = (string) $request->query('act_module', 'all');
        $actEvento = (string) $request->query('act_event', 'all');

        // 3. Métricas perpetuas y ranking de Descargas (conservadas incluso tras purgar logs)
        $metricasDescargas = $this->calcularMetricasDescargas();

        // 4. Consulta paginada optimizada de `download_logs` (Cero N+1)
        $dlLogsQuery = DownloadLog::query()
            ->with([
                'item' => static fn ($q) => $q->withTrashed()->select('id', 'title', 'slug', 'version'),
                'user:id,name,email',
            ]);

        if ($dlItemId !== 'all' && is_numeric($dlItemId)) {
            $dlLogsQuery->where('download_item_id', (int) $dlItemId);
        }

        if ($dlPeriodo !== 'all') {
            $desde = match ($dlPeriodo) {
                'today' => now()->startOfDay(),
                '7d'    => now()->subDays(7),
                '30d'   => now()->subDays(30),
                '90d'   => now()->subDays(90),
                default => null,
            };
            if ($desde !== null) {
                $dlLogsQuery->where('created_at', '>=', $desde);
            }
        }

        if ($dlSearch !== '') {
            $dlLogsQuery->where(function ($q) use ($dlSearch) {
                $q->where('ip_address', 'like', "%{$dlSearch}%")
                  ->orWhere('version_downloaded', 'like', "%{$dlSearch}%")
                  ->orWhere('user_agent', 'like', "%{$dlSearch}%")
                  ->orWhere('referer_url', 'like', "%{$dlSearch}%")
                  ->orWhereHas('item', static function ($iq) use ($dlSearch) {
                      $iq->withTrashed()->where('title', 'like', "%{$dlSearch}%");
                  });
            });
        }

        $dlLogsPaginated = $dlLogsQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'dl_page')
            ->withQueryString();

        // 5. Consulta paginada optimizada de `activity_log` (Spatie)
        $actQuery = Activity::query()->with(['causer:id,name,email']);

        if ($actModulo !== 'all') {
            $actQuery->where('log_name', $actModulo);
        }

        if ($actEvento !== 'all') {
            $actQuery->where('event', $actEvento);
        }

        if ($actSearch !== '') {
            $actQuery->where(function ($q) use ($actSearch) {
                $q->where('description', 'like', "%{$actSearch}%")
                  ->orWhere('properties', 'like', "%{$actSearch}%")
                  ->orWhereHasMorph('causer', [\App\Models\User::class], static function ($uq) use ($actSearch) {
                      $uq->where('name', 'like', "%{$actSearch}%")
                         ->orWhere('email', 'like', "%{$actSearch}%");
                  });
            });
        }

        $actPaginated = $actQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'act_page')
            ->withQueryString();

        $totalAuditoria = (int) Activity::query()->count();

        return Inertia::render('Actividad/Index', [
            'tabActiva'         => $tab,
            'metricasDescargas' => $metricasDescargas,
            'logsDescargas'     => [
                'data' => DownloadLogResource::collection($dlLogsPaginated->getCollection())->resolve($request),
                'meta' => [
                    'current_page' => $dlLogsPaginated->currentPage(),
                    'last_page'    => $dlLogsPaginated->lastPage(),
                    'per_page'     => $dlLogsPaginated->perPage(),
                    'total'        => $dlLogsPaginated->total(),
                    'links'        => $dlLogsPaginated->linkCollection()->toArray(),
                ],
            ],
            'logsAuditoria'     => [
                'data' => ActivityLogResource::collection($actPaginated->getCollection())->resolve($request),
                'meta' => [
                    'current_page' => $actPaginated->currentPage(),
                    'last_page'    => $actPaginated->lastPage(),
                    'per_page'     => $actPaginated->perPage(),
                    'total'        => $actPaginated->total(),
                    'links'        => $actPaginated->linkCollection()->toArray(),
                ],
            ],
            'totalAuditoria'    => $totalAuditoria,
            'filtros'           => [
                'dl_search'  => $dlSearch,
                'dl_item'    => $dlItemId !== '' ? $dlItemId : 'all',
                'dl_period'  => $dlPeriodo !== '' ? $dlPeriodo : 'all',
                'act_search' => $actSearch,
                'act_module' => $actModulo !== '' ? $actModulo : 'all',
                'act_event'  => $actEvento !== '' ? $actEvento : 'all',
            ],
        ]);
    }

    /**
     * Purga registros detallados de `download_logs` según antigüedad o en su totalidad,
     * manteniendo 100 % intactos el histórico global perpetuo y el contador de cada ítem.
     */
    public function purgeDownloadLogs(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:older_90,older_30,older_7,all'],
        ]);

        $mode = $validated['mode'];
        $query = DownloadLog::query();

        if ($mode !== 'all') {
            $days = match ($mode) {
                'older_90' => 90,
                'older_30' => 30,
                'older_7'  => 7,
            };
            $query->where('created_at', '<=', now()->subDays($days));
        }

        $eliminados = $query->delete();

        activity('seguridad')
            ->causedBy($request->user())
            ->event('purged')
            ->withProperties(['modo' => $mode, 'registros_eliminados' => $eliminados])
            ->log("Purgó {$eliminados} registros detallados de descargas (históricos totales conservados)");

        return back()->with(
            'success',
            "Limpieza completada: {$eliminados} registros de IPs eliminados. El histórico total general y de cada descarga permanece intacto."
        );
    }

    /**
     * Purga registros de auditoría del CMS (`activity_log`) según antigüedad o en su totalidad.
     */
    public function purgeActivityLogs(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:older_90,older_30,older_7,all'],
        ]);

        $mode = $validated['mode'];
        $query = Activity::query();

        if ($mode !== 'all') {
            $days = match ($mode) {
                'older_90' => 90,
                'older_30' => 30,
                'older_7'  => 7,
            };
            $query->where('created_at', '<=', now()->subDays($days));
        }

        $eliminados = $query->delete();

        return back()->with('success', "Limpieza de auditoría completada: {$eliminados} eventos eliminados.");
    }

    /**
     * Calcula KPIs perpetuos y ranking por recurso en mínimas consultas SQL agregadas.
     *
     * @return array<string, mixed>
     */
    private function calcularMetricasDescargas(): array
    {
        $items = DownloadItem::withTrashed()
            ->select(['id', 'title', 'slug', 'version', 'status', 'downloads_count', 'views_count', 'deleted_at'])
            ->orderByDesc('downloads_count')
            ->orderBy('order', 'asc')
            ->get();

        $sumaItems = (int) $items->sum('downloads_count');
        $guardadoGlobal = (int) (DownloadSetting::query()->value('total_downloads_all_time') ?? 0);

        // Si por importación previa la suma de ítems superaba el contador global, sincronizar al máximo real
        if ($sumaItems > $guardadoGlobal) {
            DB::table((new DownloadSetting())->getTable())->update(['total_downloads_all_time' => $sumaItems]);
            $guardadoGlobal = $sumaItems;
        }

        $hace30d = now()->subDays(30);
        $hace7d = now()->subDays(7);

        $agregadoLogs = DownloadLog::query()
            ->selectRaw('
                COUNT(*) as total_logs,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as ultimos_30d,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as ultimos_7d
            ', [$hace30d, $hace7d])
            ->first();

        $ipsUnicas30d = (int) DownloadLog::query()
            ->where('created_at', '>=', $hace30d)
            ->distinct()
            ->count(DB::raw('COALESCE(ip_address, ip_hash)'));

        $maxItemCount = max(1, (int) ($items->max('downloads_count') ?? 1));

        $ranking = $items->map(static function (DownloadItem $item) use ($maxItemCount, $guardadoGlobal): array {
            $count = (int) $item->downloads_count;
            return [
                'id'              => $item->id,
                'title'           => $item->title,
                'slug'            => $item->slug,
                'version'         => $item->version ?: '—',
                'status'          => $item->trashed() ? 'trashed' : $item->status,
                'downloads_count' => $count,
                'views_count'     => (int) $item->views_count,
                'bar_percent'     => $count > 0 ? max(4, (int) round(($count / $maxItemCount) * 100)) : 0,
                'share_percent'   => $guardadoGlobal > 0 ? round(($count / $guardadoGlobal) * 100, 1) : 0.0,
            ];
        })->values()->all();

        return [
            'historico_global'     => $guardadoGlobal,
            'descargas_30d'        => (int) ($agregadoLogs->ultimos_30d ?? 0),
            'descargas_7d'         => (int) ($agregadoLogs->ultimos_7d ?? 0),
            'ips_unicas_30d'       => $ipsUnicas30d,
            'logs_almacenados'     => (int) ($agregadoLogs->total_logs ?? 0),
            'retencion_automatica' => (int) config('downloads.delivery.log_retention_days', 180),
            'ranking_items'        => $ranking,
        ];
    }
}

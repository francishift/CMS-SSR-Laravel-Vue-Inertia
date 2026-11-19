<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Services;

use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadLog;
use App\Modules\Downloads\Models\DownloadSetting;
use App\Services\HtmlSanitizerService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de dominio para gestionar el catálogo de descargas en el backend y frontend.
 *
 * Optimizado para soportar miles de registros sin consultas N+1 ni bloqueos de tabla.
 */
class DownloadCatalogService
{
    public function __construct(
        private readonly DownloadVaultService $vaultService,
        private readonly HtmlSanitizerService $htmlSanitizer,
    ) {}

    /**
     * Listado paginado para el panel de administración con filtros y ordenación dinámica.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function listarAdmin(array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DownloadItem::query()
            ->with(['author:id,name']);

        if (!empty($filtros['search'])) {
            $search = trim((string) $filtros['search']);
            $driver = DB::connection()->getDriverName();
            $cleanTerms = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $search) ?? '';
            $words = array_values(array_filter(explode(' ', trim($cleanTerms)), static fn (string $w) => mb_strlen($w) >= 3));

            // Aprovechar índice FULLTEXT en MySQL/MariaDB para grandes volúmenes + respaldo exacto en slug/versión
            if (in_array($driver, ['mysql', 'mariadb'], true) && !empty($words)) {
                $booleanQuery = implode(' ', array_map(static fn (string $w) => '+' . $w . '*', $words));
                $query->where(function ($q) use ($booleanQuery, $search) {
                    $q->whereRaw('MATCH(title, subtitle, short_description) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery])
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('version', 'like', "%{$search}%");
                });
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('subtitle', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('short_description', 'like', "%{$search}%")
                      ->orWhere('version', 'like', "%{$search}%")
                      ->orWhere('compatibility', 'like', "%{$search}%");
                });
            }
        }

        if (!empty($filtros['status']) && $filtros['status'] !== 'all') {
            $query->where('status', (string) $filtros['status']);
        }

        if (!empty($filtros['access_type']) && $filtros['access_type'] !== 'all') {
            $query->where('access_type', (string) $filtros['access_type']);
        }

        $allowedSorts = ['title', 'status', 'version', 'downloads_count', 'file_size_bytes', 'published_at', 'created_at'];
        $sortBy = (string) ($filtros['sort_by'] ?? '');
        $sortDir = strtolower((string) ($filtros['sort_dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir)->orderBy('id', 'desc');
        } else {
            // Orden manual Drag & Drop por defecto
            $query->orderBy('order', 'asc')->orderBy('id', 'desc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Listado paginado público para SSR (solo elementos publicados y ordenados por `order`).
     */
    public function listarPublicados(int $perPage = 12, int $pageNumber = 1): LengthAwarePaginator
    {
        return DownloadItem::publicados()
            ->select([
                'id',
                'title',
                'subtitle',
                'slug',
                'short_description',
                'featured_image',
                'icon',
                'version',
                'compatibility',
                'demo_url',
                'documentation_url',
                'private_file_path',
                'file_size_bytes',
                'access_type',
                'price_cents',
                'compare_at_price_cents',
                'currency',
                'status',
                'published_at',
                'order',
                'is_featured',
                'downloads_count',
                'updated_at',
            ])
            ->orderBy('order', 'asc')
            ->orderByDesc('published_at')
            ->paginate($perPage, ['*'], 'page', $pageNumber);
    }

    /**
     * Obtiene un ítem para la vista pública (permitiendo borradores solo si previsualiza un admin).
     */
    public function obtenerPorSlugParaVista(string $slug, bool $incluirBorradoresAdmin = false): ?DownloadItem
    {
        $query = $incluirBorradoresAdmin
            ? DownloadItem::query()
            : DownloadItem::publicados();

        return $query->with(['author:id,name'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Crea un nuevo elemento descargable y procesa opcionalmente su paquete binario.
     *
     * @param  array<string, mixed>  $datos
     */
    public function crear(array $datos, ?UploadedFile $packageFile = null, ?int $authorId = null): DownloadItem
    {
        $datosLimpios = $this->prepararDatosEditorial($datos);
        $datosLimpios['author_id'] = $authorId;

        if (!isset($datosLimpios['order'])) {
            $maxOrder = DownloadItem::withTrashed()->max('order');
            $datosLimpios['order'] = $maxOrder !== null ? ((int) $maxOrder + 1) : 0;
        }

        if ($packageFile !== null) {
            $vaultMeta = $this->vaultService->storeUploadedPackage($packageFile);
            $datosLimpios = array_merge($datosLimpios, $vaultMeta);
        }

        return DownloadItem::create($datosLimpios);
    }

    /**
     * Actualiza un elemento descargable y reemplaza de forma segura su paquete si se adjunta uno nuevo.
     *
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(DownloadItem $item, array $datos, ?UploadedFile $packageFile = null): DownloadItem
    {
        $datosLimpios = $this->prepararDatosEditorial($datos);

        if ($packageFile !== null) {
            $oldDisk = $item->vault_disk;
            $oldPath = $item->private_file_path;

            $vaultMeta = $this->vaultService->storeUploadedPackage($packageFile);
            $datosLimpios = array_merge($datosLimpios, $vaultMeta);

            $item->update($datosLimpios);

            // Limpiar paquete antiguo únicamente tras confirmar guardado exitoso en BD
            $this->vaultService->deleteStoredPackage($oldDisk, $oldPath);

            return $item->fresh();
        }

        $item->update($datosLimpios);

        return $item->fresh();
    }

    /**
     * Duplica la ficha editorial y metadatos de una descarga como borrador (sin duplicar el binario).
     */
    public function duplicar(DownloadItem $original, ?int $authorId = null): DownloadItem
    {
        $copia = $original->replicate([
            'slug',
            'vault_disk',
            'private_file_path',
            'original_filename',
            'mime_type',
            'file_size_bytes',
            'sha256_hash',
            'downloads_count',
            'views_count',
            'published_at',
        ]);

        $copia->title = $original->title . ' (Copia)';
        $copia->slug = DownloadItem::generarSlugUnico($copia->title);
        $copia->status = DownloadItem::STATUS_DRAFT;
        $copia->published_at = null;
        $copia->author_id = $authorId ?? $original->author_id;
        $copia->order = ((int) DownloadItem::withTrashed()->max('order')) + 1;
        $copia->save();

        return $copia;
    }

    /**
     * Reordena elementos de una página en una sola sentencia SQL CASE WHEN dentro de una transacción.
     *
     * @param  array<int, int|string>  $orderedIds
     */
    public function reordenar(array $orderedIds, int $page = 1, int $perPage = 20): void
    {
        $cleanIds = array_values(array_unique(array_map('intval', $orderedIds)));
        if (empty($cleanIds)) {
            return;
        }

        $offset = max(0, ($page - 1) * $perPage);

        DB::transaction(function () use ($cleanIds, $offset) {
            $cases = [];
            $bindings = [];

            foreach ($cleanIds as $index => $id) {
                $cases[] = 'WHEN ? THEN ?';
                $bindings[] = $id;
                $bindings[] = $offset + $index;
            }

            $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
            $bindings = array_merge($bindings, $cleanIds);

            $table = (new DownloadItem())->getTable();
            $caseSql = implode(' ', $cases);

            DB::update(
                "UPDATE {$table} SET `order` = CASE id {$caseSql} END WHERE id IN ({$placeholders})",
                $bindings
            );
        });

        \App\Services\CacheInvalidationService::limpiarCachePublica();
    }

    /**
     * Envía un elemento a la papelera (conserva el binario por si se restaura).
     */
    public function enviarAPapelera(DownloadItem $item): void
    {
        $item->delete();
    }

    /**
     * Lista elementos en la papelera paginados.
     */
    public function listarPapelera(int $perPage = 20): LengthAwarePaginator
    {
        return DownloadItem::onlyTrashed()
            ->with(['author:id,name'])
            ->orderByDesc('deleted_at')
            ->paginate($perPage);
    }

    public function conteoPapelera(): int
    {
        return DownloadItem::onlyTrashed()->count();
    }

    public function restaurar(int $id): DownloadItem
    {
        $item = DownloadItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return $item;
    }

    /**
     * Elimina definitivamente un elemento y purga su archivo físico de la bóveda privada.
     */
    public function eliminarDefinitivo(int $id): void
    {
        $item = DownloadItem::onlyTrashed()->findOrFail($id);
        $disk = $item->vault_disk;
        $path = $item->private_file_path;

        $item->forceDelete();
        $this->vaultService->deleteStoredPackage($disk, $path);
    }

    /**
     * Vacía por completo la papelera liberando el almacenamiento privado en bloques (chunkById).
     */
    public function vaciarPapelera(): int
    {
        $eliminados = 0;

        DownloadItem::onlyTrashed()->chunkById(100, function ($items) use (&$eliminados) {
            foreach ($items as $item) {
                $disk = $item->vault_disk;
                $path = $item->private_file_path;
                $item->forceDelete();
                $this->vaultService->deleteStoredPackage($disk, $path);
                $eliminados++;
            }
        });

        return $eliminados;
    }

    /**
     * Actualiza la configuración general del portal de descargas.
     *
     * @param  array<string, mixed>  $datos
     */
    public function actualizarConfiguracion(array $datos): DownloadSetting
    {
        $settings = DownloadSetting::query()->first()
            ?? new DownloadSetting(DownloadSetting::defaultAttributes());

        if (array_key_exists('description', $datos)) {
            $datos['description'] = $datos['description'] !== null
                ? trim(strip_tags((string) $datos['description']))
                : null;
        }

        $settings->fill($datos);
        $settings->save();
        DownloadSetting::clearCache();

        return DownloadSetting::cached();
    }

    /**
     * Obtiene métricas agregadas de rendimiento para carga diferida (`Inertia::defer`) en el panel.
     *
     * @return array<string, int>
     */
    public function obtenerEstadisticasBackend(): array
    {
        $totales = DownloadItem::query()
            ->selectRaw('COUNT(*) as total_items')
            ->selectRaw("SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_items")
            ->selectRaw('COALESCE(SUM(downloads_count), 0) as total_downloads')
            ->selectRaw('COALESCE(SUM(file_size_bytes), 0) as total_bytes')
            ->first();

        $descargasUltimos30Dias = DownloadLog::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'total_items'          => (int) ($totales->total_items ?? 0),
            'published_items'      => (int) ($totales->published_items ?? 0),
            'total_downloads'      => (int) ($totales->total_downloads ?? 0),
            'downloads_last_30d'   => (int) $descargasUltimos30Dias,
            'total_vault_bytes'    => (int) ($totales->total_bytes ?? 0),
        ];
    }

    /**
     * Sanitiza HTML y normaliza importes monetarios antes de persistir en base de datos.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function prepararDatosEditorial(array $datos): array
    {
        if (array_key_exists('body', $datos)) {
            $datos['body'] = $this->htmlSanitizer->sanitize($datos['body']);
        }

        if (array_key_exists('subtitle', $datos) && $datos['subtitle'] !== null) {
            $cleanSub = trim(strip_tags((string) $datos['subtitle']));
            $datos['subtitle'] = $cleanSub !== '' ? $cleanSub : null;
        }

        if (array_key_exists('short_description', $datos) && $datos['short_description'] !== null) {
            $datos['short_description'] = trim(strip_tags((string) $datos['short_description']));
        }

        if (array_key_exists('changelog', $datos) && $datos['changelog'] !== null) {
            $datos['changelog'] = trim(strip_tags((string) $datos['changelog']));
        }

        // Si el acceso no es de pago, garantizar precio 0 céntimos por coherencia
        if (($datos['access_type'] ?? DownloadItem::ACCESS_FREE) !== DownloadItem::ACCESS_PAID) {
            $datos['price_cents'] = 0;
            $datos['compare_at_price_cents'] = null;
        }

        if (empty($datos['currency'])) {
            $datos['currency'] = (string) config('downloads.defaults.currency', 'EUR');
        }

        return $datos;
    }
}

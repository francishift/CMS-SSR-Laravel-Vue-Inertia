<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Downloads\Http\Requests\StoreDownloadItemRequest;
use App\Modules\Downloads\Http\Requests\UpdateDownloadItemRequest;
use App\Modules\Downloads\Http\Requests\UpdateDownloadSettingRequest;
use App\Modules\Downloads\Http\Resources\DownloadItemAdminResource;
use App\Modules\Downloads\Http\Resources\DownloadSettingResource;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use App\Modules\Downloads\Services\DownloadCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de administración del módulo autónomo de Descargas.
 */
class AdminDownloadController extends Controller
{
    public function __construct(
        private readonly DownloadCatalogService $catalogService,
    ) {}

    /**
     * Panel principal: listado paginado, ordenación Drag & Drop, ajustes del portal y métricas diferidas.
     */
    public function index(Request $request): Response
    {
        $filtros = $request->only(['search', 'status', 'access_type', 'sort_by', 'sort_dir']);
        $items = $this->catalogService->listarAdmin($filtros, 20);
        $settings = DownloadSetting::cached();

        return Inertia::render('Descargas/Index', [
            'descargas'              => DownloadItemAdminResource::collection($items)->response()->getData(true),
            'configuracion'          => (new DownloadSettingResource($settings))->resolve(),
            'filtros'                => $filtros,
            'conteoPapelera'         => $this->catalogService->conteoPapelera(),
            'estadosDisponibles'     => DownloadItem::estadosDisponibles(),
            'tiposAccesoDisponibles' => DownloadItem::tiposAccesoDisponibles(),
            // Estadísticas agregadas cargadas en segundo plano sin bloquear el render inicial
            'estadisticas'           => Inertia::defer(fn () => $this->catalogService->obtenerEstadisticasBackend()),
        ]);
    }

    /**
     * Formulario de creación y subida de una nueva descarga.
     */
    public function create(Request $request): Response
    {
        $settings = DownloadSetting::cached();

        return Inertia::render('Descargas/Create', [
            'configuracion'          => (new DownloadSettingResource($settings))->resolve(),
            'estadosDisponibles'     => DownloadItem::estadosDisponibles(),
            'tiposAccesoDisponibles' => DownloadItem::tiposAccesoDisponibles(),
            'limitesSubida'          => $this->obtenerLimitesSubida(),
            'monedaPorDefecto'       => (string) config('downloads.defaults.currency', 'EUR'),
            'filtrosRetorno'         => $request->only(['search', 'status', 'access_type', 'page']),
        ]);
    }

    /**
     * Almacena una nueva descarga y procesa su paquete en la bóveda privada.
     */
    public function store(StoreDownloadItemRequest $request): RedirectResponse
    {
        $this->catalogService->crear(
            $request->validated(),
            $request->file('package_file'),
            $request->user()?->id
        );

        return redirect()
            ->route('downloads.admin.index')
            ->with('success', 'Descarga creada correctamente.');
    }

    /**
     * Formulario de edición y maquetación de una descarga existente.
     */
    public function edit(Request $request, DownloadItem $downloadItem): Response
    {
        $downloadItem->loadMissing('author:id,name');
        $settings = DownloadSetting::cached();

        return Inertia::render('Descargas/Edit', [
            'descarga'               => (new DownloadItemAdminResource($downloadItem))->resolve(),
            'configuracion'          => (new DownloadSettingResource($settings))->resolve(),
            'estadosDisponibles'     => DownloadItem::estadosDisponibles(),
            'tiposAccesoDisponibles' => DownloadItem::tiposAccesoDisponibles(),
            'limitesSubida'          => $this->obtenerLimitesSubida(),
            'filtrosRetorno'         => $request->only(['search', 'status', 'access_type', 'page']),
        ]);
    }

    /**
     * Actualiza metadatos, maquetación y opcionalmente reemplaza el paquete `.zip`.
     */
    public function update(UpdateDownloadItemRequest $request, DownloadItem $downloadItem): RedirectResponse
    {
        $this->catalogService->actualizar(
            $downloadItem,
            $request->validated(),
            $request->file('package_file')
        );

        return back()->with('success', 'Descarga actualizada correctamente.');
    }

    /**
     * Cambio rápido de estado (Publicado / Borrador) desde la tabla principal.
     */
    public function changeStatus(Request $request, DownloadItem $downloadItem): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(DownloadItem::estadosDisponibles()))],
        ]);

        $nuevoEstado = $validated['status'];
        $datos = ['status' => $nuevoEstado];
        if ($nuevoEstado === DownloadItem::STATUS_PUBLISHED && $downloadItem->published_at === null) {
            $datos['published_at'] = now();
        }

        $downloadItem->update($datos);

        return back()->with('success', 'Estado de la descarga actualizado.');
    }

    /**
     * Duplica la ficha de una descarga como borrador.
     */
    public function duplicate(Request $request, DownloadItem $downloadItem): RedirectResponse
    {
        $copia = $this->catalogService->duplicar($downloadItem, $request->user()?->id);

        return redirect()
            ->route('downloads.admin.edit', $copia->id)
            ->with('success', 'Ficha duplicada como borrador. Sube el archivo correspondiente cuando esté listo.');
    }

    /**
     * Guarda el orden manual Drag & Drop de las descargas.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'      => ['required', 'array', 'min:1'],
            'ids.*'    => ['integer'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $this->catalogService->reordenar(
            $validated['ids'],
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 20)
        );

        return back()->with('success', 'Orden de las descargas actualizado.');
    }

    /**
     * Actualiza la configuración global del portal de Descargas (título, descripción, icono, Grid/Lista, visibilidad, SEO).
     */
    public function updateSettings(UpdateDownloadSettingRequest $request): RedirectResponse
    {
        $this->catalogService->actualizarConfiguracion($request->validated());

        return back()->with('success', 'Configuración del catálogo de descargas guardada.');
    }

    /**
     * Vista de papelera paginada.
     */
    public function trashed(): Response
    {
        $items = $this->catalogService->listarPapelera(20);

        return Inertia::render('Descargas/Trashed', [
            'descargas' => DownloadItemAdminResource::collection($items)->response()->getData(true),
        ]);
    }

    /**
     * Envía una descarga a la papelera.
     */
    public function destroy(DownloadItem $downloadItem): RedirectResponse
    {
        $this->catalogService->enviarAPapelera($downloadItem);

        return back()->with('success', 'Descarga enviada a la papelera.');
    }

    /**
     * Restaura una descarga desde la papelera.
     */
    public function restore(int $id): RedirectResponse
    {
        $this->catalogService->restaurar($id);

        return back()->with('success', 'Descarga restaurada correctamente.');
    }

    /**
     * Elimina definitivamente una descarga y borra su paquete físico de la bóveda privada.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $this->catalogService->eliminarDefinitivo($id);

        return back()->with('success', 'Descarga y archivo privado eliminados definitivamente.');
    }

    /**
     * Vacía toda la papelera y purga sus archivos físicos en segundo plano / por lotes.
     */
    public function emptyTrash(): RedirectResponse
    {
        $total = $this->catalogService->vaciarPapelera();

        return back()->with('success', "Papelera vaciada ({$total} elementos eliminados definitivamente).");
    }

    /**
     * @return array<string, mixed>
     */
    private function obtenerLimitesSubida(): array
    {
        $maxKb = (int) config('downloads.vault.max_upload_kb', 102400);

        return [
            'max_kb'             => $maxKb,
            'max_mb'             => round($maxKb / 1024, 1),
            'allowed_extensions' => (array) config('downloads.vault.allowed_extensions', ['zip']),
        ];
    }
}

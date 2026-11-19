<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBuilderLibraryItemRequest;
use App\Http\Requests\UpdateBuilderLibraryItemRequest;
use App\Http\Resources\BuilderLibraryItemResource;
use App\Models\BuilderLibraryItem;
use App\Services\BuilderLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador API para la Biblioteca de Presets y Elementos del Page Builder.
 */
class BuilderLibraryController extends Controller
{
    public function __construct(
        private readonly BuilderLibraryService $libraryService,
    ) {}

    /**
     * Listar elementos guardados en la biblioteca.
     */
    public function index(Request $request): JsonResponse
    {
        $type   = $request->query('type');
        $search = $request->query('search');

        $items = $this->libraryService->listar($type, $search);

        return response()->json([
            'success' => true,
            'items'   => BuilderLibraryItemResource::collection($items)->resolve(),
        ]);
    }

    /**
     * Guardar un nuevo elemento en la biblioteca.
     */
    public function store(StoreBuilderLibraryItemRequest $request): JsonResponse
    {
        $item = $this->libraryService->crear(
            $request->validated(),
            $request->user()?->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Elemento guardado correctamente en la biblioteca.',
            'item'    => (new BuilderLibraryItemResource($item))->resolve(),
        ], 201);
    }

    /**
     * Actualizar datos de un elemento guardado.
     */
    public function update(UpdateBuilderLibraryItemRequest $request, BuilderLibraryItem $builderLibraryItem): JsonResponse
    {
        $this->libraryService->actualizar($builderLibraryItem, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Preset de la biblioteca actualizado correctamente.',
            'item'    => (new BuilderLibraryItemResource($builderLibraryItem))->resolve(),
        ]);
    }

    /**
     * Eliminar un elemento de la biblioteca.
     */
    public function destroy(BuilderLibraryItem $builderLibraryItem): JsonResponse
    {
        $this->libraryService->eliminar($builderLibraryItem);

        return response()->json([
            'success' => true,
            'message' => 'Elemento eliminado de la biblioteca.',
        ]);
    }
}

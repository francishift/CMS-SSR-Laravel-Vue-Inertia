<?php

namespace App\Http\Controllers;

use App\Http\Resources\BuilderTemplateResource;
use App\Http\Resources\CategoryResource;
use App\Models\Article;
use App\Models\BuilderTemplate;
use App\Models\Category;
use App\Services\CacheInvalidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la Gestión y CRUD de Plantillas Dinámicas del Maquetador Visual.
 */
class BuilderTemplateController extends Controller
{
    /**
     * Muestra el panel de listado de plantillas divididas por tipo.
     */
    public function index(): Response
    {
        $templates = BuilderTemplate::with(['categoriesAsCategoryTemplate:id,name,slug,category_builder_template_id', 'categoriesAsArticleTemplate:id,name,slug,article_builder_template_id'])
            ->withCount(['categoriesAsCategoryTemplate', 'categoriesAsArticleTemplate', 'articles'])
            ->orderBy('name', 'asc')
            ->get();

        $categories = Category::activas()
            ->select('id', 'name', 'slug', 'category_builder_template_id', 'article_builder_template_id')
            ->orderBy('order', 'asc')
            ->get();

        return Inertia::render('Plantillas/Index', [
            'plantillas' => BuilderTemplateResource::collection($templates)->resolve(),
            'categorias' => CategoryResource::collection($categories)->resolve(),
        ]);
    }

    /**
     * Almacena una nueva plantilla con su estructura inicial según el tipo.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'type'                => 'required|string|in:single_article,category_archive',
            'description'         => 'nullable|string|max:1000',
            'preview_category_id' => 'nullable|exists:categories,id',
            'preview_article_id'  => 'nullable|exists:articles,id',
            'category_ids'        => 'nullable|array',
            'category_ids.*'      => 'exists:categories,id',
        ]);

        $previewCatId = $validated['preview_category_id'] ?? null;
        if (!$previewCatId && !empty($validated['category_ids'])) {
            $previewCatId = $validated['category_ids'][0];
        }

        $previewArtId = $validated['preview_article_id'] ?? null;

        $layoutInicial = BuilderTemplate::plantillaInicialPorTipo($validated['type']);

        $template = BuilderTemplate::create([
            'name'                => $validated['name'],
            'type'                => $validated['type'],
            'layout_json'         => $layoutInicial,
            'description'         => $validated['description'] ?? null,
            'preview_category_id' => $previewCatId,
            'preview_article_id'  => $previewArtId,
            'is_active'           => true,
        ]);

        // Asignar a categorías seleccionadas si aplica
        if (!empty($validated['category_ids'])) {
            $column = $template->type === 'category_archive' ? 'category_builder_template_id' : 'article_builder_template_id';
            Category::whereIn('id', $validated['category_ids'])->update([$column => $template->id]);
            CacheInvalidationService::limpiarCachePublica();
        }

        return redirect()->route('plantillas.builder', $template->id)
            ->with('success', 'Plantilla creada con éxito. Ya puedes maquetarla en el Builder.');
    }

    /**
     * Actualiza los metadatos y asignaciones de una plantilla.
     */
    public function update(Request $request, BuilderTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string|max:1000',
            'preview_category_id' => 'nullable|exists:categories,id',
            'preview_article_id'  => 'nullable|exists:articles,id',
            'is_active'           => 'boolean',
            'category_ids'        => 'nullable|array',
            'category_ids.*'      => 'exists:categories,id',
        ]);

        $template->update([
            'name'                => $validated['name'],
            'description'         => $validated['description'] ?? null,
            'preview_category_id' => $validated['preview_category_id'] ?? null,
            'preview_article_id'  => $validated['preview_article_id'] ?? null,
            'is_active'           => $validated['is_active'] ?? $template->is_active,
        ]);

        // Sincronizar asignaciones de categorías si se enviaron
        if (isset($validated['category_ids'])) {
            $column = $template->type === 'category_archive' ? 'category_builder_template_id' : 'article_builder_template_id';
            // Desasignar categorías que ya no están
            Category::where($column, $template->id)
                ->whereNotIn('id', $validated['category_ids'])
                ->update([$column => null]);
            // Asignar nuevas categorías
            Category::whereIn('id', $validated['category_ids'])
                ->update([$column => $template->id]);
        }

        CacheInvalidationService::limpiarCachePublica();

        return back()->with('success', 'Plantilla actualizada con éxito.');
    }

    /**
     * Duplica una plantilla existente en un solo clic.
     */
    public function duplicate(BuilderTemplate $template): RedirectResponse
    {
        $copia = $template->replicate();
        $copia->name = "{$template->name} (Copia)";
        $copia->preview_category_id = null;
        $copia->preview_article_id = null;
        $copia->is_active = true;
        $copia->save();

        return back()->with('success', "Plantilla '{$template->name}' duplicada correctamente.");
    }

    /**
     * Elimina de forma segura una plantilla (Soft Delete).
     */
    public function destroy(BuilderTemplate $template): RedirectResponse
    {
        $columnCategory = $template->type === 'category_archive' ? 'category_builder_template_id' : 'article_builder_template_id';
        Category::where($columnCategory, $template->id)->update([$columnCategory => null]);
        Article::where('article_builder_template_id', $template->id)->update(['article_builder_template_id' => null]);

        $nombre = $template->name;
        $template->delete();

        CacheInvalidationService::limpiarCachePublica();

        return back()->with('success', "Plantilla '{$nombre}' eliminada correctamente.");
    }
}

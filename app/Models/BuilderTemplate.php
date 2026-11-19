<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Plantilla Dinámica del Maquetador Visual (Theme Templates).
 *
 * Permite diseñar visualmente mediante el Page Builder la vista de
 * listado de una categoría (`category_archive`) o la vista de detalle
 * de un artículo (`single_article`).
 *
 * @property int $id
 * @property string $name
 * @property string $type 'category_archive' | 'single_article'
 * @property ?array $layout_json
 * @property ?string $description
 * @property ?int $preview_category_id
 * @property ?int $preview_article_id
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 */
class BuilderTemplate extends Model
{
    use SoftDeletes, \App\Models\Concerns\RecordsCmsActivity;

    public function activityLogChannel(): string { return 'maquetador'; }
    public function activityEntityLabel(): string { return 'plantilla visual'; }

    protected $fillable = [
        'name',
        'type',
        'layout_json',
        'description',
        'preview_category_id',
        'preview_article_id',
        'is_active',
    ];

    protected $casts = [
        'layout_json' => 'array',
        'is_active'   => 'boolean',
    ];

    /**
     * Categorías que usan esta plantilla para su vista de archivo/listado.
     */
    public function categoriesAsCategoryTemplate(): HasMany
    {
        return $this->hasMany(Category::class, 'category_builder_template_id');
    }

    /**
     * Categorías que usan esta plantilla para sus artículos asociados.
     */
    public function categoriesAsArticleTemplate(): HasMany
    {
        return $this->hasMany(Category::class, 'article_builder_template_id');
    }

    /**
     * Artículos que usan esta plantilla directamente.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'article_builder_template_id');
    }

    /**
     * Categoría de muestra para la previsualización en el Builder.
     */
    public function previewCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'preview_category_id');
    }

    /**
     * Artículo de muestra para la previsualización en el Builder.
     */
    public function previewArticle(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'preview_article_id');
    }

    /**
     * Scope para filtrar plantillas activas.
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar por tipo de plantilla.
     */
    public function scopePorTipo(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Devuelve una plantilla de inicio limpia y vacía.
     */
    public static function plantillaInicialPorTipo(string $type = ''): array
    {
        return [
            'version'  => '1.0',
            'sections' => [],
        ];
    }
}

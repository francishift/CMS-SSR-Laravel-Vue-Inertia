<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Modelo de Categoría del CMS.
 *
 * Soporta subcategorías, plantillas de layout, campos SEO
 * y un esquema de campos extra (JSON) que define qué campos
 * adicionales tendrán los artículos asociados.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property ?int $parent_id
 * @property string $template
 * @property ?string $featured_image
 * @property ?string $icon
 * @property ?string $color
 * @property ?array $extra_fields_schema
 * @property ?string $seo_title
 * @property ?string $seo_description
 * @property ?string $meta_robots
 * @property int $order
 * @property bool $is_active
 */
class Category extends Model
{
    use SoftDeletes, \App\Models\Concerns\RecordsCmsActivity;

    public function activityLogChannel(): string { return 'contenido'; }
    public function activityEntityLabel(): string { return 'categoría'; }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'template',
        'category_builder_template_id',
        'article_builder_template_id',
        'cols_mobile',
        'cols_tablet',
        'cols_desktop',
        'gap_size',
        'align_grid',
        'header_align',
        'title_tag',
        'articles_order',
        'per_page',
        'show_breadcrumb',
        'show_pagination',
        'show_author',
        'show_date',
        'show_reading_time',
        'show_category_link',
        'featured_image',
        'icon',
        'color',
        'extra_fields_schema',
        'seo_title',
        'seo_description',
        'seo_og_image',
        'meta_robots',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category_builder_template_id' => 'integer',
            'article_builder_template_id'  => 'integer',
            'extra_fields_schema' => 'array',
            'is_active'           => 'boolean',
            'show_breadcrumb'     => 'boolean',
            'show_pagination'     => 'boolean',
            'show_author'         => 'boolean',
            'show_date'           => 'boolean',
            'show_reading_time'   => 'boolean',
            'show_category_link'  => 'boolean',
            'order'               => 'integer',
            'cols_mobile'         => 'integer',
            'cols_tablet'         => 'integer',
            'cols_desktop'        => 'integer',
            'per_page'            => 'integer',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function categoryBuilderTemplate(): BelongsTo
    {
        return $this->belongsTo(BuilderTemplate::class, 'category_builder_template_id');
    }

    public function articleBuilderTemplate(): BelongsTo
    {
        return $this->belongsTo(BuilderTemplate::class, 'article_builder_template_id');
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * Solo categorías activas.
     */
    public function scopeActivas($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Solo categorías raíz (sin padre).
     */
    public function scopeRaiz($query)
    {
        return $query->whereNull('parent_id');
    }

    // ─── Mutadores ──────────────────────────────────────

    /**
     * Generar slug automático a partir del nombre si no se proporciona.
     */
    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            } else {
                $cleanSlug = trim($category->slug, " \t\n\r\0\x0B/");
                $category->slug = Str::slug($cleanSlug);
            }
        });

        // Invalidar caché al modificar
        $flush = function () {
            if (Cache::supportsTags()) {
                Cache::tags(['categories'])->flush();
            } else {
                Cache::forget('categories_active');
            }
        };
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Obtener las plantillas disponibles según la configuración.
     */
    public static function plantillasDisponibles(): array
    {
        return config('cms.plantillas', []);
    }
}

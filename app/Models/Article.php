<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Modelo de Artículo del CMS.
 *
 * Soporta estados (borrador, publicado, programado),
 * campos SEO, imagen destacada, campos extra dinámicos
 * definidos por la categoría, y cálculo automático
 * del tiempo de lectura.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ?string $excerpt
 * @property string $body
 * @property ?string $featured_image
 * @property ?int $category_id
 * @property int $author_id
 * @property string $status
 * @property ?\Carbon\Carbon $published_at
 * @property ?array $extra_fields_data
 * @property ?string $seo_title
 * @property ?string $seo_description
 * @property ?string $seo_og_image
 * @property ?string $meta_robots
 * @property ?int $reading_time
 * @property int $views_count
 * @property bool $is_featured
 */
class Article extends Model
{
    use SoftDeletes, \App\Models\Concerns\RecordsCmsActivity;

    public function activityLogChannel(): string { return 'contenido'; }
    public function activityEntityLabel(): string { return 'artículo'; }

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'featured_image',
        'icon',
        'category_id',
        'article_builder_template_id',
        'author_id',
        'status',
        'published_at',
        'published_until',
        'extra_fields_data',
        'seo_title',
        'seo_description',
        'seo_og_image',
        'meta_robots',
        'reading_time',
        'order',
        'is_featured',
        'show_read_more',
        'card_full_link',
        'read_more_text',
    ];

    protected function casts(): array
    {
        return [
            'article_builder_template_id' => 'integer',
            'extra_fields_data' => 'array',
            'published_at'      => 'datetime',
            'published_until'   => 'datetime',
            'is_featured'       => 'boolean',
            'show_read_more'    => 'boolean',
            'card_full_link'    => 'boolean',
            'reading_time'      => 'integer',
            'order'             => 'integer',
            'views_count'       => 'integer',
        ];
    }

    // ─── Relaciones ──────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function articleBuilderTemplate()
    {
        return $this->belongsTo(BuilderTemplate::class, 'article_builder_template_id');
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * Solo artículos publicados o programados dentro de su ventana de publicación activa.
     */
    public function scopePublicados($query)
    {
        return $query->whereIn('status', ['published', 'scheduled'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('published_until')
                  ->orWhere('published_until', '>=', now());
            });
    }

    /**
     * Solo artículos destacados.
     */
    public function scopeDestacados($query)
    {
        return $query->where('is_featured', true);
    }

    // ─── Mutadores ──────────────────────────────────────

    protected static function booted(): void
    {
        // Generar slug y calcular tiempo de lectura al crear/actualizar
        static::saving(function (self $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            } else {
                $cleanSlug = trim($article->slug, " \t\n\r\0\x0B/");
                $article->slug = Str::slug($cleanSlug);
            }

            // Calcular tiempo de lectura (200 palabras/minuto promedio)
            if ($article->isDirty('body') && !empty($article->body)) {
                $palabras = str_word_count(strip_tags($article->body));
                $article->reading_time = max(1, (int) ceil($palabras / 200));
            }
        });

        // Invalidar caché de artículos
        $flush = fn () => Cache::tags(['articles'])->flush();
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Incrementar el contador de vistas de forma atómica.
     */
    public function incrementarVistas(): void
    {
        $this->increment('views_count');
    }
}

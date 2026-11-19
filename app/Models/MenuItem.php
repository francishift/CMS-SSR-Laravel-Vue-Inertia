<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Modelo de Item de Menú.
 *
 * Soporta anidamiento (parent_id), enlaces a:
 * - URLs externas/manuales
 * - Rutas internas de Laravel
 * - Categorías del CMS
 * - Artículos del CMS
 * - Páginas estáticas del CMS
 */
class MenuItem extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'route_name',
        'category_id',
        'article_id',
        'page_id',
        'target',
        'is_nofollow',
        'icon',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'is_nofollow' => 'boolean',
            'order'       => 'integer',
            'category_id' => 'integer',
            'article_id'  => 'integer',
            'page_id'     => 'integer',
            'parent_id'   => 'integer',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    // ─── Accessors ──────────────────────────────────────

    /**
     * Resolver la URL final del item según prioridad:
     * 1. Categoría → /{slug}
     * 2. Artículo → /{categoría}/{slug}
     * 3. Página estática → /{slug}
     * 4. Ruta Laravel → route()
     * 5. URL manual → tal cual
     * 6. Fallback → '#'
     */
    public function getResolvedUrlAttribute(): string
    {
        // Enlace a categoría
        if ($this->category_id && $this->category) {
            return url("/{$this->category->slug}");
        }

        // Enlace a artículo
        if ($this->article_id && $this->article) {
            $catSlug = $this->article->category?->slug;

            return $catSlug
                ? url("/{$catSlug}/{$this->article->slug}")
                : url("/{$this->article->slug}");
        }

        // Enlace a página estática
        if ($this->page_id && $this->page) {
            return url("/{$this->page->slug}");
        }

        // Ruta interna de Laravel (con soporte directo para extensiones modulares como Descargas)
        if ($this->route_name) {
            if (
                $this->route_name === 'public.downloads.index'
                && class_exists(\App\Modules\Downloads\Models\DownloadSetting::class)
            ) {
                return \App\Modules\Downloads\Models\DownloadSetting::cached()->publicIndexUrl();
            }

            try {
                return route($this->route_name);
            } catch (\Throwable) {
                return '#';
            }
        }

        return $this->url ?? '#';
    }

    /**
     * Comprueba si este item debe mostrarse al visitante actual en los menús públicos.
     * Si apunta al módulo de Descargas y este se encuentra en visibilidad solo-backend,
     * se oculta automáticamente para visitantes anónimos sin rol administrador.
     */
    public function isPubliclyVisibleFor(?User $user = null): bool
    {
        if (
            $this->route_name === 'public.downloads.index'
            && class_exists(\App\Modules\Downloads\Models\DownloadSetting::class)
        ) {
            return \App\Modules\Downloads\Models\DownloadSetting::cached()->isPubliclyAccessible($user);
        }

        return true;
    }

    // ─── Caché ──────────────────────────────────────────

    /**
     * Invalidar caché del menú padre al modificar items.
     */
    protected static function booted(): void
    {
        $flush = function () {
            if (Cache::supportsTags()) {
                try {
                    Cache::tags(['menus'])->flush();
                } catch (\Throwable) {}
            }
            Cache::forget('menu_header');
            Cache::forget('menu_footer');
            Cache::forget('menu_sidebar');
        };

        static::saved($flush);
        static::deleted($flush);
    }
}

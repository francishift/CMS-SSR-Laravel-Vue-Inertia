<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Modelo de Menú de Navegación.
 *
 * Cada menú tiene una ubicación (header, footer, sidebar)
 * y contiene items de menú con soporte para anidamiento.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $location
 * @property bool $is_active
 */
class Menu extends Model
{
    use SoftDeletes, \App\Models\Concerns\RecordsCmsActivity;

    public function activityLogChannel(): string { return 'contenido'; }
    public function activityEntityLabel(): string { return 'menú'; }
    protected $fillable = [
        'name',
        'slug',
        'location',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ─── Relaciones ─────────────────────────────────────

    /**
     * Todos los items de este menú.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }

    /**
     * Solo los items raíz (sin padre) de este menú.
     */
    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('order');
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * Solo menús activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Caché ──────────────────────────────────────────

    /**
     * Obtener menú por ubicación desde caché.
     */
    public static function porUbicacion(string $location): ?self
    {
        $ttl = config('cms.cache.menus_ttl', 3600);

        try {
            $store = Cache::supportsTags() ? Cache::tags(['menus']) : Cache::store();
            $cacheKey = "menu_{$location}";

            $result = $store->remember(
                $cacheKey,
                $ttl,
                fn () => self::activos()
                    ->where('location', $location)
                    ->with([
                        'rootItems' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
                        'rootItems.category',
                        'rootItems.article.category',
                        'rootItems.page',
                        'rootItems.children' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
                        'rootItems.children.category',
                        'rootItems.children.article.category',
                        'rootItems.children.page',
                    ])
                    ->first()
            );

            // Protección contra objetos corruptos en caché
            if ($result !== null && !$result instanceof self) {
                $store->forget($cacheKey);

                return self::activos()
                    ->where('location', $location)
                    ->with([
                        'rootItems' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
                        'rootItems.category',
                        'rootItems.article.category',
                        'rootItems.page',
                        'rootItems.children' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
                        'rootItems.children.category',
                        'rootItems.children.article.category',
                        'rootItems.children.page',
                    ])
                    ->first();
            }

            return $result;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Obtener todos los menús activos con sus items estructurados en array desde caché.
     * Almacena datos nativos en array para evitar problemas de deserialización de Eloquent en Redis
     * y maximizar la velocidad de respuesta.
     */
    public static function activosEstructurados(): array
    {
        $ttl = config('cms.cache.menus_ttl', 3600);

        try {
            $store = Cache::supportsTags() ? Cache::tags(['menus']) : Cache::store();
            $cacheKey = 'menus_activos_con_items';

            return $store->remember(
                $cacheKey,
                $ttl,
                function () {
                    return self::activos()
                        ->with([
                            'rootItems' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
                            'rootItems.category',
                            'rootItems.article.category',
                            'rootItems.page',
                            'rootItems.children' => fn ($q) => $q->where('is_active', true)->orderBy('order'),
                            'rootItems.children.category',
                            'rootItems.children.article.category',
                            'rootItems.children.page',
                        ])
                        ->orderBy('name')
                        ->get()
                        ->map(fn ($menu) => [
                            'id'       => $menu->id,
                            'name'     => $menu->name,
                            'slug'     => $menu->slug,
                            'location' => $menu->location,
                            'items'    => $menu->rootItems->map(fn ($item) => [
                                'id'           => $item->id,
                                'title'        => $item->title,
                                'route_name'   => $item->route_name,
                                'resolved_url' => $item->resolved_url,
                                'target'       => $item->target,
                                'icon'         => $item->icon,
                                'is_active'    => (bool) $item->is_active,
                                'is_nofollow'  => (bool) $item->is_nofollow,
                                'children'     => $item->children->map(fn ($child) => [
                                    'id'           => $child->id,
                                    'title'        => $child->title,
                                    'route_name'   => $child->route_name,
                                    'resolved_url' => $child->resolved_url,
                                    'target'       => $child->target,
                                    'icon'         => $child->icon,
                                    'is_active'    => (bool) $child->is_active,
                                    'is_nofollow'  => (bool) $child->is_nofollow,
                                ])->values()->toArray(),
                            ])->values()->toArray(),
                        ])->values()->toArray();
                }
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Alias compatible con activosEstructurados.
     */
    public static function activosConItems(): array
    {
        return self::activosEstructurados();
    }

    /**
     * Invalidar caché de menús al guardar.
     */
    protected static function booted(): void
    {
        $flush = function () {
            if (Cache::supportsTags()) {
                Cache::tags(['menus'])->flush();
            } else {
                Cache::forget('menu_header');
                Cache::forget('menu_footer');
                Cache::forget('menu_sidebar');
                Cache::forget('menus_activos_con_items');
            }
        };

        static::saved($flush);
        static::deleted($flush);
    }
}

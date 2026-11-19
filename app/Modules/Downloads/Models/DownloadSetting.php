<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Models;

use App\Models\Concerns\RecordsCmsActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Configuración global del portal autónomo de Descargas (registro singleton cacheado).
 */
class DownloadSetting extends Model
{
    use RecordsCmsActivity;
    public const CACHE_KEY = 'downloads_module_settings_v2';
    private const LEGACY_CACHE_KEY = 'downloads_module_settings';

    /**
     * Memoización en memoria durante el ciclo de vida de la petición actual.
     */
    private static ?self $resolvedInstance = null;

    public const VISIBILITY_ADMIN_ONLY = 'admin_only';
    public const VISIBILITY_PUBLIC     = 'public';

    public const VIEW_GRID = 'grid';
    public const VIEW_LIST = 'list';

    protected $fillable = [
        'page_title',
        'slug',
        'description',
        'icon',
        'public_visibility',
        'use_builder_layout',
        'layout_json',
        'use_item_builder_layout',
        'item_layout_json',
        'default_view_mode',
        'allow_view_toggle',
        'cols_desktop',
        'cols_tablet',
        'cols_mobile',
        'gap_size',
        'header_align',
        'container_width',
        'show_breadcrumb',
        'show_header_icon',
        'show_description',
        'header_title_size',
        'header_desc_size',
        'per_page',
        'card_style',
        'card_text_align',
        'item_title_tag',
        'item_title_size',
        'item_desc_size',
        'item_desc_lines',
        'show_item_image',
        'show_item_description',
        'show_item_badges',
        'show_item_meta',
        'item_image_ratio',
        'item_svg_size',
        'item_image_position',
        'item_button_style',
        'item_button_size',
        'show_quick_download',
        'show_downloads_count_public',
        'total_downloads_all_time',
        'seo_title',
        'seo_description',
        'seo_og_image',
        'meta_robots',
    ];

    public function activityLogChannel(): string
    {
        return 'descargas';
    }

    public function activityEntityLabel(): string
    {
        return 'configuración de descargas';
    }

    protected function casts(): array
    {
        return [
            'use_builder_layout'          => 'boolean',
            'layout_json'                 => 'array',
            'use_item_builder_layout'     => 'boolean',
            'item_layout_json'            => 'array',
            'allow_view_toggle'           => 'boolean',
            'cols_desktop'                => 'integer',
            'cols_tablet'                 => 'integer',
            'cols_mobile'                 => 'integer',
            'show_breadcrumb'             => 'boolean',
            'show_header_icon'            => 'boolean',
            'show_description'            => 'boolean',
            'per_page'                    => 'integer',
            'show_item_image'             => 'boolean',
            'show_item_description'       => 'boolean',
            'show_item_badges'            => 'boolean',
            'show_item_meta'              => 'boolean',
            'show_quick_download'         => 'boolean',
            'show_downloads_count_public' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        return (string) config('downloads.tables.settings', 'download_settings');
    }

    protected static function booted(): void
    {
        static::saving(function (self $setting) {
            $defaultSlug = (string) config('downloads.defaults.slug', 'descargas');
            $cleanSlug = Str::slug(trim((string) ($setting->slug ?: $defaultSlug), " \t\n\r\0\x0B/"));
            $setting->slug = $cleanSlug !== '' ? $cleanSlug : $defaultSlug;
        });

        $flush = fn () => static::clearCache();
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Obtiene la configuración activa desde caché almacenando únicamente un array primitivo
     * (compatible con `serializable_classes => false` en Laravel 13, evitando `__PHP_Incomplete_Class`).
     */
    public static function cached(): self
    {
        if (self::$resolvedInstance !== null) {
            return self::$resolvedInstance;
        }

        try {
            $cachedAttributes = Cache::rememberForever(self::CACHE_KEY, function (): ?array {
                $instance = new self();
                if (!Schema::hasTable($instance->getTable())) {
                    return null;
                }

                $record = static::query()->first();
                if (!$record) {
                    $record = static::query()->create(static::defaultAttributes());
                }

                return $record->getAttributes();
            });

            if (is_array($cachedAttributes) && !empty($cachedAttributes)) {
                self::$resolvedInstance = (new self())->newFromBuilder($cachedAttributes);

                return self::$resolvedInstance;
            }

            // Si por corrupción previa en caché no es un array, purgar y leer de BD directamente
            Cache::forget(self::CACHE_KEY);
            $instance = new self();
            if (Schema::hasTable($instance->getTable())) {
                $record = static::query()->first() ?? static::query()->create(static::defaultAttributes());
                Cache::forever(self::CACHE_KEY, $record->getAttributes());
                self::$resolvedInstance = $record;

                return $record;
            }

            return static::makeDefaultInstance();
        } catch (\Throwable) {
            return static::makeDefaultInstance();
        }
    }

    /**
     * Limpia la caché persistente y la instancia memoizada en memoria.
     */
    public static function clearCache(): void
    {
        self::$resolvedInstance = null;
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::LEGACY_CACHE_KEY);
    }

    /**
     * Determina si el catálogo es estrictamente público e indexable por buscadores.
     */
    public function isStrictlyPublic(): bool
    {
        return $this->public_visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Determina si el usuario actual puede visualizar el catálogo público.
     * En modo 'admin_only', solo los administradores autenticados tienen visibilidad.
     */
    public function isPubliclyAccessible(?User $user = null): bool
    {
        if ($this->isStrictlyPublic()) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $adminRole = (string) config('downloads.admin_role', 'admin');

        return method_exists($user, 'hasRole') && $user->hasRole($adminRole);
    }

    /**
     * Construye la URL absoluta canónica del índice del catálogo (con paginación limpia opcional).
     */
    public function publicIndexUrl(int $page = 1): string
    {
        $base = '/' . ltrim($this->slug ?: (string) config('downloads.defaults.slug', 'descargas'), '/');

        return $page > 1 ? url("{$base}/pagina/{$page}") : url($base);
    }

    /**
     * Construye la URL absoluta canónica de la ficha individual de una descarga.
     */
    public function publicItemUrl(string $itemSlug): string
    {
        $base = '/' . ltrim($this->slug ?: (string) config('downloads.defaults.slug', 'descargas'), '/');

        return url("{$base}/" . ltrim($itemSlug, '/'));
    }

    /**
     * Atributos iniciales leídos íntegramente desde la configuración del módulo.
     *
     * @return array<string, mixed>
     */
    public static function defaultAttributes(): array
    {
        $defaults = (array) config('downloads.defaults', []);

        return [
            'page_title'                  => $defaults['page_title'] ?? 'Descargas',
            'slug'                        => $defaults['slug'] ?? 'descargas',
            'description'                 => $defaults['description'] ?? null,
            'icon'                        => $defaults['icon'] ?? 'pi pi-download',
            'public_visibility'           => $defaults['public_visibility'] ?? self::VISIBILITY_ADMIN_ONLY,
            'default_view_mode'           => $defaults['default_view_mode'] ?? self::VIEW_GRID,
            'allow_view_toggle'           => (bool) ($defaults['allow_view_toggle'] ?? true),
            'cols_desktop'                => (int) ($defaults['cols_desktop'] ?? 3),
            'cols_tablet'                 => (int) ($defaults['cols_tablet'] ?? 2),
            'cols_mobile'                 => (int) ($defaults['cols_mobile'] ?? 1),
            'gap_size'                    => $defaults['gap_size'] ?? 'md',
            'header_align'                => $defaults['header_align'] ?? 'left',
            'per_page'                    => (int) ($defaults['per_page'] ?? 12),
            'show_quick_download'         => (bool) ($defaults['show_quick_download'] ?? true),
            'show_downloads_count_public' => (bool) ($defaults['show_downloads_count_public'] ?? false),
        ];
    }

    private static function makeDefaultInstance(): self
    {
        return new self(static::defaultAttributes());
    }
}

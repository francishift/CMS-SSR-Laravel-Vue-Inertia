<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Models;

use App\Models\Concerns\RecordsCmsActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Modelo de Elemento Descargable autónomo.
 *
 * Preparado tanto para recursos gratuitos iniciales (ej. plugins y elementos de YOOtheme Pro)
 * como para distribución comercial de pago futura sin modificar su estructura.
 */
class DownloadItem extends Model
{
    use SoftDeletes, RecordsCmsActivity;

    public function activityLogChannel(): string
    {
        return 'descargas';
    }

    public function activityEntityLabel(): string
    {
        return 'descarga';
    }

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const ACCESS_FREE       = 'free';
    public const ACCESS_REGISTERED = 'registered';
    public const ACCESS_PAID       = 'paid';

    protected $fillable = [
        'title',
        'subtitle',
        'slug',
        'short_description',
        'body',
        'featured_image',
        'icon',
        'version',
        'compatibility',
        'changelog',
        'demo_url',
        'documentation_url',
        'vault_disk',
        'private_file_path',
        'original_filename',
        'mime_type',
        'file_size_bytes',
        'sha256_hash',
        'access_type',
        'price_cents',
        'compare_at_price_cents',
        'currency',
        'license_type',
        'external_product_id',
        'status',
        'published_at',
        'order',
        'is_featured',
        'author_id',
        'seo_title',
        'seo_description',
        'seo_og_image',
        'meta_robots',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes'        => 'integer',
            'price_cents'            => 'integer',
            'compare_at_price_cents' => 'integer',
            'published_at'           => 'datetime',
            'order'                  => 'integer',
            'is_featured'            => 'boolean',
            'downloads_count'        => 'integer',
            'views_count'            => 'integer',
            'author_id'              => 'integer',
        ];
    }

    public function getTable(): string
    {
        return (string) config('downloads.tables.items', 'download_items');
    }

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            if (empty($item->slug)) {
                $item->slug = static::generarSlugUnico($item->title, $item->id);
            } else {
                $clean = Str::slug(trim((string) $item->slug, " \t\n\r\0\x0B/"));
                $item->slug = static::generarSlugUnico($clean !== '' ? $clean : $item->title, $item->id);
            }

            if ($item->status === self::STATUS_PUBLISHED && $item->published_at === null) {
                $item->published_at = now();
            }
        });
    }

    /**
     * Garantiza unicidad del slug incluso considerando elementos en papelera.
     */
    public static function generarSlugUnico(string $baseText, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($baseText);
        if ($baseSlug === '') {
            $baseSlug = 'descarga';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    // ─── Relaciones ─────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DownloadLog::class, 'download_item_id');
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeDestacados(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    // ─── Métodos de Dominio ─────────────────────────────────

    public function isPublished(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        return $this->published_at === null || $this->published_at->lte(now());
    }

    public function isFree(): bool
    {
        return $this->access_type === self::ACCESS_FREE || $this->price_cents === 0;
    }

    public function isPaid(): bool
    {
        return $this->access_type === self::ACCESS_PAID && $this->price_cents > 0;
    }

    public function hasFile(): bool
    {
        return !empty($this->private_file_path) && $this->file_size_bytes > 0;
    }

    /**
     * Formatea el tamaño del archivo de forma legible para humanos.
     */
    public function formattedFileSize(): string
    {
        $bytes = (int) $this->file_size_bytes;
        if ($bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 2, ',', '.') . ' ' . $units[$power];
    }

    /**
     * Formatea el precio comercial o devuelve la etiqueta de gratuidad.
     */
    public function formattedPrice(): string
    {
        if ($this->access_type !== self::ACCESS_PAID || $this->price_cents <= 0) {
            return 'Gratis';
        }

        $amount = number_format($this->price_cents / 100, 2, ',', '.');
        $symbol = match (strtoupper((string) $this->currency)) {
            'EUR'   => '€',
            'USD'   => '$',
            'GBP'   => '£',
            default => strtoupper((string) $this->currency),
        };

        return "{$amount} {$symbol}";
    }

    /**
     * Incrementa el contador de vistas mediante sentencia SQL atómica sin alterar `updated_at`
     * ni disparar observadores de regeneración de caché/sitemap.
     */
    public function incrementarVistas(): void
    {
        \Illuminate\Support\Facades\DB::table($this->getTable())
            ->where('id', $this->getKey())
            ->increment('views_count');
    }

    /**
     * Incrementa el contador de descargas mediante sentencia SQL atómica sin alterar `updated_at`
     * (preservando intacta la fecha real de última modificación editorial/técnica y `<lastmod>`).
     */
    public function incrementarDescargas(): void
    {
        \Illuminate\Support\Facades\DB::table($this->getTable())
            ->where('id', $this->getKey())
            ->increment('downloads_count');
    }

    /**
     * Diccionario de estados disponibles para selectores administrativos.
     *
     * @return array<string, string>
     */
    public static function estadosDisponibles(): array
    {
        return [
            self::STATUS_PUBLISHED => 'Publicado',
            self::STATUS_DRAFT     => 'Borrador',
        ];
    }

    /**
     * Diccionario de modalidades de acceso (preparado para evolución Free -> Paid).
     *
     * @return array<string, string>
     */
    public static function tiposAccesoDisponibles(): array
    {
        return [
            self::ACCESS_FREE       => 'Gratuito (Descarga libre)',
            self::ACCESS_REGISTERED => 'Usuarios registrados (Gratuito con cuenta)',
            self::ACCESS_PAID       => 'De pago / Licencia comercial (Preparado)',
        ];
    }
}

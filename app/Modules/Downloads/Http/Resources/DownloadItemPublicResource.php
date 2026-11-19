<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Resources;

use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource público optimizado para SSR y tarjetas del catálogo de Descargas.
 *
 * Filtra estrictamente cualquier dato interno y respeta `show_downloads_count_public`
 * para mantener las métricas de descargas privadas en el backend salvo configuración expresa.
 *
 * @mixin DownloadItem
 */
class DownloadItemPublicResource extends JsonResource
{
    public function __construct($resource, private readonly bool $includeFullDetail = false)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $settings = DownloadSetting::cached();
        $deliveryPrefix = trim((string) config('downloads.delivery_prefix', 'download-vault'), '/');
        $showCount = (bool) $settings->show_downloads_count_public;
        $user = $request->user();
        $adminRole = (string) config('downloads.admin_role', 'admin');
        $isAdmin = $user !== null && method_exists($user, 'hasRole') && $user->hasRole($adminRole);

        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'subtitle'             => $this->subtitle,
            'slug'                 => $this->slug,
            'short_description'    => $this->short_description,
            'body'                 => $this->when($this->includeFullDetail, fn () => $this->body),
            'featured_image'       => $this->featured_image,
            'icon'                 => $this->icon,
            'version'              => $this->version,
            'compatibility'        => $this->compatibility,
            'changelog'            => $this->when($this->includeFullDetail, fn () => $this->changelog),
            'demo_url'             => $this->demo_url,
            'documentation_url'    => $this->documentation_url,
            'has_file'             => $this->hasFile(),
            'formatted_file_size'  => $this->formattedFileSize(),
            'sha256_hash'          => $this->when($this->includeFullDetail, fn () => $this->sha256_hash),
            'access_type'          => $this->access_type,
            'is_free'              => $this->isFree(),
            'is_paid'              => $this->isPaid(),
            'price_cents'          => (int) $this->price_cents,
            'compare_at_price_cents' => $this->compare_at_price_cents !== null ? (int) $this->compare_at_price_cents : null,
            'currency'             => $this->currency ?: 'EUR',
            'formatted_price'      => $this->formattedPrice(),
            'license_type'         => $this->when($this->includeFullDetail, fn () => $this->license_type),
            'order'                => (int) $this->order,
            'is_featured'          => (bool) $this->is_featured,
            'status'               => $this->status,
            'published_at_iso'     => $this->published_at?->toIso8601String(),
            'published_at_human'   => $this->published_at?->translatedFormat('d M Y'),
            'updated_at_human'     => $this->updated_at?->translatedFormat('d M Y'),
            'downloads_count'      => $showCount ? (int) $this->downloads_count : null,
            'seo_title'            => $this->when($this->includeFullDetail, fn () => $this->seo_title),
            'seo_description'      => $this->when($this->includeFullDetail, fn () => $this->seo_description),
            'meta_robots'          => $this->when($this->includeFullDetail, fn () => $this->meta_robots),
            'url'                  => $settings->publicItemUrl($this->slug),
            'download_request_url' => $this->hasFile() ? url("/{$deliveryPrefix}/{$this->slug}/request") : null,
            'admin_edit_url'       => $this->when($this->includeFullDetail && $isAdmin, fn () => route('downloads.admin.edit', $this->id)),
        ];
    }
}

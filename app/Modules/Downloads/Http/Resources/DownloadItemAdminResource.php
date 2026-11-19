<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Resources;

use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource administrativo para ítems de descarga en el panel de control.
 *
 * Jamás expone `private_file_path` ni `vault_disk` al navegador; expone únicamente
 * metadatos auditables y la ruta segura iniciadora de descarga.
 *
 * @mixin DownloadItem
 */
class DownloadItemAdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $settings = DownloadSetting::cached();
        $deliveryPrefix = trim((string) config('downloads.delivery_prefix', 'download-vault'), '/');

        return [
            'id'                     => $this->id,
            'title'                  => $this->title,
            'subtitle'               => $this->subtitle,
            'slug'                   => $this->slug,
            'short_description'      => $this->short_description,
            'body'                   => $this->body,
            'featured_image'         => $this->featured_image,
            'icon'                   => $this->icon,
            'version'                => $this->version,
            'compatibility'          => $this->compatibility,
            'changelog'              => $this->changelog,
            'demo_url'               => $this->demo_url,
            'documentation_url'      => $this->documentation_url,
            'has_file'               => $this->hasFile(),
            'original_filename'      => $this->original_filename,
            'mime_type'              => $this->mime_type,
            'file_size_bytes'        => (int) $this->file_size_bytes,
            'formatted_file_size'    => $this->formattedFileSize(),
            'sha256_hash'            => $this->sha256_hash,
            'access_type'            => $this->access_type,
            'price_cents'            => (int) $this->price_cents,
            'compare_at_price_cents' => $this->compare_at_price_cents !== null ? (int) $this->compare_at_price_cents : null,
            'currency'               => $this->currency ?: 'EUR',
            'formatted_price'        => $this->formattedPrice(),
            'license_type'           => $this->license_type,
            'external_product_id'    => $this->external_product_id,
            'status'                 => $this->status,
            'is_published'           => $this->isPublished(),
            'published_at'           => $this->published_at?->format('Y-m-d\TH:i'),
            'published_at_human'     => $this->published_at?->format('d/m/Y H:i'),
            'order'                  => (int) $this->order,
            'is_featured'            => (bool) $this->is_featured,
            'downloads_count'        => (int) $this->downloads_count,
            'views_count'            => (int) $this->views_count,
            'author'                 => $this->whenLoaded('author', fn () => [
                'id'   => $this->author?->id,
                'name' => $this->author?->name,
            ]),
            'seo_title'              => $this->seo_title,
            'seo_description'        => $this->seo_description,
            'seo_og_image'           => $this->seo_og_image,
            'meta_robots'            => $this->meta_robots,
            'created_at_human'       => $this->created_at?->format('d/m/Y H:i'),
            'updated_at_human'       => $this->updated_at?->format('d/m/Y H:i'),
            'deleted_at_human'       => $this->deleted_at?->format('d/m/Y H:i'),
            'public_url'             => $settings->publicItemUrl($this->slug),
            'download_request_url'   => $this->hasFile() ? url("/{$deliveryPrefix}/{$this->slug}/request") : null,
        ];
    }
}

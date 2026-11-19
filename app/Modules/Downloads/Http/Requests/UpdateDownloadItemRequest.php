<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Requests;

use App\Modules\Downloads\Models\DownloadItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación estricta para actualizar un elemento descargable existente.
 */
class UpdateDownloadItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $adminRole = (string) config('downloads.admin_role', 'admin');

        return $user !== null && method_exists($user, 'hasRole') && $user->hasRole($adminRole);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var DownloadItem|null $downloadItem */
        $downloadItem = $this->route('downloadItem');
        $ignoreId = $downloadItem?->id;

        $maxUploadKb = (int) config('downloads.vault.max_upload_kb', 102400);
        $itemsTable = (string) config('downloads.tables.items', 'download_items');

        return [
            'title'                  => ['required', 'string', 'max:200'],
            'subtitle'               => ['nullable', 'string', 'max:255'],
            'slug'                   => ['nullable', 'string', 'max:200', Rule::unique($itemsTable, 'slug')->ignore($ignoreId)],
            'short_description'      => ['nullable', 'string', 'max:500'],
            'body'                   => ['nullable', 'string'],
            'featured_image'         => ['nullable', 'string', 'max:2048'],
            'icon'                   => ['nullable', 'string', 'max:100'],
            'version'                => ['nullable', 'string', 'max:50'],
            'compatibility'          => ['nullable', 'string', 'max:200'],
            'changelog'              => ['nullable', 'string', 'max:10000'],
            'demo_url'               => ['nullable', 'url:http,https', 'max:2048'],
            'documentation_url'      => ['nullable', 'url:http,https', 'max:2048'],
            'package_file'           => ['nullable', 'file', "max:{$maxUploadKb}"],
            'access_type'            => ['required', 'string', Rule::in(array_keys(DownloadItem::tiposAccesoDisponibles()))],
            'price_cents'            => ['nullable', 'integer', 'min:0', 'max:99999900'],
            'compare_at_price_cents' => ['nullable', 'integer', 'min:0', 'max:99999900'],
            'currency'               => ['nullable', 'string', 'size:3', 'alpha'],
            'license_type'           => ['nullable', 'string', 'max:120'],
            'external_product_id'    => ['nullable', 'string', 'max:191'],
            'status'                 => ['required', 'string', Rule::in(array_keys(DownloadItem::estadosDisponibles()))],
            'published_at'           => ['nullable', 'date'],
            'is_featured'            => ['boolean'],
            'seo_title'              => ['nullable', 'string', 'max:160'],
            'seo_description'        => ['nullable', 'string', 'max:320'],
            'seo_og_image'           => ['nullable', 'string', 'max:2048'],
            'meta_robots'            => ['nullable', 'string', 'max:60'],
        ];
    }
}

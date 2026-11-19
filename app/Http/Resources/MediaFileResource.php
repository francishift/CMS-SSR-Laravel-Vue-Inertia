<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource para serializar archivos multimedia al frontend.
 *
 * Filtra y formatea los datos del modelo MediaFile para su consumo
 * seguro desde los componentes Vue (biblioteca, modal selector, etc.).
 */
class MediaFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'filename'          => $this->filename,
            'original_filename' => $this->original_filename,
            'url'               => $this->url,
            'thumbnail_url'     => $this->thumbnail_url,
            'path'              => $this->path,
            'mime_type'         => $this->mime_type,
            'type'              => $this->type,
            'extension'         => strtoupper($this->extension),
            'size'              => $this->size,
            'formatted_size'    => $this->formatted_size,
            'width'             => $this->width,
            'height'            => $this->height,
            'dimensions'        => $this->width && $this->height
                ? "{$this->width} × {$this->height} px"
                : null,
            'alt_text'          => $this->alt_text,
            'title'             => $this->title,
            'collection'        => $this->collection,
            'is_image'          => $this->isImage(),
            'is_video'          => $this->isVideo(),
            'is_svg'            => $this->isSvg(),
            'uploaded_by'       => $this->whenLoaded('uploadedBy', fn () => [
                'id'   => $this->uploadedBy->id,
                'name' => $this->uploadedBy->name,
            ]),
            'created_at'        => $this->created_at?->format('d/m/Y H:i'),
            'created_at_human'  => $this->created_at?->diffForHumans(),
        ];
    }
}

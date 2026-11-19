<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo centralizado para archivos multimedia de la Biblioteca de Medios.
 *
 * Cada registro representa un archivo físico en disco (imagen o vídeo)
 * con sus metadatos, dimensiones y datos SEO asociados.
 */
class MediaFile extends Model
{
    protected $fillable = [
        'uuid',
        'filename',
        'original_filename',
        'disk',
        'path',
        'mime_type',
        'type',
        'extension',
        'size',
        'width',
        'height',
        'alt_text',
        'title',
        'collection',
        'uploaded_by',
    ];

    protected $casts = [
        'size'   => 'integer',
        'width'  => 'integer',
        'height' => 'integer',
    ];

    // ─── Relaciones ──────────────────────────────────────

    /**
     * Usuario que subió el archivo.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ─── Accessors ───────────────────────────────────────

    /**
     * URL pública completa del archivo original.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    /**
     * URL del thumbnail optimizado (WebP 300×300 cover) para la biblioteca.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if (!$this->isImage() || $this->isSvg()) {
            return $this->url;
        }

        return '/media/render?' . http_build_query([
            'src' => $this->path,
            'w'   => 300,
            'h'   => 300,
            'fit' => 'cover',
            'q'   => 80,
            'f'   => 'webp',
        ]);
    }

    /**
     * Tamaño formateado para lectura humana (ej: "2.4 MB").
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }

    // ─── Helpers de tipo ─────────────────────────────────

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isSvg(): bool
    {
        return $this->extension === 'svg';
    }

    // ─── Scopes ──────────────────────────────────────────

    /**
     * Filtrar solo imágenes.
     */
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    /**
     * Filtrar solo vídeos.
     */
    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    /**
     * Filtrar por colección/etiqueta.
     */
    public function scopeByCollection($query, ?string $collection)
    {
        if (!empty($collection)) {
            return $query->where('collection', $collection);
        }
        return $query;
    }

    /**
     * Búsqueda por nombre de archivo o título.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!empty($search)) {
            $term = '%' . $search . '%';
            return $query->where(function ($q) use ($term) {
                $q->where('filename', 'like', $term)
                  ->orWhere('original_filename', 'like', $term)
                  ->orWhere('title', 'like', $term)
                  ->orWhere('alt_text', 'like', $term);
            });
        }
        return $query;
    }

    // ─── Boot ────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        // Generar UUID automáticamente al crear
        static::creating(function (MediaFile $media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la Biblioteca de Elementos y Presets Guardados del Page Builder.
 */
class BuilderLibraryItem extends Model
{
    protected $table = 'builder_library_items';

    protected $fillable = [
        'name',
        'type',
        'category',
        'description',
        'content_json',
        'preview_image',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
        ];
    }

    /**
     * Usuario administrador creador del preset.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope para filtrar por tipo de elemento.
     */
    public function scopePorTipo($query, ?string $type)
    {
        if ($type && $type !== 'all') {
            return $query->where('type', $type);
        }
        return $query;
    }

    /**
     * Scope para búsqueda textual.
     */
    public function scopeBuscar($query, ?string $search)
    {
        if ($search && trim($search) !== '') {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        return $query;
    }
}

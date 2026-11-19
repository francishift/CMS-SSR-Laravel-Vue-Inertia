<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Página estática del CMS.
 *
 * Contenidos independientes como "Sobre nosotros", "Contacto",
 * "Política de privacidad", etc. Se acceden vía /{slug}.
 * Una página puede ser designada como portada del sitio (is_homepage).
 *
 * @property int $id
 * @property string $title
 * @property string $title_size
 * @property string $slug
 * @property ?string $body
 * @property bool $is_active
 * @property bool $is_homepage
 * @property int $order
 * @property ?string $seo_title
 * @property ?string $seo_description
 * @property ?string $seo_og_image
 * @property ?string $meta_robots
 */
class Page extends Model
{
    use HasFactory, SoftDeletes, \App\Models\Concerns\RecordsCmsActivity;

    public function activityLogChannel(): string { return 'contenido'; }
    public function activityEntityLabel(): string { return 'página'; }

    protected $fillable = [
        'title',
        'title_size',
        'slug',
        'body',
        'layout_json',
        'is_active',
        'is_homepage',
        'order',
        'seo_title',
        'seo_description',
        'seo_og_image',
        'meta_robots',
    ];

    protected $casts = [
        'layout_json' => 'array',
        'is_active'   => 'boolean',
        'is_homepage' => 'boolean',
        'order'       => 'integer',
    ];

    // ─── Scopes ─────────────────────────────────────────

    public function scopeActivas($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHomepage($query)
    {
        return $query->where('is_homepage', true);
    }

    // ─── Métodos estáticos ──────────────────────────────

    /**
     * Obtener la página configurada como portada del sitio.
     * Retorna null si no hay ninguna configurada.
     */
    public static function obtenerHomepage(): ?self
    {
        return static::activas()->homepage()->first();
    }

    // ─── Slug automático + Protección de homepage ───────

    protected static function booted(): void
    {
        // Normalizar y limpiar slug automáticamente al guardar
        static::saving(function (self $page) {
            if ($page->is_homepage) {
                $page->slug = '';
            } elseif (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            } else {
                $cleanSlug = trim($page->slug, " \t\n\r\0\x0B/");
                $page->slug = Str::slug($cleanSlug);
            }
        });

        // Impedir soft-delete de la homepage
        static::deleting(function (self $page) {
            if ($page->is_homepage) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    new \Illuminate\Http\JsonResponse([
                        'message' => 'No se puede eliminar la página de inicio. Asigna otra página como inicio primero.',
                    ], 422)
                );
            }
        });
    }
}

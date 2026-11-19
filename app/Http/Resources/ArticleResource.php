<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para artículos del CMS.
 *
 * En listados (home, categorías) devuelve una estructura liviana sin atributos de administración.
 * En la vista individual (detalle) entrega todos los metadatos y el cuerpo completo.
 */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $esDetalleOEdicion = $request->routeIs(
            'public.article',
            'public.article.single',
            'articulos.edit',
            'articulos.show',
            'plantillas.builder',
            'plantillas.builder.preview-frame',
            'paginas.builder',
            'paginas.builder.preview-frame'
        );

        $isFuture = in_array($this->status, ['published', 'scheduled']) && $this->published_at && $this->published_at->isFuture();
        $isExpired = in_array($this->status, ['published', 'scheduled']) && $this->published_until && $this->published_until->isPast();

        $effectiveStatus = $this->status === 'draft' ? 'draft' : ($isExpired ? 'expired' : ($isFuture ? 'scheduled' : 'published'));
        $statusLabel = $isExpired ? 'Finalizado' : config("cms.estados_articulo.{$effectiveStatus}", $effectiveStatus);

        $esAdminOBuilder = $request->is('panel*', '*builder*');

        // Si es un listado público (tarjetas de portada, categoría o búsqueda), devolver solo los campos de presentación
        if (!$esDetalleOEdicion && !$esAdminOBuilder) {
            return [
                'id'                  => $this->id,
                'title'               => $this->title,
                'slug'                => $this->slug,
                'excerpt'             => $this->excerpt,
                'featured_image'      => $this->featured_image,
                'icon'                => $this->icon,
                'status'              => $effectiveStatus,
                'published_at'        => $this->published_at?->format('d/m/Y H:i'),
                'published_at_iso'    => $this->published_at?->toIso8601String(),
                'published_until_iso' => $this->published_until?->toIso8601String(),
                'reading_time'        => $this->reading_time,
                'show_read_more'      => $this->show_read_more ?? true,
                'card_full_link'      => $this->card_full_link ?? true,
                'read_more_text'      => $this->read_more_text ?? 'Leer más',
                'extra_fields_data'   => $this->obtenerExtraFieldsDataFiltrados(),
                'category'            => $this->whenLoaded('category', fn () => [
                    'id'                => $this->category->id,
                    'name'              => $this->category->name,
                    'slug'              => $this->category->slug,
                    'show_author'       => $this->category->show_author ?? true,
                    'show_date'         => $this->category->show_date ?? true,
                    'show_reading_time' => $this->category->show_reading_time ?? true,
                ]),
                'author'              => $this->whenLoaded('author', fn () => [
                    'name' => $this->author->name,
                ]),
            ];
        }

        // Si es un listado del panel de administración, mantener los metadatos de gestión
        if (!$esDetalleOEdicion) {
            return [
                'id'                  => $this->id,
                'title'               => $this->title,
                'slug'                => $this->slug,
                'excerpt'             => $this->excerpt,
                'featured_image'      => $this->featured_image,
                'icon'                => $this->icon,
                'order'               => (int) $this->order,
                'status'              => $effectiveStatus,
                'raw_status'          => $this->status,
                'status_label'        => $statusLabel,
                'is_future'           => $isFuture,
                'is_expired'          => $isExpired,
                'published_at'        => $this->published_at?->format('d/m/Y H:i'),
                'published_at_iso'    => $this->published_at?->toIso8601String(),
                'published_until'     => $this->published_until?->format('d/m/Y H:i'),
                'published_until_iso' => $this->published_until?->toIso8601String(),
                'reading_time'        => $this->reading_time,
                'meta_robots'         => $this->meta_robots,
                'show_read_more'              => $this->show_read_more ?? true,
                'card_full_link'              => $this->card_full_link ?? true,
                'read_more_text'              => $this->read_more_text ?? 'Leer más',
                'article_builder_template_id' => $this->article_builder_template_id,
                'extra_fields_data'           => $this->obtenerExtraFieldsDataFiltrados(),
                'category'                    => $this->whenLoaded('category', fn () => [
                    'id'                           => $this->category->id,
                    'name'                         => $this->category->name,
                    'slug'                         => $this->category->slug,
                    'category_builder_template_id' => $this->category->category_builder_template_id,
                    'article_builder_template_id'  => $this->category->article_builder_template_id,
                    'extra_fields_schema'          => $this->category->extra_fields_schema,
                    'show_breadcrumb'              => $this->category->show_breadcrumb ?? true,
                    'show_pagination'              => $this->category->show_pagination ?? true,
                    'show_author'                  => $this->category->show_author ?? true,
                    'show_date'                    => $this->category->show_date ?? true,
                    'show_reading_time'            => $this->category->show_reading_time ?? true,
                    'show_category_link'           => $this->category->show_category_link ?? true,
                ]),
                'author'              => $this->whenLoaded('author', fn () => [
                    'name' => $this->author->name,
                ]),
            ];
        }

        $tieneLayoutResueltoPublico = !$esAdminOBuilder && !empty($this->layout_json['sections']);

        // Vista de detalle o edición: si en público ya lleva layout_json resuelto, evitar enviar una segunda copia íntegra de body
        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'slug'                => $this->slug,
            'excerpt'             => $this->excerpt,
            'body'                => $tieneLayoutResueltoPublico ? null : $this->body,
            'featured_image'      => $this->featured_image,
            'icon'                => $this->icon,
            'order'               => (int) $this->order,
            'category_id'                 => $this->category_id,
            'article_builder_template_id' => $this->article_builder_template_id,
            'layout_json'                 => $this->layout_json,
            'author_id'                   => $this->author_id,
            'status'                      => $effectiveStatus,
            'raw_status'          => $this->status,
            'status_label'        => $statusLabel,
            'is_future'           => $isFuture,
            'is_expired'          => $isExpired,
            'published_at'        => $this->published_at?->format('d/m/Y H:i'),
            'published_at_iso'    => $this->published_at?->toIso8601String(),
            'published_until'     => $this->published_until?->format('d/m/Y H:i'),
            'published_until_iso' => $this->published_until?->toIso8601String(),
            'show_read_more'      => $this->show_read_more ?? true,
            'card_full_link'      => $this->card_full_link ?? true,
            'read_more_text'      => $this->read_more_text ?? 'Leer más',
            'extra_fields_data'   => $this->obtenerExtraFieldsDataFiltrados(),
            'seo_title'           => $this->seo_title,
            'seo_description'     => $this->seo_description,
            'seo_og_image'        => $this->seo_og_image,
            'meta_robots'         => $this->meta_robots,
            'reading_time'        => $this->reading_time,
            'views_count'         => $this->views_count,
            'is_featured'         => $this->is_featured,
            'created_at'          => $this->created_at?->format('d/m/Y H:i'),
            'updated_at'          => $this->updated_at?->format('d/m/Y H:i'),
            'category'          => new CategoryResource($this->whenLoaded('category')),
            'author'            => $this->whenLoaded('author', fn () => [
                'id'   => $this->author->id,
                'name' => $this->author->name,
            ]),
        ];
    }

    /**
     * Filtra extra_fields_data para excluir claves que fueron eliminadas del esquema de la categoría.
     */
    protected function obtenerExtraFieldsDataFiltrados(): array
    {
        $extraData = $this->extra_fields_data;
        if (!is_array($extraData) || empty($extraData)) {
            return [];
        }

        if ($this->relationLoaded('category') && $this->category && is_array($this->category->extra_fields_schema)) {
            $allowedKeys = array_filter(array_column($this->category->extra_fields_schema, 'key'));
            if (!empty($allowedKeys)) {
                return array_intersect_key($extraData, array_flip($allowedKeys));
            }
            return [];
        }

        return $extraData;
    }
}

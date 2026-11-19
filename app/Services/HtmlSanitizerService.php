<?php

namespace App\Services;

/**
 * Servicio de Sanitización de HTML Anti-XSS.
 * 
 * Limpia y purifica el contenido HTML generado por editores WYSIWYG
 * para prevenir inyecciones de código malicioso (XSS), eliminando etiquetas
 * peligrosas (<script>,<iframe>,<object>,<embed>) y atributos inline de evento (onload, onerror, onclick, etc.).
 */
class HtmlSanitizerService
{
    /**
     * Lista de etiquetas HTML permitidas para contenidos del CMS.
     */
    private const ALLOWED_TAGS = '<p><h1><h2><h3><h4><h5><h6><strong><em><u><s><ul><ol><li><a><img><blockquote><pre><code><br><hr><span><div><table><thead><tbody><tr><th><td>';

    /**
     * Sanitizar una cadena HTML.
     */
    public function sanitize(?string $html): ?string
    {
        if (empty($html)) {
            return $html;
        }

        // 1. Filtrar etiquetas permitidas
        $cleanHtml = strip_tags($html, self::ALLOWED_TAGS);

        // 2. Eliminar eventos de javascript inline (on*=...)
        $cleanHtml = preg_replace('/(?:\b)on[a-z]+\s*=\s*(?:["\'][^"\']*["\']|[^\s>]+)/i', '', $cleanHtml);

        // 3. Eliminar esquemas de URL peligrosos en href o src (javascript:, data:text/html)
        $cleanHtml = preg_replace('/(href|src)\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', '$1="#"', $cleanHtml);
        $cleanHtml = preg_replace('/(href|src)\s*=\s*["\']?\s*data:text\/html[^"\'>\s]*/i', '$1="#"', $cleanHtml);

        return $cleanHtml;
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\MediaImageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controlador para la entrega de imágenes dinámicas y variantes en caché.
 *
 * Endpoint: GET /media/render?src=media/imagen.jpg&w=800&h=600&fit=cover&q=85&f=webp
 */
class ImageController extends Controller
{
    public function __construct(
        private readonly MediaImageService $imageService
    ) {}

    public function render(Request $request): BinaryFileResponse
    {
        $src = $request->input('src');

        if (!$src || !is_string($src)) {
            abort(404, 'Imagen no especificada');
        }

        $options = [
            'w'   => $request->input('w'),
            'h'   => $request->input('h'),
            'fit' => $request->input('fit', 'scale'),
            'q'   => $request->input('q', 85),
            'f'   => $request->input('f', 'webp'),
        ];

        $variant = $this->imageService->getOrGenerateVariant($src, $options);

        if (!$variant || !file_exists($variant['path'])) {
            abort(404, 'Imagen no encontrada');
        }

        $response = new BinaryFileResponse($variant['path']);
        $response->headers->set('Content-Type', $variant['mime']);
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        $response->headers->set('X-Image-Cache', $variant['hit'] ? 'HIT (Disk Cache)' : 'MISS (Generated)');

        return $response;
    }
}

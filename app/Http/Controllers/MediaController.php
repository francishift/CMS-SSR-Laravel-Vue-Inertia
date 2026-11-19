<?php

namespace App\Http\Controllers;

use App\Http\Resources\MediaFileResource;
use App\Models\MediaFile;
use App\Services\MediaImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Controlador para la Biblioteca de Medios del CMS.
 *
 * Gestiona la subida, listado, edición y eliminación de archivos multimedia
 * (imágenes y vídeos) con soporte para nombres SEO-friendly, optimización
 * automática y selección desde modal picker reutilizable.
 */
class MediaController extends Controller
{
    public function __construct(
        private readonly MediaImageService $imageService
    ) {}

    /**
     * Página principal de la Biblioteca de Medios (vista Inertia).
     * Soporta búsqueda, filtrado por tipo y colección, paginación eficiente.
     */
    public function index(Request $request)
    {
        $query = MediaFile::with('uploadedBy')
            ->search($request->input('search'))
            ->byCollection($request->input('collection'))
            ->orderByDesc('created_at');

        // Filtrar por tipo (image/video)
        if ($request->filled('type') && in_array($request->input('type'), ['image', 'video'])) {
            $query->where('type', $request->input('type'));
        }

        $medios = $query->paginate(30)->withQueryString();

        // Obtener colecciones únicas para el filtro de la UI (descartando nulos y vacíos)
        $colecciones = MediaFile::whereNotNull('collection')
            ->where('collection', '!=', '')
            ->distinct()
            ->pluck('collection')
            ->sort()
            ->values();

        return Inertia::render('Media/Index', [
            'medios'       => MediaFileResource::collection($medios),
            'colecciones'  => $colecciones,
            'filtros'      => $request->only(['search', 'type', 'collection']),
            'totalMedios'  => MediaFile::count(),
            'totalImagenes'=> MediaFile::images()->count(),
            'totalVideos'  => MediaFile::videos()->count(),
        ]);
    }

    /**
     * API JSON para el modal selector MediaPickerModal.
     * Devuelve listado paginado sin renderizar vista Inertia.
     */
    public function list(Request $request): JsonResponse
    {
        $query = MediaFile::query()
            ->search($request->input('search'))
            ->byCollection($request->input('collection'))
            ->orderByDesc('created_at');

        if ($request->filled('type') && in_array($request->input('type'), ['image', 'video'])) {
            $query->where('type', $request->input('type'));
        }

        $medios = $query->paginate(40);

        $colecciones = MediaFile::whereNotNull('collection')
            ->where('collection', '!=', '')
            ->distinct()
            ->pluck('collection')
            ->sort()
            ->values();

        return response()->json([
            'success'     => true,
            'items'       => MediaFileResource::collection($medios),
            'colecciones' => $colecciones,
            'meta'        => [
                'current_page' => $medios->currentPage(),
                'last_page'    => $medios->lastPage(),
                'total'        => $medios->total(),
            ],
        ]);
    }

    /**
     * Subir un archivo multimedia (imagen o vídeo) a la biblioteca.
     * Registra en BBDD, genera nombre SEO y pre-genera variantes para imágenes.
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $isVideo = false;
            $fieldName = 'image';

            // Detectar si se envía como 'video' o como 'image'
            if ($request->hasFile('video')) {
                $fieldName = 'video';
                $isVideo = true;
            } elseif ($request->hasFile('file')) {
                $fieldName = 'file';
                // Detectar tipo por MIME
                $fileMime = $request->file('file')->getMimeType();
                $isVideo = str_starts_with($fileMime, 'video/');
            }

            if (!$request->hasFile($fieldName) || !$request->file($fieldName)->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo seleccionado supera el límite máximo permitido por el servidor o el archivo está corrupto.',
                ], 422);
            }

            // Validación diferenciada por tipo
            if ($isVideo) {
                $request->validate([
                    $fieldName => [
                        'required', 'file',
                        'mimes:mp4,webm,mov',
                        'mimetypes:video/mp4,video/webm,video/quicktime',
                        'max:102400', // 100 MB
                    ],
                ], [
                    "$fieldName.required" => 'Debes seleccionar un archivo de vídeo.',
                    "$fieldName.mimes"    => 'Formato de vídeo no soportado. Formatos permitidos: MP4, WebM, MOV.',
                    "$fieldName.max"      => 'El vídeo es demasiado pesado. El tamaño máximo permitido es 100 MB.',
                ]);
            } else {
                $request->validate([
                    $fieldName => [
                        'required', 'file',
                        'mimes:jpeg,png,jpg,webp,avif,gif,svg,xml,ico',
                        'mimetypes:image/jpeg,image/png,image/jpg,image/webp,image/avif,image/gif,image/svg+xml,text/xml,application/xml,text/plain,image/x-icon,image/vnd.microsoft.icon,image/ico',
                        'max:30720', // 30 MB
                    ],
                ], [
                    "$fieldName.required" => 'Debes seleccionar un archivo de imagen.',
                    "$fieldName.file"     => 'El archivo enviado no es válido.',
                    "$fieldName.mimes"    => 'Formato de imagen no soportado. Formatos permitidos: JPG, PNG, WebP, AVIF, GIF, SVG, ICO.',
                    "$fieldName.max"      => 'La imagen es demasiado pesada. El tamaño máximo permitido es 30 MB.',
                ]);
            }

            $file = $request->file($fieldName);

            // Sanitizar SVGs para prevenir XSS
            if (str_contains(strtolower($file->getMimeType() ?: ''), 'svg') || strtolower($file->getClientOriginalExtension()) === 'svg') {
                $this->sanitizarSvg($file);
            }

            // Guardar archivo y registrar en BBDD
            $collection = $request->input('collection');
            $mediaFile = $this->imageService->storeMaster($file, 'media', $collection);

            if (!$mediaFile) {
                throw new \RuntimeException('No se pudo guardar el archivo en el disco público.');
            }

            // Pre-generar miniaturas WebP para imágenes (no vídeos ni SVGs)
            if ($mediaFile->isImage() && !$mediaFile->isSvg()) {
                $this->imageService->prewarmVariants($mediaFile->path);
            }

            return response()->json([
                'success' => true,
                'url'     => $mediaFile->url,
                'path'    => $mediaFile->path,
                'media'   => new MediaFileResource($mediaFile),
            ]);
        } catch (ValidationException $e) {
            $mensaje = collect($e->errors())->flatten()->first() ?? 'El archivo enviado no es válido.';

            return response()->json([
                'success' => false,
                'message' => $mensaje,
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error en MediaController@upload: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el archivo en el servidor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar metadatos de un archivo multimedia (alt_text, title, collection).
     * Si se recibe 'filename', renombra el archivo físico en disco y en caché.
     */
    public function update(Request $request, MediaFile $mediaFile): JsonResponse
    {
        $validated = $request->validate([
            'alt_text'   => ['nullable', 'string', 'max:255'],
            'title'      => ['nullable', 'string', 'max:255'],
            'collection' => ['nullable', 'string', 'max:100'],
            'filename'   => ['nullable', 'string', 'max:255'],
        ]);

        // ─── Renombrado de archivo ───────────────────────────
        if (!empty($validated['filename']) && $validated['filename'] !== $mediaFile->filename) {
            $nuevoNombre = $this->procesarRenombrado($mediaFile, $validated['filename']);
            if ($nuevoNombre === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo renombrar el archivo. Verifica que el nombre sea válido.',
                ], 422);
            }
            // El renombrado ya actualizó el modelo, así que quitamos filename del array
            unset($validated['filename']);
        } else {
            unset($validated['filename']);
        }

        // Actualizar resto de metadatos
        if (array_key_exists('collection', $validated)) {
            $validated['collection'] = !empty(trim((string) $validated['collection'])) ? trim((string) $validated['collection']) : null;
        }

        if (!empty($validated)) {
            $mediaFile->update($validated);
        }

        $colecciones = MediaFile::whereNotNull('collection')
            ->where('collection', '!=', '')
            ->distinct()
            ->pluck('collection')
            ->sort()
            ->values();

        return response()->json([
            'success'     => true,
            'media'       => new MediaFileResource($mediaFile->fresh()),
            'colecciones' => $colecciones,
        ]);
    }

    /**
     * Procesar el renombrado de un archivo multimedia.
     *
     * Sanitiza el nuevo nombre (Str::slug), preserva la extensión,
     * renombra el archivo físico en disco y actualiza variantes cacheadas.
     *
     * @return string|false Nuevo nombre del archivo o false si falla
     */
    private function procesarRenombrado(MediaFile $mediaFile, string $nuevoNombreRaw): string|false
    {
        $ext = $mediaFile->extension;
        $dirPath = dirname($mediaFile->path);

        // Sanitizar el nuevo nombre: solo la parte sin extensión
        $nombreSinExt = pathinfo($nuevoNombreRaw, PATHINFO_FILENAME);
        $nombreSlug = Str::slug($nombreSinExt);

        if (empty($nombreSlug)) {
            return false;
        }

        $nuevoFilename = $nombreSlug . '.' . $ext;
        $nuevoPath = $dirPath . '/' . $nuevoFilename;

        // Evitar colisión con archivo existente
        $contador = 1;
        while (Storage::disk($mediaFile->disk)->exists($nuevoPath) && $nuevoPath !== $mediaFile->path) {
            $nuevoFilename = $nombreSlug . '-' . $contador . '.' . $ext;
            $nuevoPath = $dirPath . '/' . $nuevoFilename;
            $contador++;
        }

        // Si no hay cambio real, salir
        if ($nuevoPath === $mediaFile->path) {
            return $nuevoFilename;
        }

        // Renombrar archivo físico en disco
        try {
            Storage::disk($mediaFile->disk)->move($mediaFile->path, $nuevoPath);
        } catch (\Throwable $e) {
            Log::error('Error al renombrar archivo multimedia: ' . $e->getMessage(), [
                'original' => $mediaFile->path,
                'nuevo'    => $nuevoPath,
            ]);
            return false;
        }

        // Renombrar variantes cacheadas (cache/media/*)
        $oldBasename = pathinfo($mediaFile->filename, PATHINFO_FILENAME);
        $newBasename = pathinfo($nuevoFilename, PATHINFO_FILENAME);
        $cacheFiles = Storage::disk($mediaFile->disk)->files('cache/media');

        foreach ($cacheFiles as $cacheFile) {
            $cacheBasename = basename($cacheFile);
            if (str_starts_with($cacheBasename, $oldBasename . '_') || str_starts_with($cacheBasename, $oldBasename . '.')) {
                $nuevoCacheName = str_replace($oldBasename, $newBasename, $cacheBasename);
                $nuevoCachePath = 'cache/media/' . $nuevoCacheName;
                try {
                    Storage::disk($mediaFile->disk)->move($cacheFile, $nuevoCachePath);
                } catch (\Throwable $e) {
                    // No bloquear si falla alguna variante de caché
                    Log::warning('No se pudo renombrar variante de caché: ' . $cacheFile);
                }
            }
        }

        // Actualizar registro en BBDD
        $mediaFile->update([
            'filename' => $nuevoFilename,
            'path'     => $nuevoPath,
        ]);

        return $nuevoFilename;
    }

    /**
     * Eliminar un archivo multimedia de la BBDD y del disco.
     */
    public function destroy(MediaFile $mediaFile): JsonResponse
    {
        // Eliminar archivo original de disco
        if (Storage::disk($mediaFile->disk)->exists($mediaFile->path)) {
            Storage::disk($mediaFile->disk)->delete($mediaFile->path);
        }

        // Eliminar variantes en caché asociadas
        $nameWithoutExt = pathinfo($mediaFile->filename, PATHINFO_FILENAME);
        $cacheFiles = Storage::disk('public')->files('cache/media');
        foreach ($cacheFiles as $cacheFile) {
            if (str_contains($cacheFile, $nameWithoutExt)) {
                Storage::disk('public')->delete($cacheFile);
            }
        }

        $mediaFile->delete();

        $colecciones = MediaFile::whereNotNull('collection')
            ->where('collection', '!=', '')
            ->distinct()
            ->pluck('collection')
            ->sort()
            ->values();

        return response()->json([
            'success'     => true,
            'message'     => 'Archivo eliminado correctamente.',
            'colecciones' => $colecciones,
        ]);
    }

    /**
     * Eliminación masiva de archivos multimedia.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media_files,id'],
        ]);

        $archivos = MediaFile::whereIn('id', $request->input('ids'))->get();
        $eliminados = 0;

        foreach ($archivos as $mediaFile) {
            // Eliminar de disco
            if (Storage::disk($mediaFile->disk)->exists($mediaFile->path)) {
                Storage::disk($mediaFile->disk)->delete($mediaFile->path);
            }

            // Eliminar caché
            $nameWithoutExt = pathinfo($mediaFile->filename, PATHINFO_FILENAME);
            $cacheFiles = Storage::disk('public')->files('cache/media');
            foreach ($cacheFiles as $cacheFile) {
                if (str_contains($cacheFile, $nameWithoutExt)) {
                    Storage::disk('public')->delete($cacheFile);
                }
            }

            $mediaFile->delete();
            $eliminados++;
        }

        $colecciones = MediaFile::whereNotNull('collection')
            ->where('collection', '!=', '')
            ->distinct()
            ->pluck('collection')
            ->sort()
            ->values();

        return response()->json([
            'success'     => true,
            'message'     => "Se eliminaron {$eliminados} archivos correctamente.",
            'eliminados'  => $eliminados,
            'colecciones' => $colecciones,
        ]);
    }

    /**
     * Sanitizar archivos SVG para prevenir ataques XSS.
     * Elimina scripts, event handlers y elementos peligrosos.
     */
    private function sanitizarSvg(mixed $file): void
    {
        $contenido = file_get_contents($file->getRealPath());

        // Eliminar etiquetas <script>, <foreignObject>, <use> con href externo
        $contenido = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $contenido);
        $contenido = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $contenido);

        // Eliminar atributos de eventos (onload, onclick, onerror, etc.)
        $contenido = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $contenido);
        $contenido = preg_replace('/\s+on\w+\s*=\s*[^\s>]*/i', '', $contenido);

        // Eliminar javascript: en atributos href/xlink:href
        $contenido = preg_replace('/(?:href|xlink:href)\s*=\s*["\']javascript:[^"\']*["\']/i', '', $contenido);

        // Escribir el archivo sanitizado de vuelta
        file_put_contents($file->getRealPath(), $contenido);
    }
}

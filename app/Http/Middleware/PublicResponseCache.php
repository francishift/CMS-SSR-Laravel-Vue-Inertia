<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para la memoria caché de respuestas de páginas públicas (Redis Full Response Cache).
 *
 * Intercepta peticiones públicas anónimas (GET/HEAD) y las sirve directamente desde la memoria RAM
 * de Redis en < 0.5ms sin consultar la base de datos MySQL ni ejecutar el motor SSR de Node.js.
 *
 * Reglas de exclusión:
 *   - No se aplica a usuarios autenticados (Panel / Admins).
 *   - No se aplica a peticiones POST, PUT, DELETE, PATCH.
 *   - No se aplica a rutas privadas (panel*, login*, logout*, perfil*, etc.).
 *   - Se invalida automáticamente en Redis cuando se crea/edita/elimina cualquier contenido en el CMS.
 */
class PublicResponseCache
{
    /**
     * Clave de etiqueta en Redis para la caché pública de páginas.
     */
    public const CACHE_TAG = 'public_pages';

    /**
     * Duración en segundos de la caché en Redis (24 horas).
     */
    public const TTL_SECONDS = 86400;

    /**
     * Rutas o prefijos excluidos de la caché.
     */
    private const EXCLUDED_PREFIXES = [
        'panel',
        'acceso',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'confirm-password',
        'password',
        'verify-email',
        'auth',
        'oauth',
        'perfil',
        'media',
        'up',
        'sanctum',
        'livewire',
        'whatsapp',
        'llamar',
        'telefono',
        'email',
        'mail',
        'download-vault',
    ];

    /**
     * Prefijos de parámetros de seguimiento publicitario/analítico ignorados en la clave de caché.
     */
    private const TRACKING_QUERY_PREFIXES = [
        'utm_',
        'hsa_',
    ];

    /**
     * Parámetros exactos de clic/campaña de plataformas externas ignorados en la clave de caché.
     */
    private const TRACKING_QUERY_PARAMS = [
        'fbclid',
        'gclid',
        'dclid',
        'gbraid',
        'wbraid',
        'msclkid',
        'twclid',
        'ttclid',
        'li_fat_id',
        'mc_cid',
        'mc_eid',
        'yclid',
        'igshid',
        'mkt_tok',
        'srsltid',
        '_gl',
        '_ga',
        '_gac',
        '_hsenc',
        '_hsmi',
        'ref_src',
        'ref_url',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Verificar si la petición es elegible para caché
        if (!$this->esElegibleParaCache($request)) {
            return $next($request);
        }

        // 2. Generar clave única normalizando parámetros de campañas (utm_*, fbclid, gclid, etc.)
        $isInertia = $request->header('X-Inertia') ? 'inertia' : 'html';
        $normalizedUrl = $this->normalizarUrlParaCache($request);
        $cacheKey = 'pub_resp:' . md5($normalizedUrl . '_' . $isInertia . '_' . $request->getScheme());

        // 3. Intentar obtener la respuesta desde Redis
        try {
            $cachedData = Cache::store('redis')->tags([self::CACHE_TAG])->get($cacheKey);

            if ($cachedData && is_array($cachedData)) {
                $response = response($cachedData['content'], $cachedData['status']);

                if (!empty($cachedData['content_type'])) {
                    $response->headers->set('Content-Type', $cachedData['content_type']);
                }

                if (!empty($cachedData['is_inertia'])) {
                    $response->headers->set('X-Inertia', 'true');
                }

                $response->headers->set('X-Response-Cache', 'HIT (Redis RAM)');

                return $response;
            }
        } catch (\Throwable $e) {
            // Si Redis falla por cualquier motivo, continuar normalmente sin interrumpir la web
        }

        // 4. Limpiar parámetros efímeros de tracking antes de renderizar en MISS para no contaminar data-page en Redis
        $this->limpiarQueryTrackingEnRequest($request);

        // 5. Si no está en caché (MISS), procesar la petición normalmente
        /** @var Response $response */
        $response = $next($request);

        // 5. Guardar en Redis si la respuesta fue exitosa (HTTP 200 OK)
        if ($response->getStatusCode() === 200 && !empty($response->getContent())) {
            try {
                $ttl = $this->calcularTtl();

                Cache::store('redis')->tags([self::CACHE_TAG])->put($cacheKey, [
                    'content'      => $response->getContent(),
                    'status'       => $response->getStatusCode(),
                    'content_type' => $response->headers->get('Content-Type'),
                    'is_inertia'   => $response->headers->has('X-Inertia'),
                ], $ttl);

                $response->headers->set('X-Response-Cache', 'MISS (Stored in Redis)');
            } catch (\Throwable $e) {
                // Silenciar errores de escritura en caché para no romper la petición
            }
        }

        return $response;
    }

    /**
     * Calcula el TTL óptimo en segundos.
     * Si existen artículos con fecha de publicación o expiración próxima,
     * reduce el TTL al instante exacto del próximo evento para garantizar despublicación/publicación inmediata.
     */
    private function calcularTtl(): int
    {
        $ahora = now();

        $proxPub = \App\Models\Article::where('status', 'scheduled')
            ->where('published_at', '>', $ahora)
            ->min('published_at');

        $proxExp = \App\Models\Article::whereNotNull('published_until')
            ->where('published_until', '>', $ahora)
            ->min('published_until');

        $candidatos = [];
        if ($proxPub) {
            $diffPub = $ahora->diffInSeconds(\Carbon\Carbon::parse($proxPub), false);
            if ($diffPub > 0) {
                $candidatos[] = $diffPub;
            }
        }
        if ($proxExp) {
            $diffExp = $ahora->diffInSeconds(\Carbon\Carbon::parse($proxExp), false);
            if ($diffExp > 0) {
                $candidatos[] = $diffExp;
            }
        }

        if (!empty($candidatos)) {
            $minDiff = min($candidatos);
            return max(1, min(self::TTL_SECONDS, (int) $minDiff));
        }

        return self::TTL_SECONDS;
    }

    /**
     * Comprobar si la petición cumple todos los requisitos para ser almacenada en caché.
     */
    private function esElegibleParaCache(Request $request): bool
    {
        // Solo peticiones de lectura (GET o HEAD)
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return false;
        }

        // Excluir usuarios autenticados
        if (Auth::check()) {
            return false;
        }

        // Excluir peticiones parciales de Inertia (Inertia::defer / lazy loading)
        if ($request->hasHeader('X-Inertia-Partial-Data') || $request->hasHeader('X-Inertia-Partial-Component')) {
            return false;
        }

        // Excluir rutas del panel, autenticación y endpoints dinámicos de entrega binaria modular
        $path = ltrim($request->getPathInfo(), '/');
        $excludedPrefixes = self::EXCLUDED_PREFIXES;
        $deliveryPrefix = trim((string) config('downloads.delivery_prefix', 'download-vault'), '/');
        if ($deliveryPrefix !== '') {
            $excludedPrefixes[] = $deliveryPrefix;
        }

        foreach ($excludedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Construye una URL canónica para la clave de caché descartando parámetros efímeros de campañas
     * (utm_*, fbclid, gclid, etc.) y ordenando alfabéticamente los parámetros funcionales (ej. ?q=...).
     */
    public function normalizarUrlParaCache(Request $request): string
    {
        $baseUrl = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo(), '/');
        if ($baseUrl === $request->getSchemeAndHttpHost()) {
            $baseUrl .= '/';
        }

        $query = $this->filtrarParametrosFuncionales($request->query->all());

        if (empty($query)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Elimina parámetros efímeros de seguimiento del Request actual antes de generar una respuesta en MISS,
     * garantizando que el objeto initialPage.url almacenado en Redis permanezca limpio y libre de IDs de clic ajenos.
     */
    private function limpiarQueryTrackingEnRequest(Request $request): void
    {
        $original = $request->query->all();
        if (empty($original)) {
            return;
        }

        $limpios = $this->filtrarParametrosFuncionales($original);
        if (count($limpios) === count($original)) {
            return;
        }

        $request->query->replace($limpios);

        $queryString = empty($limpios) ? '' : http_build_query($limpios, '', '&', PHP_QUERY_RFC3986);
        $path = $request->getBaseUrl() . $request->getPathInfo();

        $request->server->set('QUERY_STRING', $queryString);
        $request->server->set('REQUEST_URI', $queryString !== '' ? "{$path}?{$queryString}" : $path);
    }

    /**
     * Filtra recursivamente parámetros de seguimiento y ordena las claves restantes.
     *
     * @param  array<string|int, mixed>  $params
     * @return array<string|int, mixed>
     */
    private function filtrarParametrosFuncionales(array $params): array
    {
        $filtrados = [];

        foreach ($params as $key => $value) {
            if (is_string($key) && $this->esParametroTracking($key)) {
                continue;
            }

            $filtrados[$key] = is_array($value) ? $this->filtrarParametrosFuncionales($value) : $value;
        }

        ksort($filtrados);

        return $filtrados;
    }

    /**
     * Determina si un parámetro query pertenece a seguimiento analítico o campañas publicitarias.
     */
    private function esParametroTracking(string $key): bool
    {
        $lower = strtolower(trim($key));

        if (in_array($lower, self::TRACKING_QUERY_PARAMS, true)) {
            return true;
        }

        foreach (self::TRACKING_QUERY_PREFIXES as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }

        return false;
    }
}

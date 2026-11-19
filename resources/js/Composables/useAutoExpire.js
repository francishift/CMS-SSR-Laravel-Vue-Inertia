import { ref, onMounted, onBeforeUnmount, watch } from 'vue';

/**
 * Composable profesional para la detección y sincronización en tiempo real
 * del vencimiento de publicaciones sin sobrecargar el servidor (Zero Polling).
 *
 * Utiliza temporizadores exactos (setTimeout) calculados al milisegundo
 * y se sincroniza con el evento Page Visibility API (al reactivar la pestaña o pantalla).
 */

/**
 * Gestiona el vencimiento de un único elemento (ej: Detalle de Artículo).
 *
 * @param {Function|import('vue').Ref<string|null>} dateGetter Función o ref que retorna la fecha ISO de fin (published_until_iso)
 * @param {Function} [onExpireCallback] Callback a ejecutar cuando el elemento caduca
 */
export function useSingleItemExpire(dateGetter, onExpireCallback) {
    const isExpired = ref(false);
    let timerId = null;

    function limpiarTemporizador() {
        if (timerId) {
            clearTimeout(timerId);
            timerId = null;
        }
    }

    function evaluarExpiracion() {
        limpiarTemporizador();

        const rawDate = typeof dateGetter === 'function' ? dateGetter() : dateGetter?.value || dateGetter;
        if (!rawDate) {
            isExpired.value = false;
            return;
        }

        const expireTime = new Date(rawDate).getTime();
        if (isNaN(expireTime)) {
            isExpired.value = false;
            return;
        }

        const now = Date.now();
        const diffMs = expireTime - now;

        if (diffMs <= 0) {
            isExpired.value = true;
            if (typeof onExpireCallback === 'function') {
                onExpireCallback();
            }
        } else {
            isExpired.value = false;
            // Configurar temporizador exacto hasta el segundo de vencimiento
            // Limitar a máximo 2^31-1 ms (24.8 días) soportado por setTimeout
            const delay = Math.min(diffMs + 500, 2147483647);
            timerId = setTimeout(() => {
                isExpired.value = true;
                if (typeof onExpireCallback === 'function') {
                    onExpireCallback();
                }
            }, delay);
        }
    }

    function handleVisibilityChange() {
        if (typeof document !== 'undefined' && document.visibilityState === 'visible') {
            evaluarExpiracion();
        }
    }

    onMounted(() => {
        evaluarExpiracion();
        if (typeof document !== 'undefined') {
            document.addEventListener('visibilitychange', handleVisibilityChange);
        }
    });

    onBeforeUnmount(() => {
        limpiarTemporizador();
        if (typeof document !== 'undefined') {
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        }
    });

    if (typeof dateGetter === 'function') {
        watch(dateGetter, () => {
            evaluarExpiracion();
        });
    }

    return {
        isExpired,
        evaluarExpiracion,
    };
}

/**
 * Gestiona el vencimiento en listados/grids de elementos (ej: Categorías, Portada).
 * Encuentra el elemento que vence más pronto y programa la recarga automática del listado.
 *
 * @param {Function|import('vue').Ref<Array>} itemsGetter Función que retorna el array de elementos actuales
 * @param {Function} onExpireCallback Callback ejecutado al vencer el elemento más próximo (ej: router.reload)
 */
export function useListAutoExpire(itemsGetter, onExpireCallback) {
    let timerId = null;

    function limpiarTemporizador() {
        if (timerId) {
            clearTimeout(timerId);
            timerId = null;
        }
    }

    function programarSiguienteVencimiento() {
        limpiarTemporizador();

        const items = typeof itemsGetter === 'function' ? itemsGetter() : itemsGetter?.value || itemsGetter;
        if (!Array.isArray(items) || items.length === 0) {
            return;
        }

        const now = Date.now();
        let minDiff = Infinity;

        items.forEach(item => {
            // Evaluar tanto published_until como published_at (programados)
            const until = item.published_until_iso ? new Date(item.published_until_iso).getTime() : null;
            const at = item.published_at_iso ? new Date(item.published_at_iso).getTime() : null;

            if (until && !isNaN(until) && until > now) {
                const diff = until - now;
                if (diff < minDiff) minDiff = diff;
            }

            if (at && !isNaN(at) && at > now) {
                const diff = at - now;
                if (diff < minDiff) minDiff = diff;
            }
        });

        if (minDiff < Infinity && minDiff > 0) {
            // Añadir un pequeño margen de 800ms para asegurar que el backend ya haya cruzado el segundo
            const delay = Math.min(minDiff + 800, 2147483647);
            timerId = setTimeout(() => {
                if (typeof onExpireCallback === 'function') {
                    onExpireCallback();
                }
            }, delay);
        }
    }

    function handleVisibilityChange() {
        if (typeof document !== 'undefined' && document.visibilityState === 'visible') {
            programarSiguienteVencimiento();
        }
    }

    onMounted(() => {
        programarSiguienteVencimiento();
        if (typeof document !== 'undefined') {
            document.addEventListener('visibilitychange', handleVisibilityChange);
        }
    });

    onBeforeUnmount(() => {
        limpiarTemporizador();
        if (typeof document !== 'undefined') {
            document.removeEventListener('visibilitychange', handleVisibilityChange);
        }
    });

    if (typeof itemsGetter === 'function') {
        watch(itemsGetter, () => {
            programarSiguienteVencimiento();
        }, { deep: true });
    }

    return {
        programarSiguienteVencimiento,
    };
}

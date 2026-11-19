/**
 * Motor de Animación al Scroll Profesional (Zero CPU Overhead).
 *
 * Utiliza una única instancia compartida de IntersectionObserver para todo el DOM.
 * Al entrar un elemento en el viewport, se activa y se desuscribe inmediatamente,
 * garantizando 0% de uso de CPU y 60fps constantes.
 *
 * Soporta cálculo jerárquico de retardos (sección + elemento) y previsualización en vivo en el maquetador.
 */

let sharedObserver = null;

function getSharedObserver() {
    if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
        return null;
    }

    if (!sharedObserver) {
        sharedObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const delay = el.getAttribute('data-cms-delay');
                    if (delay !== null && delay !== undefined && Number(delay) >= 0) {
                        el.style.setProperty('--cms-anim-delay', `${Number(delay)}ms`);
                    }
                    el.classList.add('cms-animated');
                    sharedObserver.unobserve(el);
                }
            });
        }, {
            threshold: 0.05,
            rootMargin: '0px 0px -20px 0px',
        });
    }

    return sharedObserver;
}

export function initScrollAnimations(container = null) {
    if (typeof window === 'undefined' || typeof document === 'undefined') return;

    const root = container || document;

    // Respetar preferencia del usuario de reducción de movimiento (Accesibilidad WCAG)
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        root.querySelectorAll('[data-cms-animate]').forEach((el) => {
            el.classList.add('cms-animated');
        });
        return;
    }

    const observer = getSharedObserver();
    if (!observer) {
        root.querySelectorAll('[data-cms-animate]').forEach((el) => {
            el.classList.add('cms-animated');
        });
        return;
    }

    const elements = root.querySelectorAll('[data-cms-animate]:not(.cms-animated)');
    elements.forEach((el) => {
        const delay = el.getAttribute('data-cms-delay');
        if (delay !== null && delay !== undefined && !isNaN(Number(delay)) && Number(delay) >= 0) {
            el.style.setProperty('--cms-anim-delay', `${Number(delay)}ms`);
        } else {
            el.style.setProperty('--cms-anim-delay', '0ms');
        }

        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            // Se encuentra visible en la carga inicial
            requestAnimationFrame(() => {
                el.classList.add('cms-animated');
            });
        } else {
            observer.observe(el);
        }
    });
}

/**
 * Desconecta del observador los elementos de un contenedor que se desmonta del DOM,
 * evitando fugas de memoria y retención de nodos huérfanos.
 */
export function cleanupScrollAnimations(container = null) {
    if (typeof window === 'undefined' || !sharedObserver) return;
    const root = container || document;
    try {
        const elements = root.querySelectorAll('[data-cms-animate]');
        elements.forEach((el) => {
            sharedObserver.unobserve(el);
        });
    } catch {
        // Contenedor ya desmontado
    }
}

export function replayAnimation(targetElement) {
    if (!targetElement) return;

    const delay = targetElement.getAttribute('data-cms-delay');
    if (delay !== null && delay !== undefined && !isNaN(Number(delay)) && Number(delay) >= 0) {
        targetElement.style.setProperty('--cms-anim-delay', `${Number(delay)}ms`);
    } else {
        targetElement.style.setProperty('--cms-anim-delay', '0ms');
    }

    // 1. Quitar clase cms-animated (vuelve a opacity: 0 y desplazamiento en 0ms gracias a transition: none)
    targetElement.classList.remove('cms-animated');

    // 2. Forzar lectura del offsetHeight para que el DOM aplique el snap al estado inicial
    void targetElement.offsetHeight;

    // 3. Re-añadir cms-animated tras dos frames de renderizado
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            targetElement.classList.add('cms-animated');
        });
    });
}

/**
 * Desplaza la ventana de manera suave hacia un elemento (o selector),
 * compensando dinámicamente la altura de la cabecera fija/sticky (Header)
 * para evitar que el contenido quede oculto detrás del menú.
 *
 * @param {HTMLElement|string|null} elementoOSelector - Elemento DOM o selector CSS.
 * @param {number} margenExtra - Margen adicional de cortesía en píxeles (por defecto 28).
 */
export function desplazarSuaveConOffset(elementoOSelector, margenExtra = 28) {
    if (typeof window === 'undefined') return;

    let el = null;
    if (typeof elementoOSelector === 'string') {
        el = document.querySelector(elementoOSelector);
    } else if (elementoOSelector && elementoOSelector instanceof HTMLElement) {
        el = elementoOSelector;
    } else if (elementoOSelector && elementoOSelector.$el instanceof HTMLElement) {
        el = elementoOSelector.$el;
    }

    if (!el) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    // Obtener la altura real de la cabecera sticky o fixed
    const header = document.querySelector('header');
    let headerOffset = 0;
    if (header) {
        const style = window.getComputedStyle(header);
        if (style.position === 'sticky' || style.position === 'fixed') {
            headerOffset = header.getBoundingClientRect().height || header.offsetHeight || 80;
        }
    } else {
        headerOffset = 80;
    }

    const elementoTopAbsoluto = el.getBoundingClientRect().top + window.scrollY;
    const destinoScroll = Math.max(0, elementoTopAbsoluto - headerOffset - margenExtra);

    window.scrollTo({
        top: destinoScroll,
        behavior: 'smooth'
    });
}

/**
 * Composable useCookieConsent: Integración de CookieConsent (Orest Bida v3)
 * con Google Analytics 4 / Google Consent Mode v2 y navegación SPA de Inertia.js.
 *
 * 100% SSR-safe: no importa nada a nivel de módulo que toque window/document.
 * Todo el código de navegador se ejecuta exclusivamente dentro de onMounted
 * o tras interacción del usuario (click). El composable es un no-op en Node.js.
 */

/**
 * Comprobación centralizada de entorno navegador.
 */
function esCliente() {
    return typeof window !== 'undefined' && typeof document !== 'undefined';
}

export function useCookieConsent() {
    // En SSR devolvemos funciones vacías inmediatamente sin tocar nada
    if (!esCliente()) {
        return {
            initCookieConsent: async () => {},
            openCookiePreferences: async () => {},
        };
    }

    /**
     * Inicializa CookieConsent de Orest Bida con la configuración del backend.
     * Solo se ejecuta en el navegador, nunca en Node.js.
     */
    const initCookieConsent = async (settings = {}) => {
        if (!settings.cookie_consent_enabled) return;

        // No inicializar en iframes de preview del Page Builder ni en rutas del panel admin
        const ruta = window.location.pathname;
        const esPreviewFrame = window.self !== window.top;
        if (ruta.startsWith('/panel/') || esPreviewFrame) return;

        // Si ya está inicializado, evitar duplicidad
        if (window.__ccInicializado) return;
        window.__ccInicializado = true;

        // Carga diferida de CookieConsent y sus estilos exclusivamente en el cliente
        let CookieConsent;
        try {
            CookieConsent = await import('vanilla-cookieconsent');
            await import('vanilla-cookieconsent/dist/cookieconsent.css');
        } catch (error) {
            console.error('Error cargando módulo CookieConsent:', error);
            window.__ccInicializado = false;
            return;
        }

        // Guardar referencia global para acceso desde openCookiePreferences y el listener
        window.__ccModule = CookieConsent;

        inyectarEstilosPersonalizados(settings);
        sincronizarModoOscuro();
        registrarRastreoInertia(CookieConsent);
        activarShortcodesCookies(CookieConsent);

        const tieneAnalytics = Boolean(settings.analytics_enabled && settings.analytics_measurement_id);
        const measurementId = settings.analytics_measurement_id;
        const anonymizeIp = settings.analytics_anonymize_ip ?? true;

        if (tieneAnalytics) {
            initGoogleConsentModeDefault();
        }

        // Configuración de categorías de cookies
        const categories = {
            necessary: {
                enabled: true,
                readOnly: true,
            },
        };

        if (tieneAnalytics) {
            categories.analytics = {
                enabled: false,
                readOnly: false,
                autoClear: {
                    cookies: [
                        { name: /^_ga/ },
                        { name: '_gid' },
                        { name: '_gat' },
                    ],
                },
            };
        }

        // Textos configurables o por defecto
        const tituloModal = settings.cookie_consent_title || 'Gestionar cookies y privacidad';
        const descripcionModal = settings.cookie_consent_description
            || 'Utilizamos cookies propias y de terceros para garantizar el correcto funcionamiento del portal y realizar análisis estadísticos de navegación con el objetivo de mejorar nuestros contenidos.';

        const urlPrivacidad = settings.cookie_consent_privacy_url || '/politica-de-privacidad';
        const urlCookies = settings.cookie_consent_cookie_url || '/politica-de-cookies';

        const layoutModal = settings.cookie_consent_layout || 'box';
        const posicionModal = settings.cookie_consent_position || 'bottom right';
        const revision = Number(settings.cookie_consent_revision) || 1;

        await CookieConsent.run({
            revision,
            autoShow: true,
            disablePageInteraction: false,

            guiOptions: {
                consentModal: {
                    layout: layoutModal,
                    position: posicionModal,
                    equalWeightButtons: false,
                    flipButtons: false,
                },
                preferencesModal: {
                    layout: 'box',
                    position: 'right',
                    equalWeightButtons: false,
                    flipButtons: false,
                },
            },

            categories,

            language: {
                default: 'es',
                translations: {
                    es: {
                        consentModal: {
                            title: tituloModal,
                            description: `${descripcionModal} Puedes aceptar todas las cookies, configurar tus preferencias o rechazarlas. Para más información, consulta nuestra <a href="${urlCookies}" class="cc__link" target="_blank" rel="noopener">Política de Cookies</a> y <a href="${urlPrivacidad}" class="cc__link" target="_blank" rel="noopener">Política de Privacidad</a>.`,
                            acceptAllBtn: 'Aceptar todas',
                            acceptNecessaryBtn: 'Rechazar todas',
                            showPreferencesBtn: 'Configurar preferencias',
                            footer: `
                                <a href="${urlCookies}" target="_blank" rel="noopener">Política de Cookies</a>
                                <a href="${urlPrivacidad}" target="_blank" rel="noopener">Política de Privacidad</a>
                            `,
                        },
                        preferencesModal: {
                            title: 'Panel de Preferencias de Cookies',
                            acceptAllBtn: 'Aceptar todas',
                            acceptNecessaryBtn: 'Rechazar todas',
                            savePreferencesBtn: 'Guardar preferencias',
                            closeIconLabel: 'Cerrar ventana',
                            serviceCounterLabel: 'Servicios',
                            sections: [
                                {
                                    title: 'Uso de Cookies',
                                    description: `En este panel puedes habilitar o deshabilitar las diferentes categorías de cookies utilizadas en nuestro sitio web. Las cookies estrictamente necesarias no pueden desactivarse ya que son imprescindibles para la navegación técnica.`,
                                },
                                {
                                    title: 'Cookies Técnicas & Necesarias',
                                    description: 'Estas cookies son esenciales para el funcionamiento básico del sitio web (seguridad, autenticación, preferencias de sesión).',
                                    linkedCategory: 'necessary',
                                },
                                ...(tieneAnalytics ? [{
                                    title: 'Cookies de Analítica y Rendimiento',
                                    description: 'Nos permiten medir de forma anónima el número de visitas, el comportamiento de navegación y el rendimiento general de las páginas para ofrecer un mejor servicio.',
                                    linkedCategory: 'analytics',
                                    cookieTable: {
                                        caption: 'Listado de cookies analíticas',
                                        headers: {
                                            name: 'Cookie',
                                            domain: 'Servicio',
                                            desc: 'Finalidad',
                                        },
                                        body: [
                                            {
                                                name: '_ga, _ga_*',
                                                domain: 'Google Analytics 4',
                                                desc: 'Distingue usuarios anónimos y mide métricas de interacción.',
                                            },
                                        ],
                                    },
                                }] : []),
                                {
                                    title: 'Más información',
                                    description: `Para resolver cualquier duda relativa a nuestra política de cookies y tus derechos, visita nuestra <a class="cc__link" href="${urlPrivacidad}" target="_blank" rel="noopener">Política de Privacidad</a>.`,
                                },
                            ],
                        },
                    },
                },
            },

            onConsent: () => {
                if (tieneAnalytics) {
                    if (CookieConsent.acceptedCategory('analytics')) {
                        activarGoogleAnalytics(measurementId, anonymizeIp);
                    } else {
                        desactivarGoogleAnalytics(measurementId);
                    }
                }
            },

            onChange: () => {
                if (tieneAnalytics) {
                    if (CookieConsent.acceptedCategory('analytics')) {
                        activarGoogleAnalytics(measurementId, anonymizeIp);
                    } else {
                        desactivarGoogleAnalytics(measurementId);
                    }
                }
            },
        });
    };

    /**
     * Abre el modal de configuración de preferencias para el visitante.
     */
    const openCookiePreferences = async () => {
        // Intentar con la referencia global guardada durante initCookieConsent
        let CC = window.__ccModule;

        if (!CC) {
            try {
                CC = await import('vanilla-cookieconsent');
            } catch {
                return;
            }
        }

        try {
            CC.showPreferences();
        } catch {
            try { CC.show(true); } catch { /* No-op */ }
        }
    };

    return {
        initCookieConsent,
        openCookiePreferences,
    };
}

// ─── Funciones auxiliares (solo se ejecutan dentro del cliente) ───

/**
 * Prepara Google Consent Mode v2 por defecto con almacenamiento denegado.
 */
function initGoogleConsentModeDefault() {
    window.dataLayer = window.dataLayer || [];
    if (!window.gtag) {
        window.gtag = function () {
            window.dataLayer.push(arguments);
        };
    }

    window.gtag('consent', 'default', {
        analytics_storage: 'denied',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        wait_for_update: 500,
    });
}

/**
 * Activa Google Analytics e inyecta la etiqueta gtag.js de forma asíncrona.
 */
function activarGoogleAnalytics(measurementId, anonymizeIp = true) {
    if (!measurementId) return;

    window.__ccAnalyticsMeasurementId = measurementId;
    window.dataLayer = window.dataLayer || [];
    if (!window.gtag) {
        window.gtag = function () {
            window.dataLayer.push(arguments);
        };
    }

    // Actualizar consentimiento a concedido
    window.gtag('consent', 'update', {
        analytics_storage: 'granted',
    });

    // Eliminar bandera de desactivación
    delete window[`ga-disable-${measurementId}`];

    const scriptId = 'cms-gtag-script';
    if (!window.__ccGtagConfigured) {
        window.__ccGtagConfigured = true;
        window.gtag('js', new Date());
        window.gtag('config', measurementId, {
            send_page_view: false,
            anonymize_ip: Boolean(anonymizeIp),
        });
    }

    // Registrar inmediatamente la visita en dataLayer (no se pierde ninguna visita aunque gtag.js cargue en idle)
    window.gtag('event', 'page_view', {
        page_title: document.title,
        page_location: window.location.href,
    });

    // Descargar el script externo de Google Tag Manager cuando el hilo principal esté libre o haya interacción,
    // evitando bloquear el LCP cuando el visitante ya aceptó las cookies previamente.
    if (!document.getElementById(scriptId) && !window.__ccGtagLoading) {
        window.__ccGtagLoading = true;
        const inyectarGtag = () => {
            if (document.getElementById(scriptId)) return;
            const scriptTag = document.createElement('script');
            scriptTag.id = scriptId;
            scriptTag.async = true;
            scriptTag.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
            document.head.appendChild(scriptTag);
        };

        const dispararAlInteractuar = () => {
            ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((ev) =>
                window.removeEventListener(ev, dispararAlInteractuar, { passive: true })
            );
            inyectarGtag();
        };

        ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach((ev) =>
            window.addEventListener(ev, dispararAlInteractuar, { once: true, passive: true })
        );

        const programarTrasLoad = () => {
            if (typeof window.requestIdleCallback === 'function') {
                window.requestIdleCallback(inyectarGtag, { timeout: 2500 });
            } else {
                setTimeout(inyectarGtag, 1800);
            }
        };

        if (document.readyState === 'complete') {
            programarTrasLoad();
        } else {
            window.addEventListener('load', programarTrasLoad, { once: true });
        }
    }
}

/**
 * Desactiva Google Analytics y revoca el consentimiento.
 */
function desactivarGoogleAnalytics(measurementId) {
    if (!measurementId) return;

    window[`ga-disable-${measurementId}`] = true;

    if (window.gtag) {
        window.gtag('consent', 'update', {
            analytics_storage: 'denied',
        });
    }
}

/**
 * Sincroniza la clase cc--darkmode con la clase .dark del sitio.
 */
function sincronizarModoOscuro() {
    const aplicarClase = () => {
        const esOscuro = document.documentElement.classList.contains('dark');
        document.documentElement.classList.toggle('cc--darkmode', esOscuro);
    };

    aplicarClase();

    const observer = new MutationObserver(aplicarClase);
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}

/**
 * Escucha las navegaciones SPA de Inertia para reportar page_view.
 * Importa el router de Inertia dinámicamente para no contaminar el bundle SSR.
 */
function registrarRastreoInertia(CookieConsent) {
    if (window.__ccInertiaListenerRegistrado) return;
    window.__ccInertiaListenerRegistrado = true;

    // Importar router de Inertia dinámicamente para que nunca se ejecute en SSR
    import('@inertiajs/vue3').then(({ router }) => {
        router.on('finish', () => {
            const mid = window.__ccAnalyticsMeasurementId;
            if (!mid) return;

            try {
                if (CookieConsent.acceptedCategory('analytics') && window.gtag) {
                    window.gtag('event', 'page_view', {
                        page_title: document.title,
                        page_location: window.location.href,
                    });
                }
            } catch {
                // Silencioso si CookieConsent aún no está listo
            }
        });
    });
}

/**
 * Inyecta un bloque <style> con las variables CSS de cookieconsent
 * mapeadas a los colores del tema configurado en el backend.
 * Se adapta automáticamente al modo claro y oscuro.
 */
function inyectarEstilosPersonalizados(settings) {
    const styleId = 'cc-custom-theme';
    if (document.getElementById(styleId)) return;

    // Colores para el banner de cookies: Aceptar siempre en verde (#10b981), Rechazar en color contrastado/neutro
    const verdeAceptar = settings.primary_color || '#10b981';
    const colorRechazar = (settings.button_primary_bg && settings.button_primary_bg !== verdeAceptar)
        ? settings.button_primary_bg
        : '#18181b';

    const btnPrimaryBg = verdeAceptar;
    const btnPrimaryText = '#ffffff';
    const btnSecondaryBg = colorRechazar;
    const btnSecondaryText = '#ffffff';

    // Colores primarios del tema (modo oscuro)
    const btnPrimaryBgDark = verdeAceptar;
    const btnPrimaryTextDark = '#ffffff';
    const btnSecondaryBgDark = '#27272a';
    const btnSecondaryTextDark = '#ffffff';

    const primaryColor = verdeAceptar;
    const borderRadius = settings.button_border_radius || 'rounded-xl';
    const bodyFont = settings.body_font || 'Open Sans';

    // Mapear border-radius de Tailwind a CSS
    const radiusMap = {
        'rounded-none': '0px',
        'rounded-sm': '0.125rem',
        'rounded': '0.25rem',
        'rounded-md': '0.375rem',
        'rounded-lg': '0.5rem',
        'rounded-xl': '0.75rem',
        'rounded-2xl': '1rem',
        'rounded-3xl': '1.5rem',
        'rounded-full': '9999px',
    };
    const btnRadius = radiusMap[borderRadius] || '0.75rem';

    // Generar color hover con opacidad reducida (más oscuro)
    const hoverPrimaryBg = ajustarBrillo(btnPrimaryBg, -15);
    const hoverSecondaryBg = ajustarBrillo(btnSecondaryBg, 15);
    const hoverPrimaryBgDark = ajustarBrillo(btnPrimaryBgDark, -15);
    const hoverSecondaryBgDark = ajustarBrillo(btnSecondaryBgDark, 15);

    const style = document.createElement('style');
    style.id = styleId;
    style.textContent = `
        /* ── CookieConsent: Tema personalizado del CMS ── */

        /* Modo claro (por defecto) */
        #cc-main {
            --cc-font-family: '${bodyFont}', system-ui, sans-serif;
            --cc-btn-border-radius: ${btnRadius};
            --cc-modal-border-radius: ${btnRadius};
            --cc-btn-primary-bg: ${btnPrimaryBg};
            --cc-btn-primary-color: ${btnPrimaryText};
            --cc-btn-primary-hover-bg: ${hoverPrimaryBg};
            --cc-btn-primary-hover-color: ${btnPrimaryText};
            --cc-btn-primary-border-color: ${btnPrimaryBg};
            --cc-btn-primary-hover-border-color: ${hoverPrimaryBg};
            --cc-btn-secondary-bg: ${btnSecondaryBg};
            --cc-btn-secondary-color: ${btnSecondaryText};
            --cc-btn-secondary-hover-bg: ${hoverSecondaryBg};
            --cc-btn-secondary-hover-color: ${btnSecondaryText};
            --cc-btn-secondary-border-color: ${btnSecondaryBg};
            --cc-btn-secondary-hover-border-color: ${hoverSecondaryBg};
            --cc-toggle-on-bg: ${primaryColor};
            --cc-link-color: ${primaryColor};
        }

        /* Modo oscuro */
        #cc-main.cc--darkmode {
            --cc-btn-primary-bg: ${btnPrimaryBgDark};
            --cc-btn-primary-color: ${btnPrimaryTextDark};
            --cc-btn-primary-hover-bg: ${hoverPrimaryBgDark};
            --cc-btn-primary-hover-color: ${btnPrimaryTextDark};
            --cc-btn-primary-border-color: ${btnPrimaryBgDark};
            --cc-btn-primary-hover-border-color: ${hoverPrimaryBgDark};
            --cc-btn-secondary-bg: ${btnSecondaryBgDark};
            --cc-btn-secondary-color: ${btnSecondaryTextDark};
            --cc-btn-secondary-hover-bg: ${hoverSecondaryBgDark};
            --cc-btn-secondary-hover-color: ${btnSecondaryTextDark};
            --cc-btn-secondary-border-color: ${btnSecondaryBgDark};
            --cc-btn-secondary-hover-border-color: ${hoverSecondaryBgDark};
            --cc-toggle-on-bg: ${primaryColor};
            --cc-link-color: ${primaryColor};
        }
    `;
    document.head.appendChild(style);
}

/**
 * Ajusta el brillo de un color hexadecimal sumando/restando un delta.
 * @param {string} hex - Color en formato #RRGGBB
 * @param {number} delta - Valor a sumar (positivo = más claro, negativo = más oscuro)
 * @returns {string} - Color ajustado en formato #RRGGBB
 */
function ajustarBrillo(hex, delta) {
    try {
        const limpio = hex.replace('#', '');
        const r = Math.max(0, Math.min(255, parseInt(limpio.substring(0, 2), 16) + delta));
        const g = Math.max(0, Math.min(255, parseInt(limpio.substring(2, 4), 16) + delta));
        const b = Math.max(0, Math.min(255, parseInt(limpio.substring(4, 6), 16) + delta));
        return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
    } catch {
        return hex;
    }
}

/**
 * Activa los shortcodes {{cookies_button}} en el DOM.
 * Busca todas las ocurrencias del placeholder y las reemplaza por un botón
 * interactivo que abre el modal de preferencias de cookies.
 *
 * También observa el DOM para activar shortcodes en contenido cargado dinámicamente
 * (SPA / navegaciones Inertia).
 */
function activarShortcodesCookies(CookieConsent) {
    if (window.__ccShortcodesActivados) return;
    window.__ccShortcodesActivados = true;

    const procesarShortcodes = () => {
        // Buscar textos {{cookies_button}} en el DOM
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: (node) => {
                    return node.textContent.includes('{{cookies_button}}')
                        ? NodeFilter.FILTER_ACCEPT
                        : NodeFilter.FILTER_REJECT;
                },
            }
        );

        const nodosTexto = [];
        while (walker.nextNode()) {
            nodosTexto.push(walker.currentNode);
        }

        nodosTexto.forEach((nodo) => {
            const partes = nodo.textContent.split('{{cookies_button}}');
            const fragmento = document.createDocumentFragment();

            partes.forEach((parte, index) => {
                if (parte) {
                    fragmento.appendChild(document.createTextNode(parte));
                }
                // Insertar botón entre cada parte (excepto después de la última)
                if (index < partes.length - 1) {
                    fragmento.appendChild(crearBotonCookies(CookieConsent));
                }
            });

            nodo.parentNode.replaceChild(fragmento, nodo);
        });

        // También buscar en atributos v-html que ya fueron renderizados (elementos con innerHTML)
        document.querySelectorAll('[data-cc-shortcode]').forEach((el) => {
            el.addEventListener('click', () => {
                try { CookieConsent.showPreferences(); } catch { /* no-op */ }
            });
        });
    };

    // Procesar al inicializar
    procesarShortcodes();

    // Re-procesar tras cada navegación Inertia (contenido nuevo)
    import('@inertiajs/vue3').then(({ router }) => {
        router.on('finish', () => {
            // Esperar a que Vue actualice el DOM
            setTimeout(procesarShortcodes, 100);
        });
    });
}

/**
 * Crea un elemento <button> estilizado para abrir las preferencias de cookies.
 * Usa los colores del tema configurado del sitio (variables CSS existentes).
 */
function crearBotonCookies(CookieConsent) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'cc-shortcode-btn';
    btn.setAttribute('data-cc-shortcode', 'true');
    btn.innerHTML = '<i class="pi pi-shield" style="font-size:0.85em;margin-right:0.4em;"></i>Preferencias de cookies';
    btn.addEventListener('click', () => {
        try { CookieConsent.showPreferences(); } catch { /* no-op */ }
    });

    // Inyectar estilos del botón shortcode una sola vez
    if (!document.getElementById('cc-shortcode-styles')) {
        const style = document.createElement('style');
        style.id = 'cc-shortcode-styles';
        style.textContent = `
            .cc-shortcode-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.35em;
                padding: 0.5em 1.1em;
                font-size: 0.875rem;
                font-weight: 500;
                line-height: 1.4;
                color: var(--btn-secondary-text, #ffffff);
                background: var(--btn-secondary-bg, #6366f1);
                border: 1px solid transparent;
                border-radius: var(--button-border-radius, 0.75rem);
                cursor: pointer;
                transition: all 0.2s ease;
                font-family: inherit;
            }
            .cc-shortcode-btn:hover {
                opacity: 0.88;
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }
            .cc-shortcode-btn:active {
                transform: translateY(0);
            }
        `;
        document.head.appendChild(style);
    }

    return btn;
}


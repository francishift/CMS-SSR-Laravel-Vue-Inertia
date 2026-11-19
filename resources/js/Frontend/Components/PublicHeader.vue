<script setup>
/**
 * Encabezado Público (Header) Modular, Impactante y Personalizable.
 * 
 * Soporta múltiples layouts (Logo Izquierda/Centro, Split 50/50, Editorial),
 * submenús desplegables multinivel, botón CTA destacado, cajón móvil Off-Canvas
 * y personalización completa desde la configuración general.
 */
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { esNavegacionInertia, getRutaInterna } from '@/Composables/useUrlHelpers';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    menuHeader: { type: Object, default: null },
    isMobilePreview: { type: Boolean, default: false },
    transparentOverride: { type: Object, default: null },
});

const page = usePage();

function esRutaActiva(rawUrl) {
    if (!rawUrl) return false;
    const path = getRutaInterna(rawUrl);
    const currentPath = page.url ? page.url.split('?')[0] : '/';

    if (path === '/' && currentPath === '/') return true;
    if (path !== '/' && currentPath.startsWith(path)) return true;

    return false;
}

function getItemClass(rawUrl, isSubmenuButton = false) {
    const active = esRutaActiva(rawUrl);
    const contrast = navContrastClass.value;
    if (active) {
        return ['header-nav-link header-nav-link-active px-3 py-2 rounded-lg text-sm font-bold bg-zinc-100/90 dark:bg-zinc-800/90 shadow-xs transition-all inline-flex items-center gap-2', contrast].filter(Boolean).join(' ');
    }
    if (isSubmenuButton) {
        return ['header-nav-link px-3 py-2 rounded-lg text-sm font-semibold group-hover:bg-zinc-100/70 dark:group-hover:bg-zinc-800/60 transition-all inline-flex items-center gap-1.5 cursor-pointer', contrast].filter(Boolean).join(' ');
    }
    return ['header-nav-link px-3 py-2 rounded-lg text-sm font-semibold hover:bg-zinc-100/70 dark:hover:bg-zinc-800/60 transition-all inline-flex items-center gap-2', contrast].filter(Boolean).join(' ');
}

function getMobileItemClass(rawUrl) {
    const active = esRutaActiva(rawUrl);
    if (active) {
        return 'header-nav-link header-nav-link-active flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-sm bg-zinc-100/90 dark:bg-zinc-900/90 shadow-xs transition-colors';
    }
    return 'header-nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-semibold text-sm hover:bg-zinc-100/80 dark:hover:bg-zinc-900/80 transition-colors';
}

const headerRef = ref(null);
const menuMovilAbierto = ref(false);
const submenusAbiertos = ref({});
const esModoOscuroPublico = ref(false);
const esModoOscuroActivo = ref(false);

const isScrolled = ref(false);
const isVisible = ref(true);
const lastScrollY = ref(0);
let unregisterRouter = null;

function handleScroll() {
    if (typeof window === 'undefined') return;
    const currentY = window.scrollY;

    // Si el menú móvil está abierto y se produce scroll en la pantalla, cerrarlo automáticamente
    // Umbral de 15px para no interferir con toques o pulsaciones en pantallas táctiles
    if (menuMovilAbierto.value && Math.abs(currentY - lastScrollY.value) > 15) {
        menuMovilAbierto.value = false;
    }

    isScrolled.value = currentY > 30;

    const style = props.settings.header_style || 'sticky';
    if (style === 'reveal_on_scroll_up') {
        if (currentY > lastScrollY.value && currentY > 100) {
            isVisible.value = false;
        } else {
            isVisible.value = true;
        }
    } else {
        isVisible.value = true;
    }

    lastScrollY.value = currentY;
}

function cerrarMenuMovilSuave() {
    // Permitir que Inertia procese el clic y ejecute la navegación antes de desmontar el menú del DOM
    setTimeout(() => {
        menuMovilAbierto.value = false;
    }, 120);
}

function onDocumentClick(event) {
    if (!menuMovilAbierto.value) return;
    // Si el menú móvil está en modo dropdown y el clic se dio fuera de la cabecera, cerrarlo
    if (props.settings.mobile_menu_style === 'dropdown' && headerRef.value && !headerRef.value.contains(event.target)) {
        menuMovilAbierto.value = false;
    }
}

function handleKeyDown(event) {
    if (event.key === 'Escape' && menuMovilAbierto.value) {
        menuMovilAbierto.value = false;
    }
}

const isTransparentSectionActive = computed(() => {
    return Boolean(props.transparentOverride?.active || page.props?.cabeceraTransparente?.active);
});

const navColorLightConfig = computed(() => {
    return props.transparentOverride?.navColor || page.props?.cabeceraTransparente?.navColor || 'inherit';
});

const navColorDarkConfig = computed(() => {
    const rawDark = props.transparentOverride?.navColorDark || page.props?.cabeceraTransparente?.navColorDark || 'same';
    return (!rawDark || rawDark === 'same') ? navColorLightConfig.value : rawDark;
});

const navContrastClass = computed(() => {
    if (!isTransparentSectionActive.value || isScrolled.value) return '';
    const colorLight = navColorLightConfig.value;
    const colorDark = navColorDarkConfig.value;

    if (colorLight === colorDark) {
        if (colorLight === 'light') return 'header-nav-contrast-light !text-white hover:!text-white/80 active:!text-white';
        if (colorLight === 'dark') return 'header-nav-contrast-dark !text-zinc-900 hover:!text-zinc-700 active:!text-zinc-900';
        return '';
    }

    const classes = [];
    if (colorLight === 'light') classes.push('header-nav-contrast-light-mode-white');
    else if (colorLight === 'dark') classes.push('header-nav-contrast-light-mode-dark');

    if (colorDark === 'light') classes.push('header-nav-contrast-dark-mode-white');
    else if (colorDark === 'dark') classes.push('header-nav-contrast-dark-mode-dark');

    return classes.join(' ');
});

const navContrastButtonClass = computed(() => {
    if (!isTransparentSectionActive.value || isScrolled.value) return '';
    const colorLight = navColorLightConfig.value;
    const colorDark = navColorDarkConfig.value;

    if (colorLight === colorDark) {
        if (colorLight === 'light') return '!text-white hover:!bg-white/10';
        if (colorLight === 'dark') return '!text-zinc-900 hover:!bg-black/5';
        return '';
    }

    const classes = [];
    if (colorLight === 'light') classes.push('header-nav-contrast-light-mode-white hover:bg-white/10');
    else if (colorLight === 'dark') classes.push('header-nav-contrast-light-mode-dark hover:bg-black/5');

    if (colorDark === 'light') classes.push('header-nav-contrast-dark-mode-white dark:hover:bg-white/10');
    else if (colorDark === 'dark') classes.push('header-nav-contrast-dark-mode-dark dark:hover:bg-black/5');

    return classes.join(' ');
});

const permiteEleccionUsuario = computed(() => props.settings.frontend_theme_mode === 'user_choice');

function actualizarEstadoModoOscuro() {
    if (typeof document !== 'undefined') {
        esModoOscuroActivo.value = document.documentElement.classList.contains('dark');
    }
}

onMounted(() => {
    if (permiteEleccionUsuario.value) {
        const pref = localStorage.getItem('public_theme_mode');
        const defaultInitialTheme = props.settings.frontend_default_theme || 'light';

        if (pref === 'dark') {
            esModoOscuroPublico.value = true;
        } else if (pref === 'light') {
            esModoOscuroPublico.value = false;
        } else {
            if (defaultInitialTheme === 'dark') {
                esModoOscuroPublico.value = true;
            } else if (defaultInitialTheme === 'light') {
                esModoOscuroPublico.value = false;
            } else if (defaultInitialTheme === 'system') {
                esModoOscuroPublico.value = Boolean(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            }
        }
    }
    actualizarEstadoModoOscuro();

    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(() => {
            actualizarEstadoModoOscuro();
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('keydown', handleKeyDown);
    document.addEventListener('click', onDocumentClick);
    handleScroll();

    // Cerrar menú móvil automáticamente ante cualquier navegación Inertia SPA
    unregisterRouter = router.on('navigate', () => {
        menuMovilAbierto.value = false;
    });
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('scroll', handleScroll);
        window.removeEventListener('keydown', handleKeyDown);
    }
    if (typeof document !== 'undefined') {
        document.removeEventListener('click', onDocumentClick);
    }
    if (typeof unregisterRouter === 'function') {
        unregisterRouter();
    }
});

function toggleModoOscuroPublico() {
    esModoOscuroPublico.value = !esModoOscuroPublico.value;
    const nuevoModo = esModoOscuroPublico.value ? 'dark' : 'light';
    localStorage.setItem('public_theme_mode', nuevoModo);
    document.documentElement.classList.toggle('dark', esModoOscuroPublico.value);
    actualizarEstadoModoOscuro();
}

function toggleMenuMovil() {
    menuMovilAbierto.value = !menuMovilAbierto.value;
}

function toggleSubmenuMovil(id) {
    submenusAbiertos.value[id] = !submenusAbiertos.value[id];
}

function getRel(item, isExternal = false) {
    const parts = [];
    if (isExternal || item?.target === '_blank') {
        parts.push('noopener', 'noreferrer');
    }
    if (item?.is_nofollow) {
        parts.push('nofollow');
    }
    return parts.length ? parts.join(' ') : undefined;
}

const logoActualSrc = computed(() => {
    if (isTransparentSectionActive.value && !isScrolled.value) {
        const activeNavContrast = esModoOscuroActivo.value ? navColorDarkConfig.value : navColorLightConfig.value;
        if (activeNavContrast === 'light') {
            return props.settings.logo_dark_path || props.settings.logo_path;
        }
        if (activeNavContrast === 'dark') {
            return props.settings.logo_path || props.settings.logo_dark_path;
        }
    }
    if (esModoOscuroActivo.value) {
        return props.settings.logo_dark_path || props.settings.logo_path;
    }
    return props.settings.logo_path || props.settings.logo_dark_path;
});

const estiloLogoActual = computed(() => {
    const filtros = [];
    if (esModoOscuroActivo.value) {
        if (props.settings.logo_dark_invert) filtros.push('invert(100%)');
        if (props.settings.logo_dark_brightness && Number(props.settings.logo_dark_brightness) !== 100) {
            filtros.push(`brightness(${props.settings.logo_dark_brightness}%)`);
        }
    } else {
        if (props.settings.logo_invert) filtros.push('invert(100%)');
        if (props.settings.logo_brightness && Number(props.settings.logo_brightness) !== 100) {
            filtros.push(`brightness(${props.settings.logo_brightness}%)`);
        }
    }
    return filtros.length ? { filter: filtros.join(' ') } : {};
});

const esFlotanteOCapsula = computed(() => {
    return props.settings.header_style === 'floating' || props.settings.header_shadow === 'pill';
});

// Estilo inline para asegurar compatibilidad universal del efecto Cristal esmerilado (backdrop-filter)
const headerGlassStyle = computed(() => {
    if (esFlotanteOCapsula.value) return {};
    if (isTransparentSectionActive.value && !isScrolled.value) return {};
    const bgType = props.settings.header_bg_type || 'glassmorphism';
    if (bgType === 'glassmorphism' || bgType === 'gradient') {
        return {
            backdropFilter: 'blur(16px) saturate(180%)',
            WebkitBackdropFilter: 'blur(16px) saturate(180%)',
        };
    }
    return {};
});

const barBoxGlassStyle = computed(() => {
    if (!esFlotanteOCapsula.value) return {};
    if (isTransparentSectionActive.value && !isScrolled.value) return {};
    const bgType = props.settings.header_bg_type || 'glassmorphism';
    if (bgType === 'glassmorphism' || bgType === 'gradient') {
        return {
            backdropFilter: 'blur(16px) saturate(180%)',
            WebkitBackdropFilter: 'blur(16px) saturate(180%)',
        };
    }
    return {};
});

// Clases del elemento <header> raíz (Barra 100% completa en sticky, o marco esquelético flotante en cápsula)
const headerClasses = computed(() => {
    const style = props.settings.header_style || 'sticky';
    const bgType = props.settings.header_bg_type || 'glassmorphism';
    const shadow = props.settings.header_shadow || 'border';

    // Manejo dinámico de animación al ocultar/mostrar (reveal_on_scroll_up)
    let revealAnimation = 'translate-y-0 opacity-100 transition-all duration-300';
    if (style === 'reveal_on_scroll_up' && !isVisible.value) {
        revealAnimation = '-translate-y-full opacity-0 pointer-events-none transition-all duration-300';
    }

    if (esFlotanteOCapsula.value) {
        let capsuleReveal = 'translate-y-0 opacity-100';
        if (style === 'reveal_on_scroll_up' && !isVisible.value) {
            capsuleReveal = '-translate-y-28 opacity-0 pointer-events-none';
        }

        // Si la sección activa cabecera transparente y no hay desplazamiento:
        // Se posiciona de forma absoluta para no empujar el contenido y permitir que la sección comience en y = 0
        let capsulePos = 'sticky top-3 sm:top-5';
        if (isTransparentSectionActive.value) {
            capsulePos = !isScrolled.value ? 'absolute top-3 sm:top-5' : 'fixed top-3 sm:top-5';
        }

        return [
            capsulePos,
            'inset-x-0 z-50 transition-all duration-300 px-3 sm:px-6 pointer-events-none w-full flex justify-center',
            capsuleReveal,
            isTransparentSectionActive.value ? 'header-transparent-section' : '',
            isScrolled.value ? 'header-scrolled' : ''
        ].filter(Boolean).join(' ');
    }

    let positioning = 'sticky top-0 z-50 transition-all duration-300';
    if (style === 'static') positioning = 'relative z-50 transition-all duration-300';
    if (style === 'transparent' || isTransparentSectionActive.value) {
        positioning = !isScrolled.value
            ? 'absolute top-0 inset-x-0 z-50 transition-all duration-300'
            : 'fixed top-0 inset-x-0 z-50 transition-all duration-300 shadow-md';
    }

    let background = 'bg-white/65 dark:bg-zinc-950/70 backdrop-blur-xl';
    if (bgType === 'solid') background = 'bg-white dark:bg-zinc-950';
    if (bgType === 'gradient') background = 'bg-gradient-to-r from-white/70 via-zinc-50/70 to-white/70 dark:from-zinc-950/75 dark:via-zinc-900/75 dark:to-zinc-950/75 backdrop-blur-xl';
    if ((bgType === 'transparent' || isTransparentSectionActive.value) && !isScrolled.value) {
        background = '!bg-transparent';
    }

    let borderShadow = 'border-b border-zinc-200/70 dark:border-zinc-800/70';
    if (shadow === 'shadow' || (isScrolled.value && style === 'shrink_on_scroll')) {
        borderShadow = 'shadow-md border-b border-zinc-200/50 dark:border-zinc-800/50';
    }
    if (shadow === 'none' || ((style === 'transparent' || isTransparentSectionActive.value) && !isScrolled.value)) {
        borderShadow = '!border-none !shadow-none';
    }

    return [
        positioning,
        background,
        borderShadow,
        revealAnimation,
        isTransparentSectionActive.value ? 'header-transparent-section' : '',
        isScrolled.value ? 'header-scrolled' : '',
        style === 'shrink_on_scroll' ? 'header-shrink' : '',
        'w-full'
    ].filter(Boolean).join(' ');
});

// Clases de la cápsula o del contenedor de la barra principal
const barBoxClasses = computed(() => {
    const bgType = props.settings.header_bg_type || 'glassmorphism';
    const width = props.settings.header_width || 'contained';
    const radius = props.settings.header_border_radius || 'rounded-2xl';
    const capShadow = props.settings.header_capsule_shadow || 'shadow-xl';

    if (!esFlotanteOCapsula.value) {
        // En modo estándar no flotante, el contenedor interno solo encuadra el menú en el centro
        let widthClass = 'max-w-7xl mx-auto w-full';
        if (width === 'full') widthClass = 'w-full';
        else if (width === 'wide_1600') widthClass = 'max-w-[1600px] mx-auto w-full';
        else if (width === 'expand' || width === 'screen_2xl') widthClass = 'max-w-screen-2xl mx-auto w-full';
        else if (width === 'wide_1440') widthClass = 'max-w-[1440px] mx-auto w-full';
        else if (width === 'contained' || width === 'boxed') widthClass = 'max-w-7xl mx-auto w-full';
        else if (width === 'compact') widthClass = 'max-w-6xl mx-auto w-full';
        else if (width === 'skinny') widthClass = 'max-w-5xl mx-auto w-full';
        else if (width === 'narrow') widthClass = 'max-w-4xl mx-auto w-full';
        else if (width === 'ultra_compact' || width === 'small') widthClass = 'max-w-3xl mx-auto w-full';
        else if (width === 'fit') widthClass = 'w-fit mx-auto';

        return widthClass;
    }

    // Modo Cápsula Flotante: fondo translúcido cristalino con blur, sombra flotante configurable y borde redondeado
    let background = 'bg-white/65 dark:bg-zinc-950/70 backdrop-blur-xl';
    if (bgType === 'solid') background = 'bg-white dark:bg-zinc-950';
    if (bgType === 'gradient') background = 'bg-gradient-to-r from-white/70 via-zinc-50/70 to-white/70 dark:from-zinc-950/75 dark:via-zinc-900/75 dark:to-zinc-950/75 backdrop-blur-xl';
    if (bgType === 'transparent' || (isTransparentSectionActive.value && !isScrolled.value)) {
        background = '!bg-transparent';
    }

    let borderShadow = 'border border-zinc-200/80 dark:border-zinc-800/80 shadow-xl shadow-black/5';
    if (capShadow === 'shadow-sm') borderShadow = 'border border-zinc-200/80 dark:border-zinc-800/80 shadow-sm';
    if (capShadow === 'shadow-md') borderShadow = 'border border-zinc-200/80 dark:border-zinc-800/80 shadow-md';
    if (capShadow === 'shadow-lg') borderShadow = 'border border-zinc-200/80 dark:border-zinc-800/80 shadow-lg';
    if (capShadow === 'shadow-2xl') borderShadow = 'border border-zinc-200/80 dark:border-zinc-800/80 shadow-2xl shadow-black/10';
    if (capShadow === 'shadow-glow') borderShadow = 'border border-[var(--primary-color,#10b981)]/40 shadow-lg shadow-[var(--primary-color,#10b981)]/20';
    if (capShadow === 'none') borderShadow = 'border border-zinc-200/80 dark:border-zinc-800/80 border-none shadow-none';

    if (isTransparentSectionActive.value && !isScrolled.value) {
        borderShadow = '!border-none !shadow-none';
    }

    let widthClass = 'max-w-7xl mx-auto w-full';
    if (width === 'full') widthClass = 'w-full';
    else if (width === 'wide_1600') widthClass = 'max-w-[1600px] mx-auto w-full';
    else if (width === 'expand' || width === 'screen_2xl') widthClass = 'max-w-screen-2xl mx-auto w-full';
    else if (width === 'wide_1440') widthClass = 'max-w-[1440px] mx-auto w-full';
    else if (width === 'contained' || width === 'boxed') widthClass = 'max-w-7xl mx-auto w-full';
    else if (width === 'compact') widthClass = 'max-w-6xl mx-auto w-full';
    else if (width === 'skinny') widthClass = 'max-w-5xl mx-auto w-full';
    else if (width === 'narrow') widthClass = 'max-w-4xl mx-auto w-full';
    else if (width === 'ultra_compact' || width === 'small') widthClass = 'max-w-3xl mx-auto w-full';
    else if (width === 'fit') widthClass = 'w-fit mx-auto';

    return [background, borderShadow, radius, widthClass, 'pointer-events-auto transition-all duration-300'].join(' ');
});

const innerPaddingClass = computed(() => {
    const pad = props.settings.header_capsule_padding || props.settings.header_padding_y || 'standard';
    const style = props.settings.header_style || 'sticky';

    const extraPx = esFlotanteOCapsula.value ? 'px-3 sm:px-5 lg:px-7' : 'px-4 sm:px-6 lg:px-8';

    // Modo 'shrink_on_scroll': al desplazarse hacia abajo, encoger suavemente la altura y padding
    if (style === 'shrink_on_scroll' && isScrolled.value) {
        return `py-2 min-h-12 sm:min-h-14 transition-all duration-300 ${extraPx}`;
    }

    if (pad === 'compact') return `py-2.5 min-h-12 sm:min-h-14 transition-all duration-300 ${esFlotanteOCapsula.value ? 'px-4 sm:px-6' : 'px-3.5 sm:px-5'}`;
    if (pad === 'spacious') return `py-4.5 min-h-20 sm:min-h-24 transition-all duration-300 ${esFlotanteOCapsula.value ? 'px-6 sm:px-10' : 'px-5 sm:px-8'}`;
    return `py-3 min-h-16 sm:min-h-20 transition-all duration-300 ${extraPx}`;
});

// Ítems de menú filtrados por activos
const menuItemsActivos = computed(() => {
    return (props.menuHeader?.items || []).filter(item => item.is_active);
});

// Menú dividido (50% izquierda / 50% derecha) para el layout 'logo_center_menu_split'
const menuItemsSplit = computed(() => {
    const items = menuItemsActivos.value;
    const mitad = Math.ceil(items.length / 2);
    return {
        izq: items.slice(0, mitad),
        der: items.slice(mitad),
    };
});

// Botones e Iconos de Acción Dinámicos
const headerButtonsActivos = computed(() => {
    const raw = props.settings.header_buttons;
    if (Array.isArray(raw)) {
        return raw.filter(b => b && b.is_active !== false);
    }
    if (props.settings.header_cta_enable && props.settings.header_cta_text) {
        return [{
            id: 'legacy_cta',
            text: props.settings.header_cta_text,
            url: props.settings.header_cta_url || '#',
            target: '_self',
            icon: props.settings.header_cta_icon || '',
            variant: props.settings.header_cta_variant || 'primary',
            is_active: true,
            show_on_desktop: true,
            show_on_mobile: props.settings.mobile_show_cta ?? true,
        }];
    }
    return [];
});

const headerButtonsEscritorio = computed(() => {
    return headerButtonsActivos.value.filter(b => b.show_on_desktop !== false);
});

const headerButtonsMovilBarra = computed(() => {
    return headerButtonsActivos.value.filter(b => b.show_on_mobile_header === true);
});

const headerButtonsMovilMenu = computed(() => {
    return headerButtonsActivos.value.filter(b => {
        if (b.show_on_mobile_menu !== undefined) return b.show_on_mobile_menu === true;
        return b.show_on_mobile !== false;
    });
});

const headerButtonsMovilConTexto = computed(() => {
    return headerButtonsMovilMenu.value.filter(b => b.text && b.text.trim() !== '' && b.variant !== 'icon_only');
});

const headerButtonsMovilSoloIcono = computed(() => {
    return headerButtonsMovilMenu.value.filter(b => !b.text || b.text.trim() === '' || b.variant === 'icon_only');
});

function getButtonVariantClass(btn, isIconOnly = false) {
    const v = btn.variant || 'primary';
    const radius = props.settings.button_border_radius || 'rounded-xl';

    if (v === 'icon_only' || isIconOnly || (!btn.text || btn.text.trim() === '')) {
        if (v === 'glow') {
            return `w-10 h-10 ${radius} bg-[var(--primary-color,#10b981)] text-white shadow-lg shadow-[var(--primary-color,#10b981)]/30 hover:scale-105 active:scale-95 transition-all cursor-pointer shrink-0`;
        }
        if (v === 'outline') {
            return `w-10 h-10 ${radius} border-2 border-[var(--primary-color,#10b981)] text-[var(--primary-color,#10b981)] hover:bg-[var(--primary-color,#10b981)] hover:text-white transition-all cursor-pointer shrink-0`;
        }
        if (v === 'secondary') {
            return `w-10 h-10 ${radius} bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-zinc-100 dark:hover:bg-white dark:text-zinc-900 transition-all cursor-pointer shrink-0`;
        }
        return `w-10 h-10 ${radius} text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white bg-zinc-100/80 dark:bg-zinc-800/80 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-all cursor-pointer shrink-0`;
    }

    if (v === 'secondary') {
        return `bg-zinc-900 hover:bg-zinc-800 text-white dark:bg-zinc-100 dark:hover:bg-white dark:text-zinc-900 shadow-sm px-4 py-2 text-xs font-bold ${radius} transition-all`;
    }
    if (v === 'outline') {
        return `border-2 border-[var(--primary-color,#10b981)] text-[var(--primary-color,#10b981)] hover:bg-[var(--primary-color,#10b981)] hover:text-white px-4 py-2 text-xs font-bold ${radius} transition-all`;
    }
    if (v === 'glow') {
        return `bg-[var(--primary-color,#10b981)] text-white shadow-lg shadow-[var(--primary-color,#10b981)]/30 hover:scale-105 active:scale-95 animate-pulse px-4 py-2 text-xs font-bold ${radius} transition-all`;
    }
    if (v === 'link') {
        return 'text-xs font-bold text-zinc-700 hover:text-[var(--primary-color,#10b981)] dark:text-zinc-300 transition-colors px-2 py-1';
    }
    return `bg-[var(--primary-color,#10b981)] hover:opacity-90 text-white shadow-sm px-4 py-2 text-xs font-bold ${radius} transition-all`;
}
</script>

<template>
    <header ref="headerRef" :class="headerClasses" :style="headerGlassStyle">
        <div :class="barBoxClasses" :style="barBoxGlassStyle">
            
            <!-- LAYOUT 1: CLÁSICO Y MODERNO (Logo a la izquierda — Menú Derecha o Centro) -->
            <div
                v-if="!settings.header_layout || settings.header_layout === 'logo_left_menu_right' || settings.header_layout === 'logo_left_menu_center'"
                :class="['flex items-center justify-between transition-all duration-300', innerPaddingClass]"
            >
                <!-- Logotipo -->
                <Link href="/" @click="cerrarMenuMovilSuave" class="flex items-center gap-3 group shrink-0">
                    <template v-if="settings.logo_path || settings.logo_dark_path">
                        <img
                            v-if="!settings.logo_dark_path || settings.logo_dark_path === settings.logo_path"
                            :src="settings.logo_path || settings.logo_dark_path"
                            :alt="settings.site_name || 'Logo'"
                            width="160"
                            :height="settings.logo_height || 36"
                            class="site-logo w-auto object-contain transition-all duration-200 group-hover:scale-102"
                        />
                        <template v-else>
                            <img
                                :src="settings.logo_path"
                                :alt="settings.site_name || 'Logo'"
                                width="160"
                                :height="settings.logo_height || 36"
                                class="site-logo site-logo-light dark:hidden w-auto object-contain transition-all duration-200 group-hover:scale-102"
                            />
                            <img
                                :src="settings.logo_dark_path"
                                :alt="settings.site_name || 'Logo'"
                                width="160"
                                :height="settings.logo_height || 36"
                                class="site-logo site-logo-dark hidden dark:inline-block w-auto object-contain transition-all duration-200 group-hover:scale-102"
                            />
                        </template>
                    </template>
                    <span v-else :class="['text-xl font-extrabold tracking-tight group-hover:text-[var(--primary-color,#10b981)] transition-colors', navContrastClass]" style="font-family: var(--heading-font), system-ui, sans-serif;">
                        {{ settings.site_name || 'CMS' }}
                    </span>
                </Link>

                <!-- Menú Centrado u Ordenado a la Derecha -->
                <nav
                    v-if="menuItemsActivos.length && !isMobilePreview"
                    :class="[
                        'hidden md:flex items-center gap-1 lg:gap-2',
                        settings.header_layout === 'logo_left_menu_center' ? 'mx-auto' : 'ms-auto me-2 sm:me-4'
                    ]"
                >
                    <div v-for="item in menuItemsActivos" :key="item.id" class="relative group">
                        <!-- Enlace Principal (Sin hijos) -->
                        <template v-if="!item.children || !item.children.length">
                            <Link
                                v-if="esNavegacionInertia(item.resolved_url)"
                                :href="getRutaInterna(item.resolved_url)"
                                :rel="getRel(item)"
                                prefetch="hover"
                                :class="getItemClass(item.resolved_url)"
                            >
                                <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                <span>{{ item.title }}</span>
                            </Link>
                            <a
                                v-else
                                :href="item.resolved_url"
                                :target="item.target || '_blank'"
                                :rel="getRel(item, true)"
                                :class="getItemClass(item.resolved_url)"
                            >
                                <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                <span>{{ item.title }}</span>
                            </a>
                        </template>

                        <!-- Desplegable (Dropdown Nivel 2) -->
                        <template v-else>
                            <button
                                type="button"
                                :class="getItemClass(item.resolved_url, true)"
                            >
                                <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                <span>{{ item.title }}</span>
                                <i class="pi pi-chevron-down text-[10px] text-zinc-400 group-hover:rotate-180 transition-transform duration-200" />
                            </button>

                            <!-- Panel Flotante del Desplegable -->
                            <div class="absolute left-0 top-full pt-2 opacity-0 translate-y-2 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-200 z-50 min-w-[200px]">
                                <div class="p-2 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-xl backdrop-blur-lg space-y-1">
                                    <template v-for="child in item.children" :key="child.id">
                                        <Link
                                            v-if="child.is_active && esNavegacionInertia(child.resolved_url)"
                                            :href="getRutaInterna(child.resolved_url)"
                                            :rel="getRel(child)"
                                            class="block px-3 py-2 rounded-lg text-xs font-semibold text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors flex items-center gap-2"
                                        >
                                            <i v-if="child.icon" :class="child.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                            <span>{{ child.title }}</span>
                                        </Link>
                                        <a
                                            v-else-if="child.is_active"
                                            :href="child.resolved_url"
                                            :target="child.target || '_blank'"
                                            :rel="getRel(child, true)"
                                            class="block px-3 py-2 rounded-lg text-xs font-semibold text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors flex items-center gap-2"
                                        >
                                            <i v-if="child.icon" :class="child.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                            <span>{{ child.title }}</span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </nav>

                <!-- Acciones a la Derecha (Botón CTA + Selector Tema + Hamburguesa) -->
                <div class="flex items-center gap-3 shrink-0">
                    <!-- Botones Dinámicos en Escritorio (ocultos en móvil < md) -->
                    <template v-if="!isMobilePreview">
                        <template v-for="btn in headerButtonsEscritorio" :key="btn.id">
                            <Link
                                v-if="esNavegacionInertia(btn.url)"
                                :href="getRutaInterna(btn.url)"
                                :class="['hidden md:inline-flex items-center justify-center gap-2', getButtonVariantClass(btn)]"
                                :title="btn.text || undefined"
                                :aria-label="(!btn.text || btn.variant === 'icon_only') ? (btn.text || btn.url) : undefined"
                            >
                                <i v-if="btn.icon" :class="btn.icon" aria-hidden="true" />
                                <span v-if="btn.text && btn.variant !== 'icon_only'">{{ btn.text }}</span>
                            </Link>
                            <a
                                v-else
                                :href="btn.url || '#'"
                                :target="btn.target || '_self'"
                                :class="['hidden md:inline-flex items-center justify-center gap-2', getButtonVariantClass(btn)]"
                                :title="btn.text || undefined"
                                :aria-label="(!btn.text || btn.variant === 'icon_only') ? (btn.text || btn.url) : undefined"
                            >
                                <i v-if="btn.icon" :class="btn.icon" aria-hidden="true" />
                                <span v-if="btn.text && btn.variant !== 'icon_only'">{{ btn.text }}</span>
                            </a>
                        </template>
                    </template>

                    <!-- Botones Dinámicos en Barra Móvil (visibles solo en móvil < md o en preview móvil) -->
                    <template v-for="btn in headerButtonsMovilBarra" :key="'mob_bar_' + btn.id">
                        <Link
                            v-if="esNavegacionInertia(btn.url)"
                            :href="getRutaInterna(btn.url)"
                            :class="[isMobilePreview ? 'inline-flex' : 'md:hidden inline-flex', 'items-center justify-center gap-1.5', getButtonVariantClass(btn, !btn.text || btn.variant === 'icon_only')]"
                            :title="btn.text || undefined"
                            :aria-label="(!btn.text || btn.variant === 'icon_only') ? (btn.text || btn.url) : undefined"
                        >
                            <i v-if="btn.icon" :class="btn.icon" aria-hidden="true" />
                            <span v-if="btn.text && btn.variant !== 'icon_only'" class="text-xs">{{ btn.text }}</span>
                        </Link>
                        <a
                            v-else
                            :href="btn.url || '#'"
                            :target="btn.target || '_self'"
                            :class="[isMobilePreview ? 'inline-flex' : 'md:hidden inline-flex', 'items-center justify-center gap-1.5', getButtonVariantClass(btn, !btn.text || btn.variant === 'icon_only')]"
                            :title="btn.text || undefined"
                            :aria-label="(!btn.text || btn.variant === 'icon_only') ? (btn.text || btn.url) : undefined"
                        >
                            <i v-if="btn.icon" :class="btn.icon" aria-hidden="true" />
                            <span v-if="btn.text && btn.variant !== 'icon_only'" class="text-xs">{{ btn.text }}</span>
                        </a>
                    </template>
                    <!-- Redes Sociales en Escritorio y Tablet -->
                    <div v-if="(settings.header_show_socials ?? true) && settings.social_links && !isMobilePreview" class="hidden md:flex items-center gap-1 border-s border-zinc-200 dark:border-zinc-800 ps-2.5 ms-1">
                        <a v-if="settings.social_links.github" :href="settings.social_links.github" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="GitHub" aria-label="GitHub">
                            <i class="pi pi-github text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.twitter" :href="settings.social_links.twitter" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Twitter / X" aria-label="Twitter">
                            <i class="pi pi-twitter text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.facebook" :href="settings.social_links.facebook" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Facebook" aria-label="Facebook">
                            <i class="pi pi-facebook text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.instagram" :href="settings.social_links.instagram" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Instagram" aria-label="Instagram">
                            <i class="pi pi-instagram text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.linkedin" :href="settings.social_links.linkedin" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="LinkedIn" aria-label="LinkedIn">
                            <i class="pi pi-linkedin text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.youtube" :href="settings.social_links.youtube" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="YouTube" aria-label="YouTube">
                            <i class="pi pi-youtube text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.whatsapp" :href="settings.social_links.whatsapp" target="_blank" rel="noopener noreferrer" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="WhatsApp" aria-label="WhatsApp">
                            <i class="pi pi-whatsapp text-sm" aria-hidden="true" />
                        </a>
                    </div>

                    <!-- Conmutador de Tema (user_choice) -->
                    <button
                        v-if="permiteEleccionUsuario"
                        type="button"
                        @click="toggleModoOscuroPublico"
                        :class="[
                            'p-2 rounded-xl text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white hover:bg-zinc-100/80 dark:hover:bg-zinc-800/80 transition-all cursor-pointer flex items-center justify-center shrink-0',
                            navContrastButtonClass
                        ]"
                        :title="esModoOscuroPublico ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                    >
                        <i :class="esModoOscuroPublico ? 'pi pi-sun' : 'pi pi-moon'" class="text-base text-zinc-700 dark:text-zinc-200 transition-transform duration-300 hover:scale-110" />
                    </button>

                    <!-- Botón Hamburguesa Móvil -->
                    <button
                        v-if="menuItemsActivos.length"
                        type="button"
                        @click="toggleMenuMovil"
                        :class="[
                            isMobilePreview ? 'flex' : 'md:hidden flex',
                            'p-2 rounded-xl text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white hover:bg-zinc-100/80 dark:hover:bg-zinc-800/80 transition-all cursor-pointer items-center justify-center shrink-0',
                            navContrastButtonClass
                        ]"
                        aria-label="Abrir menú"
                    >
                        <i :class="[menuMovilAbierto ? 'pi pi-times' : 'pi pi-bars', 'text-lg']" />
                    </button>
                </div>
            </div>

            <!-- LAYOUT 3: SPLIT PREMUM (Logo al centro con Menú dividido 50% izq / 50% der) -->
            <div
                v-else-if="settings.header_layout === 'logo_center_menu_split'"
                :class="['hidden md:flex items-center justify-between min-h-20 transition-all duration-300', innerPaddingClass]"
            >
                <!-- Menú Izquierda -->
                <nav class="flex items-center gap-2 flex-1 justify-end pe-6">
                    <div v-for="item in menuItemsSplit.izq" :key="item.id" class="relative group">
                        <Link
                            v-if="esNavegacionInertia(item.resolved_url)"
                            :href="getRutaInterna(item.resolved_url)"
                            :rel="getRel(item)"
                            :class="getItemClass(item.resolved_url)"
                        >
                            <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                            <span>{{ item.title }}</span>
                        </Link>
                        <a
                            v-else
                            :href="item.resolved_url"
                            :target="item.target || '_blank'"
                            :rel="getRel(item, true)"
                            :class="getItemClass(item.resolved_url)"
                        >
                            <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                            <span>{{ item.title }}</span>
                        </a>
                    </div>
                </nav>

                <!-- Logo Centrado -->
                <Link href="/" @click="cerrarMenuMovilSuave" class="flex items-center justify-center shrink-0 group">
                    <template v-if="settings.logo_path || settings.logo_dark_path">
                        <img
                            v-if="!settings.logo_dark_path || settings.logo_dark_path === settings.logo_path"
                            :src="settings.logo_path || settings.logo_dark_path"
                            :alt="settings.site_name || 'Logo'"
                            width="180"
                            :height="settings.logo_height || 40"
                            class="site-logo w-auto object-contain transition-all duration-200 group-hover:scale-105"
                        />
                        <template v-else>
                            <img
                                :src="settings.logo_path"
                                :alt="settings.site_name || 'Logo'"
                                width="180"
                                :height="settings.logo_height || 40"
                                class="site-logo site-logo-light dark:hidden w-auto object-contain transition-all duration-200 group-hover:scale-105"
                            />
                            <img
                                :src="settings.logo_dark_path"
                                :alt="settings.site_name || 'Logo'"
                                width="180"
                                :height="settings.logo_height || 40"
                                class="site-logo site-logo-dark hidden dark:inline-block w-auto object-contain transition-all duration-200 group-hover:scale-105"
                            />
                        </template>
                    </template>
                    <span v-else :class="['text-2xl font-black tracking-tight group-hover:text-[var(--primary-color,#10b981)] transition-colors', navContrastClass]" style="font-family: var(--heading-font), system-ui, sans-serif;">
                        {{ settings.site_name || 'CMS' }}
                    </span>
                </Link>

                <!-- Menú Derecha + CTA -->
                <div class="flex items-center justify-start flex-1 ps-6 gap-3">
                    <nav class="flex items-center gap-2">
                        <div v-for="item in menuItemsSplit.der" :key="item.id" class="relative group">
                            <Link
                                v-if="esNavegacionInertia(item.resolved_url)"
                                :href="getRutaInterna(item.resolved_url)"
                                :rel="getRel(item)"
                                :class="getItemClass(item.resolved_url)"
                            >
                                <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                <span>{{ item.title }}</span>
                            </Link>
                            <a
                                v-else
                                :href="item.resolved_url"
                                :target="item.target || '_blank'"
                                :rel="getRel(item, true)"
                                :class="getItemClass(item.resolved_url)"
                            >
                                <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                <span>{{ item.title }}</span>
                            </a>
                        </div>
                    </nav>

                    <!-- Botones Dinámicos -->
                    <template v-for="btn in headerButtonsEscritorio" :key="btn.id">
                        <Link
                            v-if="esNavegacionInertia(btn.url)"
                            :href="getRutaInterna(btn.url)"
                            :class="['inline-flex items-center gap-2 ms-auto', getButtonVariantClass(btn)]"
                            :title="btn.text || undefined"
                        >
                            <i v-if="btn.icon" :class="btn.icon" />
                            <span v-if="btn.text && btn.variant !== 'icon_only'">{{ btn.text }}</span>
                        </Link>
                        <a
                            v-else
                            :href="btn.url || '#'"
                            :target="btn.target || '_self'"
                            :class="['inline-flex items-center gap-2 ms-auto', getButtonVariantClass(btn)]"
                            :title="btn.text || undefined"
                        >
                            <i v-if="btn.icon" :class="btn.icon" />
                            <span v-if="btn.text && btn.variant !== 'icon_only'">{{ btn.text }}</span>
                        </a>
                    </template>

                    <!-- Redes Sociales en Escritorio y Tablet -->
                    <div v-if="(settings.header_show_socials ?? true) && settings.social_links && !isMobilePreview" class="hidden md:flex items-center gap-1 border-s border-zinc-200 dark:border-zinc-800 ps-2 ms-1">
                        <a v-if="settings.social_links.github" :href="settings.social_links.github" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="GitHub" aria-label="GitHub">
                            <i class="pi pi-github text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.twitter" :href="settings.social_links.twitter" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Twitter / X" aria-label="Twitter">
                            <i class="pi pi-twitter text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.facebook" :href="settings.social_links.facebook" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Facebook" aria-label="Facebook">
                            <i class="pi pi-facebook text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.instagram" :href="settings.social_links.instagram" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Instagram" aria-label="Instagram">
                            <i class="pi pi-instagram text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.linkedin" :href="settings.social_links.linkedin" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="LinkedIn" aria-label="LinkedIn">
                            <i class="pi pi-linkedin text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.youtube" :href="settings.social_links.youtube" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="YouTube" aria-label="YouTube">
                            <i class="pi pi-youtube text-sm" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.whatsapp" :href="settings.social_links.whatsapp" target="_blank" rel="noopener noreferrer" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="WhatsApp" aria-label="WhatsApp">
                            <i class="pi pi-whatsapp text-sm" aria-hidden="true" />
                        </a>
                    </div>

                    <!-- Conmutador Tema -->
                    <button
                        v-if="permiteEleccionUsuario"
                        type="button"
                        @click="toggleModoOscuroPublico"
                        class="p-2 rounded-xl text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white hover:bg-zinc-100/80 dark:hover:bg-zinc-800/80 transition-all cursor-pointer flex items-center justify-center shrink-0"
                    >
                        <i :class="esModoOscuroPublico ? 'pi pi-sun' : 'pi pi-moon'" class="text-sm text-zinc-700 dark:text-zinc-200" />
                    </button>
                </div>
            </div>

            <!-- LAYOUT 4: EDITORIAL / DOBLE BARRA (Logo Arriba — Menú Abajo) -->
            <div v-else-if="settings.header_layout === 'logo_top_menu_bottom'" class="hidden md:block py-3 px-6">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-200/60 dark:border-zinc-800/60">
                    <div class="flex items-center gap-1 min-w-[96px]">
                        <div v-if="(settings.header_show_socials ?? true) && settings.social_links && !isMobilePreview" class="hidden md:flex items-center gap-1">
                            <a v-if="settings.social_links.github" :href="settings.social_links.github" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="GitHub" aria-label="GitHub">
                                <i class="pi pi-github text-sm" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.twitter" :href="settings.social_links.twitter" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Twitter / X" aria-label="Twitter">
                                <i class="pi pi-twitter text-sm" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.facebook" :href="settings.social_links.facebook" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Facebook" aria-label="Facebook">
                                <i class="pi pi-facebook text-sm" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.instagram" :href="settings.social_links.instagram" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="Instagram" aria-label="Instagram">
                                <i class="pi pi-instagram text-sm" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.linkedin" :href="settings.social_links.linkedin" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="LinkedIn" aria-label="LinkedIn">
                                <i class="pi pi-linkedin text-sm" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.youtube" :href="settings.social_links.youtube" target="_blank" rel="noopener" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="YouTube" aria-label="YouTube">
                                <i class="pi pi-youtube text-sm" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.whatsapp" :href="settings.social_links.whatsapp" target="_blank" rel="noopener noreferrer" class="p-1.5 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors" title="WhatsApp" aria-label="WhatsApp">
                                <i class="pi pi-whatsapp text-sm" aria-hidden="true" />
                            </a>
                        </div>
                    </div>
                    <Link href="/" @click="cerrarMenuMovilSuave" class="flex items-center justify-center group">
                        <template v-if="settings.logo_path || settings.logo_dark_path">
                            <img
                                v-if="!settings.logo_dark_path || settings.logo_dark_path === settings.logo_path"
                                :src="settings.logo_path || settings.logo_dark_path"
                                :alt="settings.site_name || 'Logo'"
                                width="200"
                                :height="settings.logo_height || 44"
                                class="site-logo w-auto object-contain transition-all duration-200"
                            />
                            <template v-else>
                                <img
                                    :src="settings.logo_path"
                                    :alt="settings.site_name || 'Logo'"
                                    width="200"
                                    :height="settings.logo_height || 44"
                                    class="site-logo site-logo-light dark:hidden w-auto object-contain transition-all duration-200"
                                />
                                <img
                                    :src="settings.logo_dark_path"
                                    :alt="settings.site_name || 'Logo'"
                                    width="200"
                                    :height="settings.logo_height || 44"
                                    class="site-logo site-logo-dark hidden dark:inline-block w-auto object-contain transition-all duration-200"
                                />
                            </template>
                        </template>
                        <span v-else class="text-2xl font-black tracking-tight" style="font-family: var(--heading-font), system-ui, sans-serif;">
                            {{ settings.site_name || 'CMS' }}
                        </span>
                    </Link>

                    <div class="flex items-center gap-2">
                        <template v-for="btn in headerButtonsEscritorio" :key="btn.id">
                            <Link
                                v-if="esNavegacionInertia(btn.url)"
                                :href="getRutaInterna(btn.url)"
                                :class="['inline-flex items-center gap-2', getButtonVariantClass(btn)]"
                                :title="btn.text || undefined"
                            >
                                <i v-if="btn.icon" :class="btn.icon" />
                                <span v-if="btn.text && btn.variant !== 'icon_only'">{{ btn.text }}</span>
                            </Link>
                            <a
                                v-else
                                :href="btn.url || '#'"
                                :target="btn.target || '_self'"
                                :class="['inline-flex items-center gap-2', getButtonVariantClass(btn)]"
                                :title="btn.text || undefined"
                            >
                                <i v-if="btn.icon" :class="btn.icon" />
                                <span v-if="btn.text && btn.variant !== 'icon_only'">{{ btn.text }}</span>
                            </a>
                        </template>
                        <button v-if="permiteEleccionUsuario" type="button" @click="toggleModoOscuroPublico" class="p-2 rounded-lg text-zinc-700 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 text-xs">
                            <i :class="esModoOscuroPublico ? 'pi pi-sun' : 'pi pi-moon'" class="text-zinc-700 dark:text-zinc-200" />
                        </button>
                    </div>
                </div>

                <nav class="flex items-center justify-center gap-6 pt-2">
                    <div v-for="item in menuItemsActivos" :key="item.id">
                        <Link
                            v-if="esNavegacionInertia(item.resolved_url)"
                            :href="getRutaInterna(item.resolved_url)"
                            :rel="getRel(item)"
                            class="header-nav-link text-xs font-bold uppercase tracking-wider transition-colors"
                        >
                            {{ item.title }}
                        </Link>
                        <a v-else :href="item.resolved_url" :target="item.target || '_self'" :rel="getRel(item, item.target === '_blank')" class="header-nav-link text-xs font-bold uppercase tracking-wider transition-colors">
                            {{ item.title }}
                        </a>
                    </div>
                </nav>
            </div>

            <!-- MODO A: TARJETA FLOTANTE SOBRE EL CONTENIDO (mobile_menu_style === 'dropdown') -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform -translate-y-4 opacity-0 scale-95"
                enter-to-class="transform translate-y-0 opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-y-0 opacity-100 scale-100"
                leave-to-class="transform -translate-y-4 opacity-0 scale-95"
            >
                <div
                    v-if="menuMovilAbierto && settings.mobile_menu_style === 'dropdown'"
                    class="absolute top-[calc(100%+0.6rem)] left-0 right-0 z-50 p-4 rounded-2xl bg-white/95 dark:bg-zinc-950/95 backdrop-blur-2xl border border-zinc-200/90 dark:border-zinc-800/90 shadow-2xl space-y-3 pointer-events-auto"
                >
                    <div class="space-y-1">
                        <div v-for="item in menuItemsActivos" :key="item.id">
                            <template v-if="!item.children || !item.children.length">
                                <Link
                                    v-if="esNavegacionInertia(item.resolved_url)"
                                    :href="getRutaInterna(item.resolved_url)"
                                    :rel="getRel(item)"
                                    @click="cerrarMenuMovilSuave"
                                    :class="getMobileItemClass(item.resolved_url)"
                                >
                                    <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                    <span>{{ item.title }}</span>
                                </Link>
                                <a
                                    v-else
                                    :href="item.resolved_url"
                                    :target="item.target || '_blank'"
                                    :rel="getRel(item, true)"
                                    @click="cerrarMenuMovilSuave"
                                    :class="getMobileItemClass(item.resolved_url)"
                                >
                                    <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                    <span>{{ item.title }}</span>
                                </a>
                            </template>

                            <template v-else>
                                <button
                                    type="button"
                                    @click="toggleSubmenuMovil(item.id)"
                                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl font-semibold text-sm text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100/80 dark:hover:bg-zinc-900/80 transition-colors text-left"
                                >
                                    <div class="flex items-center gap-3">
                                        <i v-if="item.icon" :class="item.icon" class="text-xs text-[var(--primary-color,#10b981)]" />
                                        <span>{{ item.title }}</span>
                                    </div>
                                    <i :class="[submenusAbiertos[item.id] ? 'rotate-180' : '', 'pi pi-chevron-down text-xs transition-transform duration-200 text-zinc-400']" />
                                </button>

                                <div v-if="submenusAbiertos[item.id]" class="ps-6 space-y-1 border-l-2 border-zinc-200 dark:border-zinc-800 ms-3 my-1">
                                    <template v-for="child in item.children" :key="child.id">
                                        <Link
                                            v-if="child.is_active && esNavegacionInertia(child.resolved_url)"
                                            :href="getRutaInterna(child.resolved_url)"
                                            :rel="getRel(child)"
                                            @click="cerrarMenuMovilSuave"
                                            class="block px-3 py-2 rounded-lg text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                        >
                                            {{ child.title }}
                                        </Link>
                                        <a
                                            v-else-if="child.is_active"
                                            :href="child.resolved_url"
                                            :target="child.target || '_blank'"
                                            :rel="getRel(child, true)"
                                            @click="cerrarMenuMovilSuave"
                                            class="block px-3 py-2 rounded-lg text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                        >
                                            {{ child.title }}
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div v-if="headerButtonsMovilMenu.length" class="pt-2.5 border-t border-zinc-200 dark:border-zinc-800 space-y-2">
                        <!-- Botones CTA con texto (Ancho completo) -->
                        <template v-for="btn in headerButtonsMovilConTexto" :key="btn.id">
                            <Link
                                v-if="esNavegacionInertia(btn.url)"
                                :href="getRutaInterna(btn.url)"
                                @click="cerrarMenuMovilSuave"
                                :class="['w-full flex items-center justify-center gap-2', getButtonVariantClass(btn)]"
                                :title="btn.text"
                            >
                                <i v-if="btn.icon" :class="btn.icon" />
                                <span>{{ btn.text }}</span>
                            </Link>
                            <a
                                v-else
                                :href="btn.url || '#'"
                                :target="btn.target || '_self'"
                                @click="cerrarMenuMovilSuave"
                                :class="['w-full flex items-center justify-center gap-2', getButtonVariantClass(btn)]"
                                :title="btn.text"
                            >
                                <i v-if="btn.icon" :class="btn.icon" />
                                <span>{{ btn.text }}</span>
                            </a>
                        </template>

                        <!-- Botones de solo icono (WhatsApp, Teléfono, etc.) centrados y proporcionados -->
                        <div v-if="headerButtonsMovilSoloIcono.length" class="flex items-center justify-center gap-2.5 pt-1">
                            <template v-for="btn in headerButtonsMovilSoloIcono" :key="btn.id">
                                <Link
                                    v-if="esNavegacionInertia(btn.url)"
                                    :href="getRutaInterna(btn.url)"
                                    @click="cerrarMenuMovilSuave"
                                    :class="['inline-flex items-center justify-center', getButtonVariantClass(btn, true)]"
                                    :title="btn.text || btn.url"
                                    :aria-label="btn.text || btn.url"
                                >
                                    <i v-if="btn.icon" :class="[btn.icon, 'text-base']" aria-hidden="true" />
                                </Link>
                                <a
                                    v-else
                                    :href="btn.url || '#'"
                                    :target="btn.target || '_self'"
                                    @click="cerrarMenuMovilSuave"
                                    :class="['inline-flex items-center justify-center', getButtonVariantClass(btn, true)]"
                                    :title="btn.text || btn.url"
                                    :aria-label="btn.text || btn.url"
                                >
                                    <i v-if="btn.icon" :class="[btn.icon, 'text-base']" aria-hidden="true" />
                                </a>
                            </template>
                        </div>
                    </div>

                    <!-- Redes Sociales en Menú Móvil Desplegable -->
                    <div v-if="(settings.mobile_show_socials ?? true) && settings.social_links" class="pt-3 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-center gap-3">
                        <a v-if="settings.social_links.github" :href="settings.social_links.github" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500 transition-colors" title="GitHub" aria-label="GitHub">
                            <i class="pi pi-github text-base" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.twitter" :href="settings.social_links.twitter" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500 transition-colors" title="Twitter / X" aria-label="Twitter">
                            <i class="pi pi-twitter text-base" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.facebook" :href="settings.social_links.facebook" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500 transition-colors" title="Facebook" aria-label="Facebook">
                            <i class="pi pi-facebook text-base" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.instagram" :href="settings.social_links.instagram" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500 transition-colors" title="Instagram" aria-label="Instagram">
                            <i class="pi pi-instagram text-base" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.linkedin" :href="settings.social_links.linkedin" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500 transition-colors" title="LinkedIn" aria-label="LinkedIn">
                            <i class="pi pi-linkedin text-base" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.youtube" :href="settings.social_links.youtube" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500 transition-colors" title="YouTube" aria-label="YouTube">
                            <i class="pi pi-youtube text-base" aria-hidden="true" />
                        </a>
                        <a v-if="settings.social_links.whatsapp" :href="settings.social_links.whatsapp" target="_blank" rel="noopener noreferrer" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500 transition-colors" title="WhatsApp" aria-label="WhatsApp">
                            <i class="pi pi-whatsapp text-base" aria-hidden="true" />
                        </a>
                    </div>
                </div>
            </Transition>

        </div>

        <!-- 5. MENÚ MÓVIL CAJÓN LATERAL (OFF-CANVAS DRAWER) O PANTALLA COMPLETA (FULLSCREEN) -->
        <Teleport to="body">
            <div v-if="menuMovilAbierto && settings.mobile_menu_style !== 'dropdown'" class="fixed inset-0 z-50 flex">
                <!-- Overlay de Fondo Esmerilado -->
                <div
                    @click="menuMovilAbierto = false"
                    class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300"
                />

                <!-- Panel del Menú Móvil -->
                <div
                    :class="[
                        'relative w-full max-w-sm bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 shadow-2xl flex flex-col justify-between p-6 z-10 transition-transform duration-300',
                        settings.mobile_menu_style === 'drawer_left' ? 'me-auto border-r border-zinc-200 dark:border-zinc-800' : 'ms-auto border-l border-zinc-200 dark:border-zinc-800',
                        settings.mobile_menu_style === 'fullscreen' ? 'max-w-none inset-0 p-8' : ''
                    ]"
                >
                    <!-- Cabecera del Cajón Móvil -->
                    <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800">
                        <Link href="/" @click="cerrarMenuMovilSuave" class="flex items-center gap-2">
                            <template v-if="settings.logo_path || settings.logo_dark_path">
                                <img
                                    v-if="!settings.logo_dark_path || settings.logo_dark_path === settings.logo_path"
                                    :src="settings.logo_path || settings.logo_dark_path"
                                    :alt="settings.site_name || 'Logo'"
                                    class="site-logo site-logo-drawer w-auto object-contain"
                                />
                                <template v-else>
                                    <img
                                        :src="settings.logo_path"
                                        :alt="settings.site_name || 'Logo'"
                                        class="site-logo site-logo-light site-logo-drawer dark:hidden w-auto object-contain"
                                    />
                                    <img
                                        :src="settings.logo_dark_path"
                                        :alt="settings.site_name || 'Logo'"
                                        class="site-logo site-logo-dark site-logo-drawer hidden dark:inline-block w-auto object-contain"
                                    />
                                </template>
                            </template>
                            <span v-else class="font-bold text-lg">{{ settings.site_name || 'CMS' }}</span>
                        </Link>

                        <button
                            @click="menuMovilAbierto = false"
                            class="p-2 rounded-xl bg-zinc-100 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 transition-colors cursor-pointer"
                        >
                            <i class="pi pi-times text-lg" />
                        </button>
                    </div>

                    <!-- Lista de Ítems Móvil (con Acordeón para Submenús) -->
                    <div class="py-6 overflow-y-auto space-y-1 flex-1">
                        <div v-for="item in menuItemsActivos" :key="item.id" class="space-y-1">
                            <!-- Ítem Sin Hijos -->
                            <template v-if="!item.children || !item.children.length">
                                <Link
                                    v-if="esNavegacionInertia(item.resolved_url)"
                                    :href="getRutaInterna(item.resolved_url)"
                                    :rel="getRel(item)"
                                    @click="cerrarMenuMovilSuave"
                                    :class="getMobileItemClass(item.resolved_url)"
                                >
                                    <i v-if="item.icon" :class="item.icon" class="text-sm text-[var(--primary-color,#10b981)]" />
                                    <span>{{ item.title }}</span>
                                </Link>
                                <a
                                    v-else
                                    :href="item.resolved_url"
                                    :target="item.target || '_blank'"
                                    :rel="getRel(item, true)"
                                    @click="cerrarMenuMovilSuave"
                                    :class="getMobileItemClass(item.resolved_url)"
                                >
                                    <i v-if="item.icon" :class="item.icon" class="text-sm text-[var(--primary-color,#10b981)]" />
                                    <span>{{ item.title }}</span>
                                </a>
                            </template>

                            <!-- Ítem Con Submenú Acordeón -->
                            <template v-else>
                                <button
                                    type="button"
                                    @click="toggleSubmenuMovil(item.id)"
                                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl font-semibold text-base text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors text-left"
                                >
                                    <div class="flex items-center gap-3">
                                        <i v-if="item.icon" :class="item.icon" class="text-sm text-[var(--primary-color,#10b981)]" />
                                        <span>{{ item.title }}</span>
                                    </div>
                                    <i :class="[submenusAbiertos[item.id] ? 'rotate-180' : '', 'pi pi-chevron-down text-xs transition-transform duration-200 text-zinc-400']" />
                                </button>

                                <div v-if="submenusAbiertos[item.id]" class="ps-6 space-y-1 border-l-2 border-zinc-200 dark:border-zinc-800 ms-3">
                                    <template v-for="child in item.children" :key="child.id">
                                        <Link
                                            v-if="child.is_active && esNavegacionInertia(child.resolved_url)"
                                            :href="getRutaInterna(child.resolved_url)"
                                            :rel="getRel(child)"
                                            @click="cerrarMenuMovilSuave"
                                            class="block px-3 py-2 rounded-lg text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                        >
                                            {{ child.title }}
                                        </Link>
                                        <a
                                            v-else-if="child.is_active"
                                            :href="child.resolved_url"
                                            :target="child.target || '_blank'"
                                            :rel="getRel(child, true)"
                                            @click="cerrarMenuMovilSuave"
                                            class="block px-3 py-2 rounded-lg text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white"
                                        >
                                            {{ child.title }}
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Pie del Menú Móvil (Botón CTA + Redes Sociales) -->
                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 space-y-4">
                        <template v-if="headerButtonsMovilMenu.length">
                            <!-- Botones CTA con texto (Ancho completo) -->
                            <template v-for="btn in headerButtonsMovilConTexto" :key="btn.id">
                                <Link
                                    v-if="esNavegacionInertia(btn.url)"
                                    :href="getRutaInterna(btn.url)"
                                    @click="cerrarMenuMovilSuave"
                                    :class="['w-full flex items-center justify-center gap-2', getButtonVariantClass(btn)]"
                                    :title="btn.text"
                                >
                                    <i v-if="btn.icon" :class="btn.icon" />
                                    <span>{{ btn.text }}</span>
                                </Link>
                                <a
                                    v-else
                                    :href="btn.url || '#'"
                                    :target="btn.target || '_self'"
                                    @click="cerrarMenuMovilSuave"
                                    :class="['w-full flex items-center justify-center gap-2', getButtonVariantClass(btn)]"
                                    :title="btn.text"
                                >
                                    <i v-if="btn.icon" :class="btn.icon" />
                                    <span>{{ btn.text }}</span>
                                </a>
                            </template>

                            <!-- Botones de solo icono (WhatsApp, Teléfono, etc.) centrados y proporcionados -->
                            <div v-if="headerButtonsMovilSoloIcono.length" class="flex items-center justify-center gap-2.5 pt-1">
                                <template v-for="btn in headerButtonsMovilSoloIcono" :key="btn.id">
                                    <Link
                                        v-if="esNavegacionInertia(btn.url)"
                                        :href="getRutaInterna(btn.url)"
                                        @click="cerrarMenuMovilSuave"
                                        :class="['inline-flex items-center justify-center', getButtonVariantClass(btn, true)]"
                                        :title="btn.text || btn.url"
                                        :aria-label="btn.text || btn.url"
                                    >
                                        <i v-if="btn.icon" :class="[btn.icon, 'text-base']" aria-hidden="true" />
                                    </Link>
                                    <a
                                        v-else
                                        :href="btn.url || '#'"
                                        :target="btn.target || '_self'"
                                        @click="cerrarMenuMovilSuave"
                                        :class="['inline-flex items-center justify-center', getButtonVariantClass(btn, true)]"
                                        :title="btn.text || btn.url"
                                        :aria-label="btn.text || btn.url"
                                    >
                                        <i v-if="btn.icon" :class="[btn.icon, 'text-base']" aria-hidden="true" />
                                    </a>
                                </template>
                            </div>
                        </template>

                        <!-- Redes Sociales si existen -->
                        <div v-if="(settings.mobile_show_socials ?? true) && settings.social_links" class="flex items-center justify-center gap-3 pt-2">
                            <a v-if="settings.social_links.github" :href="settings.social_links.github" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500" title="GitHub" aria-label="GitHub">
                                <i class="pi pi-github text-base" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.twitter" :href="settings.social_links.twitter" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500" title="Twitter" aria-label="Twitter">
                                <i class="pi pi-twitter text-base" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.instagram" :href="settings.social_links.instagram" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500" title="Instagram" aria-label="Instagram">
                                <i class="pi pi-instagram text-base" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.linkedin" :href="settings.social_links.linkedin" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500" title="LinkedIn" aria-label="LinkedIn">
                                <i class="pi pi-linkedin text-base" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.youtube" :href="settings.social_links.youtube" target="_blank" rel="noopener" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500" title="YouTube" aria-label="YouTube">
                                <i class="pi pi-youtube text-base" aria-hidden="true" />
                            </a>
                            <a v-if="settings.social_links.whatsapp" :href="settings.social_links.whatsapp" target="_blank" rel="noopener noreferrer" @click="cerrarMenuMovilSuave" class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:text-emerald-500" title="WhatsApp" aria-label="WhatsApp">
                                <i class="pi pi-whatsapp text-base" aria-hidden="true" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </header>
</template>

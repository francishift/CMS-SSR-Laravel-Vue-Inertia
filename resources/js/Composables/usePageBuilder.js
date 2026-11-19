import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import axios from 'axios';
import { GRID_PRESETS } from '@/Config/gridPresets';

/**
 * Composable para la gestión de Estado, Acciones y Mutaciones del Constructor Visual (Page Builder).
 * Desacopla la lógica de negocio, manipulación del árbol JSON y la sincronización con la API.
 */
export function usePageBuilder(props) {
    const page = usePage();
    const siteSettings = computed(() => page.props.siteSettings || {});
    const menuHeader = computed(() => page.props.menuHeader);
    const menuFooter = computed(() => page.props.menuFooter);

    // Detección de cambios no guardados en el diseño
    const layoutInicialJson = ref(JSON.stringify(props.pagina.layout_json || {}));
    const tieneCambiosPendientes = computed(() => {
        return JSON.stringify(layout.value) !== layoutInicialJson.value;
    });

    function uuid() {
        return 'id_' + Math.random().toString(36).substr(2, 9);
    }

    // Plantilla por defecto rica para evitar pantallas vacías
    const layoutPorDefecto = {
        version: '1.0',
        sections: [
            {
                id: 'sec_hero',
                name: 'Sección Cabecera Hero',
                settings: {
                    container: 'boxed',
                    padding: 'lg',
                    margin: 'none',
                    bg_preset: 'white',
                    bg_color: '',
                    bg_image: '',
                    bg_image_mobile: '',
                    bg_size: 'cover',
                    bg_position: 'center center',
                    bg_size_tablet: '',
                    bg_size_mobile: '',
                    bg_position_tablet: '',
                    bg_position_mobile: '',
                    bg_overlay: '',
                    bg_dark_mode: 'inherit',
                    transparent_header: false,
                    transparent_header_pull: false,
                    transparent_header_nav_color: 'inherit',
                    transparent_header_nav_color_dark: 'same',
                },
                rows: [
                    {
                        id: 'row_hero',
                        layout: '1/2+1/2',
                        gap: 'md',
                        align: 'center',
                        columns: [
                            {
                                id: 'col_hero_1',
                                width: { xs: '1/1', sm: 'inherit', md: 'inherit', lg: '1/2', xl: 'inherit' },
                                order: { xs: 'none', sm: 'none', md: 'none', lg: 'none', xl: 'none' },
                                elements: [
                                    {
                                        id: 'el_h1',
                                        type: 'heading',
                                        content: { text: props.pagina.title || 'Título de la Página', tag: 'h1', align: 'left' }
                                    },
                                    {
                                        id: 'el_p1',
                                        type: 'text',
                                        content: { html: '<p>Esta es una sección maquetada visualmente. Puedes personalizar márgenes, paddings, fondos y columnas desde el panel izquierdo.</p>' }
                                    },
                                    {
                                        id: 'el_btn1',
                                        type: 'button',
                                        content: { label: 'Explorar Contenidos', url: '/general', variant: 'primary' }
                                    }
                                ]
                            },
                            {
                                id: 'col_hero_2',
                                width: { xs: '1/1', sm: 'inherit', md: 'inherit', lg: '1/2', xl: 'inherit' },
                                order: { xs: 'none', sm: 'none', md: 'none', lg: 'none', xl: 'none' },
                                elements: [
                                    {
                                        id: 'el_grid1',
                                        type: 'grid_articles',
                                        content: {
                                            columns_desktop: 2,
                                            dynamic_binding: {
                                                source: 'articles',
                                                category_id: props.categorias[0]?.id || null,
                                                limit: 4
                                            }
                                        }
                                    }
                                ]
                            }
                        ]
                    }
                ]
            }
        ]
    };

    /**
     * Asegura que el content de cualquier elemento sea siempre un objeto ({}), nunca un Array ([]).
     * En JS, si content es un Array y se le asignan propiedades como content.items o content.mode,
     * JSON.stringify() las descarta por completo produciendo 'content: []' al guardar.
     */
    function normalizarElementoBuilder(elem) {
        if (!elem || typeof elem !== 'object') return;
        if (elem.type) {
            if (!elem.content || Array.isArray(elem.content) || typeof elem.content !== 'object') {
                elem.content = {};
            }
            if (elem.type === 'gallery') {
                if (!elem.content.mode) elem.content.mode = 'manual';
                if (!Array.isArray(elem.content.items)) elem.content.items = [];
                if (!elem.content.layout) elem.content.layout = 'grid';
                if (!elem.content.columns_desktop) elem.content.columns_desktop = 3;
                if (!elem.content.gap) elem.content.gap = 'md';
                if (!elem.content.gap_tablet) elem.content.gap_tablet = 'auto';
                if (!elem.content.gap_mobile) elem.content.gap_mobile = 'auto';
                if (!elem.content.image_ratio) elem.content.image_ratio = '16:9';
                if (!elem.content.image_fit) elem.content.image_fit = 'cover';
                if (!elem.content.rounded) elem.content.rounded = 'lg';
                if (!elem.content.carousel_rows) elem.content.carousel_rows = 1;
                if (!elem.content.carousel_speed) elem.content.carousel_speed = 60;
                if (!elem.content.carousel_direction) elem.content.carousel_direction = 'left';
                if (elem.content.carousel_pause_on_hover === undefined) elem.content.carousel_pause_on_hover = true;
                if (elem.content.carousel_fade_edges === undefined) elem.content.carousel_fade_edges = true;
                if (!elem.content.carousel_card_style) elem.content.carousel_card_style = 'plain';
                if (!elem.content.carousel_item_size) elem.content.carousel_item_size = 'md';
            }
        }
    }

    /**
     * Recorre recursivamente todas las secciones, filas, columnas y sublayouts normalizando
     * el content de cada elemento para garantizar la integridad de la persistencia JSON.
     */
    function normalizarArbolLayout(nodo) {
        if (!nodo || typeof nodo !== 'object') return;
        if (nodo.type && nodo.content !== undefined) {
            normalizarElementoBuilder(nodo);
        }
        if (Array.isArray(nodo.sections)) nodo.sections.forEach(normalizarArbolLayout);
        if (Array.isArray(nodo.rows)) nodo.rows.forEach(normalizarArbolLayout);
        if (Array.isArray(nodo.columns)) nodo.columns.forEach(normalizarArbolLayout);
        if (Array.isArray(nodo.elements)) {
            nodo.elements.forEach(el => {
                normalizarElementoBuilder(el);
                normalizarArbolLayout(el);
            });
        }
        if (nodo.content && Array.isArray(nodo.content.rows)) {
            nodo.content.rows.forEach(normalizarArbolLayout);
        }
    }

    const layoutInicial = (props.pagina?.layout_json && Array.isArray(props.pagina.layout_json.sections))
        ? props.pagina.layout_json
        : { version: '1.0', sections: [] };

    normalizarArbolLayout(layoutInicial);
    const layout = ref(JSON.parse(JSON.stringify(layoutInicial)));
    const layoutPreview = ref(JSON.parse(JSON.stringify(layout.value)));

    const tabActiva = ref('0');
    const elementoSeleccionado = ref(null);
    const elementoSeleccionadoTipo = ref(null); // 'section', 'row', 'element'
    const vistaDispositivo = ref('desktop');

    const anchuraPrevisualizacion = computed(() => {
        return {
            desktop: 'w-full border border-zinc-200 dark:border-zinc-800 shadow-sm',
            tablet: 'w-full max-w-[770px] mx-auto shadow-2xl rounded-2xl border border-zinc-300 dark:border-zinc-700 my-4',
            mobile: 'w-full max-w-[375px] mx-auto shadow-2xl rounded-2xl border border-zinc-300 dark:border-zinc-700 my-4',
        }[vistaDispositivo.value] || 'w-full';
    });

    const form = useForm({
        layout_json: layout.value,
        seo_title: props.pagina.seo_title || '',
        seo_description: props.pagina.seo_description || '',
        seo_og_image: props.pagina.seo_og_image || '',
        meta_robots: props.pagina.meta_robots || null,
    });

    const modalAddElementoAbierto = ref(false);
    const modalBibliotecaModo = ref('element'); // 'element' | 'section'
    const targetSectionIndex = ref(null);
    const columnaObjetivoId = ref(null);

    const catalogoElementos = [
        { type: 'heading', name: 'Título / Encabezado', icon: 'pi pi-heading', desc: 'Título principal o subtítulo (H1-H4)' },
        { type: 'text', name: 'Texto Párrafo', icon: 'pi pi-file-edit', desc: 'Bloque de texto enriquecido o HTML' },
        { type: 'image', name: 'Imagen Optimizada', icon: 'pi pi-image', desc: 'Imagen responsiva con caché' },
        { type: 'video', name: 'Vídeo HTML5 Optimizado', icon: 'pi pi-video', desc: 'Vídeo con streaming instantáneo y opciones avanzadas' },
        { type: 'button', name: 'Botón CTA', icon: 'pi pi-external-link', desc: 'Botón de llamada a la acción' },
        { type: 'icon', name: 'Icono', icon: 'pi pi-star', desc: 'Icono decorativo o interactivo individual' },
        { type: 'pagination', name: 'Paginación / Navegación', icon: 'pi pi-arrows-h', desc: 'Navegación entre artículos anterior/siguiente o paginación numérica' },
        { type: 'grid_articles', name: 'Grid Artículos (Campos Dinámicos)', icon: 'pi pi-th-large', desc: 'Muestra artículos en vivo de una categoría' },
        { type: 'accordion', name: 'Acordeón FAQ', icon: 'pi pi-list', desc: 'Desplegables de preguntas' },
        { type: 'menu', name: 'Menú de Navegación', icon: 'pi pi-compass', desc: 'Muestra un menú de navegación activo' },
        { type: 'social', name: 'Redes Sociales', icon: 'pi pi-share-alt', desc: 'Iconos de redes sociales configuradas' },
        { type: 'html', name: 'Código HTML Libre', icon: 'pi pi-code', desc: 'Código HTML o SVG personalizado' },
        { type: 'gallery', name: 'Galería de Imágenes', icon: 'pi pi-images', desc: 'Galería responsiva con lightbox y múltiples layouts' },
        { type: 'sublayout', name: 'Subdiseño / Sublayout', icon: 'pi pi-table', desc: 'Crea una cuadrícula interna con columnas para maquetar elementos lado a lado' },
        { type: 'map', name: 'Mapa Interactivo', icon: 'pi pi-map-marker', desc: 'Mapa con Google Maps u OpenStreetMap, estilos de color y múltiples marcadores' },
        { type: 'form', name: 'Formulario', icon: 'pi pi-inbox', desc: 'Formulario dinámico configurable con validación y envío seguro' },
    ];

    function getElementIcon(type) {
        return {
            heading: 'pi pi-heading',
            text: 'pi pi-file-edit',
            image: 'pi pi-image',
            video: 'pi pi-video',
            button: 'pi pi-external-link',
            icon: 'pi pi-star',
            pagination: 'pi pi-arrows-h',
            grid_articles: 'pi pi-th-large',
            accordion: 'pi pi-list',
            menu: 'pi pi-compass',
            social: 'pi pi-share-alt',
            html: 'pi pi-code',
            gallery: 'pi pi-images',
            sublayout: 'pi pi-table',
            map: 'pi pi-map-marker',
            form: 'pi pi-inbox',
        }[type] || 'pi pi-box';
    }

    function getElementLabel(type) {
        return {
            heading: 'Encabezado',
            text: 'Texto Párrafo',
            image: 'Imagen',
            video: 'Vídeo Optimizado',
            button: 'Botón CTA',
            icon: 'Icono',
            pagination: 'Paginación',
            grid_articles: 'Grid / Cuadrícula',
            accordion: 'Acordeón',
            menu: 'Menú de Navegación',
            social: 'Redes Sociales',
            html: 'Código HTML',
            gallery: 'Galería de Imágenes',
            sublayout: 'Subdiseño',
            map: 'Mapa Interactivo',
            form: 'Formulario',
        }[type] || type;
    }

    function obtenerNombreElemento(elem) {
        if (elem.admin_label && elem.admin_label.trim() !== '') {
            return elem.admin_label;
        }
        if (elem.type === 'heading') return elem.content?.text || 'Nuevo Encabezado';
        if (elem.type === 'button') return elem.content?.label || 'Botón CTA';
        if (elem.type === 'map') {
            const count = elem.content?.items?.length || 1;
            return `Mapa (${count} ${count === 1 ? 'punto' : 'puntos'})`;
        }
        if (elem.type === 'sublayout') {
            const lay = elem.content?.rows?.[0]?.layout || elem.content?.layout || '2 Cols';
            return `Subdiseño (${lay})`;
        }
        if (elem.type === 'icon') {
            const ic = (elem.content?.icon || '').replace('pi pi-', '').replace('pi ', '');
            return ic ? `Icono: ${ic}` : 'Icono';
        }
        if (elem.type === 'pagination') {
            const m = elem.content?.mode || 'article_nav';
            return m === 'archive_numeric' ? 'Paginación Numérica' : 'Navegación entre Artículos';
        }
        if (elem.type === 'menu') return 'Menú de Navegación';
        if (elem.type === 'social') return 'Redes Sociales';
        if (elem.type === 'video') return 'Vídeo Optimizado';
        if (elem.type === 'text') {
            const text = (elem.content?.html || '').replace(/<[^>]*>?/gm, '').trim();
            return text ? (text.length > 30 ? text.substring(0, 30) + '...' : text) : 'Texto Párrafo';
        }
        if (elem.type === 'grid_articles') {
            const mode = elem.content?.mode || 'dynamic';
            return mode === 'manual' ? 'Cuadrícula Manual' : (mode === 'mixed' ? 'Grid Mixto' : 'Grid de Artículos');
        }
        if (elem.type === 'image') return elem.content?.alt || 'Imagen Ilustrativa';
        if (elem.type === 'accordion') {
            const firstTitle = elem.content?.items?.[0]?.title;
            return firstTitle ? `Acordeón: ${firstTitle}` : 'Acordeón FAQ';
        }
        if (elem.type === 'html') return 'Código HTML Personalizado';
        if (elem.type === 'gallery') {
            const count = elem.content?.items?.length || 0;
            return count > 0 ? `Galería (${count} imágenes)` : 'Galería de Imágenes';
        }
        if (elem.type === 'form') {
            return elem.content?.form_slug ? `Formulario: ${elem.content.form_slug}` : 'Formulario';
        }
        return getElementLabel(elem.type);
    }

    const opcionesCategoriasConTodas = computed(() => [
        ...(props.esPlantilla ? [{ id: 'current', name: '⚡ Categoría Actual Dinámica (Contexto Activo)' }] : []),
        { id: null, name: 'Todas las Categorías (Artículos Recientes del Sitio)' },
        ...props.categorias,
    ]);

    const itemManualDragIndex = ref(null);

    function startDragItemManual(index) {
        itemManualDragIndex.value = index;
    }

    function dropItemManual(targetIndex) {
        if (itemManualDragIndex.value === null || itemManualDragIndex.value === targetIndex) return;
        const items = elementoSeleccionado.value?.content?.items;
        if (!items) return;

        const [movedItem] = items.splice(itemManualDragIndex.value, 1);
        items.splice(targetIndex, 0, movedItem);
        itemManualDragIndex.value = null;
    }

    function moverItemManual(index, direccion) {
        const items = elementoSeleccionado.value?.content?.items;
        if (!items) return;

        const targetIndex = index + direccion;
        if (targetIndex < 0 || targetIndex >= items.length) return;

        const [movedItem] = items.splice(index, 1);
        items.splice(targetIndex, 0, movedItem);
    }

    function añadirItemManualGrid() {
        if (!elementoSeleccionado.value) return;
        if (!elementoSeleccionado.value.content) {
            elementoSeleccionado.value.content = {};
        }
        if (!Array.isArray(elementoSeleccionado.value.content.items)) {
            elementoSeleccionado.value.content.items = [];
        }

        elementoSeleccionado.value.content.items.push({
            id: uuid(),
            title: 'Tarjeta Personalizada ' + (elementoSeleccionado.value.content.items.length + 1),
            description: 'Descripción detallada o resumen del servicio / característica.',
            badge: 'Destacado',
            icon: 'pi pi-star',
            image: '',
            url: '',
            button_text: 'Saber más',
        });
    }

    function eliminarItemManualGrid(index) {
        if (elementoSeleccionado.value?.content?.items) {
            elementoSeleccionado.value.content.items.splice(index, 1);
        }
    }

    function añadirItemAcordeon() {
        if (!elementoSeleccionado.value) return;
        if (!elementoSeleccionado.value.content) {
            elementoSeleccionado.value.content = {};
        }
        if (!Array.isArray(elementoSeleccionado.value.content.items)) {
            elementoSeleccionado.value.content.items = [];
        }
        elementoSeleccionado.value.content.items.push({
            title: 'Título ' + (elementoSeleccionado.value.content.items.length + 1),
            content: 'Descripción',
        });
    }

    function eliminarItemAcordeon(index) {
        if (elementoSeleccionado.value?.content?.items) {
            elementoSeleccionado.value.content.items.splice(index, 1);
        }
    }

    // Opciones predefinidas de contenedores y paddings
    const opcionesContenedor = [
        { label: 'Boxed (Centrado 1280px)', value: 'boxed' },
        { label: 'Pequeño (Centrado 768px)', value: 'small' },
        { label: 'Expandido Grande (1536px)', value: 'expand' },
        { label: 'Full Width con Margen (100% + px-6)', value: 'full_margin' },
        { label: 'Full Bleed Total (100% sin margen px-0)', value: 'full_bleed' },
    ];

    const opcionesContenedorFila = [
        { label: 'Heredar de la Sección (Por defecto)', value: 'inherit' },
        ...opcionesContenedor,
    ];

    const opcionesAltura = [
        { label: 'Ninguno (Automático según contenido)', value: 'none' },
        { label: 'Pixels (Altura fija en px)', value: 'pixels' },
        { label: 'Ventana gráfica / Viewport (100vh)', value: 'viewport' },
        { label: 'Viewport (Descontar Cabecera)', value: 'viewport_minus_header' },
        { label: 'Expandir Página al Viewport', value: 'expand' },
    ];

    const opcionesAlineacionVertical = [
        { label: 'Arriba (Top)', value: 'top' },
        { label: 'Centro (Center)', value: 'center' },
        { label: 'Abajo (Bottom)', value: 'bottom' },
    ];

    const opcionesColorTexto = [
        { label: 'Por Defecto (Heredado)', value: 'inherit' },
        { label: 'Claro (Forzar Texto Blanco)', value: 'light' },
        { label: 'Oscuro (Forzar Texto Negro)', value: 'dark' },
    ];

    const opcionesContrasteCabecera = [
        { label: 'Heredado / Por defecto', value: 'inherit' },
        { label: 'Texto Claro / Blanco (Fondos oscuros)', value: 'light' },
        { label: 'Texto Oscuro / Negro (Fondos claros)', value: 'dark' },
    ];

    const opcionesElementoHtml = [
        { label: 'section (Sección Estándar)', value: 'section' },
        { label: 'div (División genérica)', value: 'div' },
        { label: 'header (Encabezado de página/bloque)', value: 'header' },
        { label: 'footer (Pie de página/bloque)', value: 'footer' },
        { label: 'main (Contenido Principal)', value: 'main' },
        { label: 'article (Articulo/Noticia)', value: 'article' },
    ];

    const opcionesSticky = [
        { label: 'Sin Efecto (Desplazamiento normal)', value: 'none' },
        { label: 'Pegajoso - Cubierto por siguiente sección (Efecto Telón)', value: 'cover' },
        { label: 'Pegajoso Superior (Fijo siempre arriba)', value: 'fixed_top' },
    ];

    const opcionesPadding = [
        { label: 'Sin Padding (py-0)', value: 'none' },
        { label: 'Pequeño (py-6 / 24px)', value: 'sm' },
        { label: 'Mediano (py-12 / 48px)', value: 'md' },
        { label: 'Grande (py-20 / 80px)', value: 'lg' },
        { label: 'Extra Grande (py-28 / 112px)', value: 'xl' },
        { label: 'Gigante (py-36 / 144px)', value: '2xl' },
    ];

    const opcionesMargin = [
        { label: 'Sin Margen (my-0)', value: 'none' },
        { label: 'Pequeño (my-6)', value: 'sm' },
        { label: 'Mediano (my-12)', value: 'md' },
        { label: 'Grande (my-20)', value: 'lg' },
    ];

    const opcionesFondos = [
        { label: 'Transparente / Por Defecto', value: 'transparent' },
        { label: 'Blanco Puro (bg-white)', value: 'white' },
        { label: 'Gris Suave (bg-zinc-50)', value: 'zinc_50' },
        { label: 'Zinc Oscuro (bg-zinc-900)', value: 'zinc_900' },
        { label: 'Esmeralda Suave (bg-emerald-50)', value: 'emerald_subtle' },
        { label: 'Gradiente Oscuro Premium', value: 'gradient_dark' },
        { label: 'Gradiente Esmeralda', value: 'gradient_emerald' },
    ];

    const opcionesBgSize = [
        { label: 'Cover (Cubrir toda la sección)', value: 'cover' },
        { label: 'Contain (Ajustar imagen completa)', value: 'contain' },
        { label: 'Auto (Tamaño original en px)', value: 'auto' },
        { label: 'Ancho 100% (100% auto)', value: '100% auto' },
        { label: 'Alto 100% (auto 100%)', value: 'auto 100%' },
    ];

    const opcionesBgPosition = [
        { label: 'Centro (Centrado)', value: 'center center' },
        { label: 'Arriba Centro', value: 'top center' },
        { label: 'Abajo Centro', value: 'bottom center' },
        { label: 'Izquierda Centro', value: 'left center' },
        { label: 'Derecha Centro', value: 'right center' },
        { label: 'Arriba Izquierda', value: 'top left' },
        { label: 'Arriba Derecha', value: 'top right' },
        { label: 'Abajo Izquierda', value: 'bottom left' },
        { label: 'Abajo Derecha', value: 'bottom right' },
    ];

    const opcionesBgAttachment = [
        { label: 'Scroll (Desplazamiento normal)', value: 'scroll' },
        { label: 'Fixed (Fijo / Efecto Parallax)', value: 'fixed' },
    ];

    const opcionesBgRepeat = [
        { label: 'Sin Repetir (Imagen única)', value: 'no-repeat' },
        { label: 'Repetir Patrón (Mosaico X y Y)', value: 'repeat' },
        { label: 'Repetir Horizontal (Mosaico X)', value: 'repeat-x' },
        { label: 'Repetir Vertical (Mosaico Y)', value: 'repeat-y' },
    ];

    const opcionesBgBlendMode = [
        { label: 'Normal (Sin Fusión)', value: 'normal' },
        { label: 'Multiplicar (Oscurecer / Multiply)', value: 'multiply' },
        { label: 'Trama (Aclarar / Screen)', value: 'screen' },
        { label: 'Superponer (Contraste / Overlay)', value: 'overlay' },
        { label: 'Oscurecer (Darken)', value: 'darken' },
        { label: 'Aclarar (Lighten)', value: 'lighten' },
        { label: 'Luminosidad (Luminosity)', value: 'luminosity' },
    ];

    const opcionesBgFilter = [
        { label: 'Ninguno (Color Original)', value: 'none' },
        { label: 'Blanco y Negro (Grayscale)', value: 'grayscale' },
        { label: 'Desenfoque Suave (Blur 8px)', value: 'blur' },
        { label: 'Tono Sepia (Vintage)', value: 'sepia' },
        { label: 'Alto Contraste y Brillo', value: 'brightness_contrast' },
    ];

    const opcionesBgHideMobile = [
        { label: 'Mostrar en Todos los Dispositivos', value: 'none' },
        { label: 'Ocultar en Móviles (< 768px)', value: 'hidden_mobile' },
    ];

    const opcionesLayoutFila = GRID_PRESETS.map(p => ({
        label: p.label,
        value: p.id,
    }));

    const resolvingPreview = ref(false);

    async function refrescarPrevisualizacion() {
        resolvingPreview.value = true;
        try {
            const res = await axios.post(route('paginas.builder.preview'), {
                layout_json: layout.value,
            });
            if (res.data?.success) {
                layoutPreview.value = res.data.layout;
            }
        } catch (e) {
            layoutPreview.value = JSON.parse(JSON.stringify(layout.value));
        } finally {
            resolvingPreview.value = false;
        }
    }

    watch(layout, () => {
        refrescarPrevisualizacion();
    }, { deep: true });

    watch([() => props.pagina?.id, () => props.pagina?.layout_json], ([newId, newLayout], [oldId]) => {
        if (newLayout && Array.isArray(newLayout.sections)) {
            const pageChanged = newId !== oldId;
            const currentSelectedId = !pageChanged ? elementoSeleccionado.value?.id : null;
            const parsed = JSON.parse(JSON.stringify(newLayout));
            normalizarArbolLayout(parsed);
            layout.value = parsed;
            layoutInicialJson.value = JSON.stringify(parsed);

            if (pageChanged) {
                form.seo_title = props.pagina?.seo_title || '';
                form.seo_description = props.pagina?.seo_description || '';
                form.seo_og_image = props.pagina?.seo_og_image || '';
                form.meta_robots = props.pagina?.meta_robots || null;
            }

            const reconnectedElem = currentSelectedId ? buscarElementoPorId(currentSelectedId) : null;
            if (reconnectedElem) {
                elementoSeleccionado.value = reconnectedElem;
            } else if (layout.value.sections[0]?.rows?.[0]?.columns?.[0]?.elements?.[0]) {
                elementoSeleccionado.value = layout.value.sections[0].rows[0].columns[0].elements[0];
                elementoSeleccionadoTipo.value = 'element';
            } else if (layout.value.sections[0]) {
                elementoSeleccionado.value = layout.value.sections[0];
                elementoSeleccionadoTipo.value = 'section';
            } else {
                elementoSeleccionado.value = null;
                elementoSeleccionadoTipo.value = null;
            }
            refrescarPrevisualizacion();
        }
    }, { deep: true });

    onMounted(() => {
        if (layout.value.sections[0]?.rows[0]?.columns[0]?.elements[0]) {
            elementoSeleccionado.value = layout.value.sections[0].rows[0].columns[0].elements[0];
            elementoSeleccionadoTipo.value = 'element';
        }
        tabActiva.value = '0';
        refrescarPrevisualizacion();
    });

    /* ─── Acciones de Estructura ────────────────────────────────── */

    function añadirSeccion() {
        const nuevaSeccion = {
            id: uuid(),
            name: 'Sección ' + (layout.value.sections.length + 1),
            settings: {
                container: 'boxed',
                padding: 'md',
                margin: 'none',
                bg_preset: '',
                bg_color: '',
                bg_image: '',
                bg_image_mobile: '',
                bg_size: 'cover',
                bg_position: 'center center',
                bg_size_tablet: '',
                bg_size_mobile: '',
                bg_position_tablet: '',
                bg_position_mobile: '',
                bg_overlay: '',
                transparent_header: false,
                transparent_header_pull: false,
                transparent_header_nav_color: 'inherit',
            },
            rows: [
                {
                    id: uuid(),
                    layout: '1',
                    container: 'inherit',
                    gap: 'md',
                    align: 'start',
                    columns: [
                        {
                            id: uuid(),
                            width: { xs: '1/1', sm: 'inherit', md: 'inherit', lg: '1/1', xl: 'inherit' },
                            order: { xs: 'none', sm: 'none', md: 'none', lg: 'none', xl: 'none' },
                            elements: []
                        }
                    ]
                }
            ]
        };
        layout.value.sections.push(nuevaSeccion);
        seleccionarElemento(nuevaSeccion, 'section');
    }

    function eliminarSeccion(index) {
        if (!confirm) {
            layout.value.sections.splice(index, 1);
            elementoSeleccionado.value = null;
            return;
        }
        confirm.require({
            message: '¿Estás seguro de que deseas eliminar esta sección y todo su contenido?',
            header: 'Confirmar Eliminación de Sección',
            icon: 'pi pi-exclamation-triangle',
            acceptProps: { label: 'Eliminar Sección', severity: 'danger' },
            rejectProps: { label: 'Cancelar', severity: 'secondary', outlined: true },
            accept: () => {
                layout.value.sections.splice(index, 1);
                elementoSeleccionado.value = null;
            }
        });
    }

    function añadirFila(seccion) {
        const nuevaFila = {
            id: uuid(),
            layout: '1',
            container: 'inherit',
            gap: 'md',
            align: 'start',
            columns: [
                {
                    id: uuid(),
                    width: { xs: '1/1', sm: 'inherit', md: 'inherit', lg: '1/1', xl: 'inherit' },
                    order: { xs: 'none', sm: 'none', md: 'none', lg: 'none', xl: 'none' },
                    elements: []
                }
            ]
        };
        seccion.rows.push(nuevaFila);
        seleccionarElemento(nuevaFila, 'row');
    }

    function eliminarFila(sec, rIdx) {
        if (!confirm) {
            sec.rows.splice(rIdx, 1);
            elementoSeleccionado.value = null;
            return;
        }
        confirm.require({
            message: '¿Estás seguro de que deseas eliminar esta fila y todas sus columnas y elementos?',
            header: 'Confirmar Eliminación de Fila',
            icon: 'pi pi-exclamation-triangle',
            acceptProps: { label: 'Eliminar Fila', severity: 'danger' },
            rejectProps: { label: 'Cancelar', severity: 'secondary', outlined: true },
            accept: () => {
                sec.rows.splice(rIdx, 1);
                elementoSeleccionado.value = null;
            }
        });
    }

    function eliminarFilaSeleccionada() {
        if (!elementoSeleccionado.value) return;
        const targetId = elementoSeleccionado.value.id;

        for (const sec of layout.value.sections) {
            const rIdx = sec.rows.findIndex(r => r.id === targetId);
            if (rIdx !== -1) {
                eliminarFila(sec, rIdx);
                return;
            }
        }
    }

    function cambiarLayoutFila(fila, nuevoLayout) {
        if (!fila) return;

        const preset = GRID_PRESETS.find(p => p.id === nuevoLayout) || GRID_PRESETS[0];
        const targetNumCols = preset.cols || 1;

        let parentSec = null;
        let rIdx = -1;

        for (const sec of layout.value.sections) {
            const foundIndex = sec.rows.findIndex(r => r.id === fila.id);
            if (foundIndex !== -1) {
                parentSec = sec;
                rIdx = foundIndex;
                break;
            }
        }

        const colsActuales = fila.columns || [];
        const numColsActuales = colsActuales.length;

        fila.layout = nuevoLayout;

        if (numColsActuales > targetNumCols) {
            const sobrantes = colsActuales.slice(targetNumCols);
            fila.columns = colsActuales.slice(0, targetNumCols);

            // Solo crear nuevas filas para aquellas columnas sobrantes que contengan elementos reales.
            // Las columnas vacías se descartan directamente para no generar filas duplicadas indeseadas.
            const sobrantesConElementos = sobrantes.filter(col => Array.isArray(col.elements) && col.elements.length > 0);

            if (parentSec && rIdx !== -1 && sobrantesConElementos.length > 0) {
                sobrantesConElementos.forEach((colSobrante, sOffset) => {
                    const nuevaFila = {
                        id: uuid(),
                        layout: '1',
                        gap: fila.gap || 'md',
                        align: 'start',
                        columns: [
                            {
                                id: colSobrante.id || uuid(),
                                width: { xs: '1/1', sm: 'inherit', md: 'inherit', lg: '1/1', xl: 'inherit' },
                                order: { xs: 'none', sm: 'none', md: 'none', lg: 'none', xl: 'none' },
                                elements: colSobrante.elements || []
                            }
                        ]
                    };
                    parentSec.rows.splice(rIdx + 1 + sOffset, 0, nuevaFila);
                });
            }
        } else if (numColsActuales < targetNumCols) {
            const diff = targetNumCols - numColsActuales;
            const nuevasCols = [...colsActuales];

            for (let i = 0; i < diff; i++) {
                const colIdx = numColsActuales + i;
                const nextRowIndex = rIdx + 1;
                const nextRow = parentSec?.rows[nextRowIndex];

                if (nextRow && nextRow.layout === '1' && nextRow.columns?.length === 1) {
                    const colAbsorbida = nextRow.columns[0];
                    nuevasCols.push({
                        id: colAbsorbida.id || uuid(),
                        width: {
                            xs: '1/1',
                            sm: 'inherit',
                            md: 'inherit',
                            lg: preset.fractions?.[colIdx] || 'inherit',
                            xl: 'inherit',
                        },
                        order: { xs: 'none', sm: 'none', md: 'none', lg: 'none', xl: 'none' },
                        align: 'start',
                        elements: colAbsorbida.elements || []
                    });
                    parentSec.rows.splice(nextRowIndex, 1);
                } else {
                    nuevasCols.push({
                        id: uuid(),
                        width: {
                            xs: '1/1',
                            sm: 'inherit',
                            md: 'inherit',
                            lg: preset.fractions?.[colIdx] || 'inherit',
                            xl: 'inherit',
                        },
                        order: { xs: 'none', sm: 'none', md: 'none', lg: 'none', xl: 'none' },
                        align: 'start',
                        elements: []
                    });
                }
            }
            fila.columns = nuevasCols;
        }

        // Asegurar que las columnas tengan estructura width/order válida y sincronizar el ancho desktop (lg) con el preset seleccionado
        fila.columns.forEach((col, idx) => {
            const defaultLgFraction = preset.fractions?.[idx] || (targetNumCols === 1 ? '1/1' : 'inherit');
            if (!col.width || typeof col.width !== 'object') {
                col.width = {
                    xs: '1/1',
                    sm: 'inherit',
                    md: 'inherit',
                    lg: defaultLgFraction,
                    xl: 'inherit',
                };
            } else {
                col.width.lg = defaultLgFraction;
            }
            if (!col.order || typeof col.order !== 'object') {
                col.order = { xs: 'none', sm: 'none', md: 'none', lg: 'none', xl: 'none' };
            }
        });

        refrescarPrevisualizacion();
    }

    function añadirColumnaAFila(fila) {
        fila.columns.push({
            id: uuid(),
            width: { xs: '1/1', sm: 'inherit', md: 'inherit', lg: 'inherit', xl: 'inherit' },
            order: { xs: 'none', sm: 'none', md: 'none', lg: 'none', xl: 'none' },
            elements: []
        });
    }

    function eliminarColumnaDeFila(fila, index) {
        if (fila.columns.length <= 1) return;
        fila.columns.splice(index, 1);
    }

    function abrirModalAñadirElemento(columnaId) {
        modalBibliotecaModo.value = 'element';
        targetSectionIndex.value = null;
        columnaObjetivoId.value = columnaId;
        modalAddElementoAbierto.value = true;
    }

    function abrirModalBibliotecaSecciones(index = null) {
        modalBibliotecaModo.value = 'section';
        targetSectionIndex.value = index;
        columnaObjetivoId.value = null;
        modalAddElementoAbierto.value = true;
    }

    function insertarElemento(tipo) {
        let targetCol = null;

        if (columnaObjetivoId.value) {
            for (const sec of layout.value.sections || []) {
                for (const row of sec.rows || []) {
                    for (const col of row.columns || []) {
                        if (col.id === columnaObjetivoId.value) {
                            targetCol = col;
                            break;
                        }
                        // Búsqueda recursiva en subcolumnas de elementos sublayout
                        for (const elem of col.elements || []) {
                            if (elem.type === 'sublayout' && Array.isArray(elem.content?.rows)) {
                                for (const subrow of elem.content.rows) {
                                    for (const subcol of subrow.columns || []) {
                                        if (subcol.id === columnaObjetivoId.value) {
                                            targetCol = subcol;
                                            break;
                                        }
                                    }
                                    if (targetCol) break;
                                }
                            }
                            if (targetCol) break;
                        }
                        if (targetCol) break;
                    }
                    if (targetCol) break;
                }
                if (targetCol) break;
            }
        }

        if (!targetCol) {
            if (!layout.value.sections || !layout.value.sections.length) {
                crearSeccion();
            }
            let sec = layout.value.sections[layout.value.sections.length - 1] || layout.value.sections[0];
            if (sec) {
                if (!sec.rows || !sec.rows.length) {
                    sec.rows = [{ id: uuid(), type: 'row', columns: [{ id: uuid(), elements: [] }] }];
                }
                let row = sec.rows[sec.rows.length - 1] || sec.rows[0];
                if (!row.columns || !row.columns.length) {
                    row.columns = [{ id: uuid(), elements: [] }];
                }
                targetCol = row.columns[0];
            }
        }

        if (!targetCol) return;

        let content = {};
        if (tipo === 'heading') {
            content = { text: 'Nuevo Encabezado', tag: 'h2', align: 'left' };
        } else if (tipo === 'text') {
            content = { html: '<p>Este es un nuevo párrafo de texto enriquecido.</p>' };
        } else if (tipo === 'accordion') {
            content = {
                items: [
                    { title: 'Título 1', content: 'Descripción 1' },
                    { title: 'Título 2', content: 'Descripción 2' }
                ]
            };
        } else if (tipo === 'grid_articles') {
            content = {
                mode: 'manual',
                columns_desktop: 3,
                items: [
                    {
                        id: uuid(),
                        title: 'Título de ejemplo',
                        description: '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>',
                    }
                ],
            };
        } else if (tipo === 'button') {
            content = { label: 'Haz Clic Aquí', url: '#', variant: 'primary' };
        } else if (tipo === 'icon') {
            content = {
                icon: 'pi pi-star',
                url: '',
                target: '_self',
                icon_size: 'md',
                icon_size_px: 32,
                icon_style_mode: 'none',
                icon_color: 'primary',
                icon_bg_color: 'primary_soft',
                custom_color: '',
                custom_bg_color: '',
                align: 'left',
                margin_top: 'none',
                margin_bottom: 'none',
            };
        } else if (tipo === 'pagination') {
            content = {
                mode: 'article_nav',
                layout_variant: 'cards',
                numeric_variant: 'numbers_boxed',
                show_labels: true,
                custom_prev_label: 'Artículo Anterior',
                custom_next_label: 'Artículo Siguiente',
                show_titles: true,
                show_arrows: true,
                arrow_type: 'arrow',
                show_home_button: false,
                home_button_label: 'Todos los Artículos',
                home_button_url: '',
                home_button_icon: 'pi pi-th-large',
                align: 'center',
                margin_top: 'md',
                margin_bottom: 'none',
            };
        } else if (tipo === 'image') {
            content = { src: '', alt: 'Imagen ilustrativa' };
        } else if (tipo === 'video') {
            content = {
                src: '',
                poster: '',
                use_first_frame: true,
                autoplay: false,
                muted: true,
                loop: false,
                controls: true,
                pause_on_scroll: true,
                play_on_hover: false,
                aspect_ratio: '16:9',
                object_fit: 'cover',
                max_width: '100%',
                align: 'center',
                rounded: 'lg',
                shadow: 'md',
                overlay_enabled: false,
                overlay_color: 'rgba(0, 0, 0, 0.4)',
                show_play_button: true,
            };
        } else if (tipo === 'menu') {
            content = { source: 'location_header', orientation: 'horizontal', align: 'left', size: 'sm', color: 'default', gap: 'gap-4' };
        } else if (tipo === 'social') {
            content = { style_preset: 'soft', align: 'left', size: 'md', color_preset: 'brand' };
        } else if (tipo === 'html') {
            content = { code: '<div><p>Código HTML personalizado</p></div>' };
        } else if (tipo === 'sublayout') {
            content = {
                layout: '1/2+1/2',
                gap: 'md',
                align: 'center',
                justify: 'start',
                margin_top: 'none',
                margin_bottom: 'none',
                rows: [
                    {
                        id: uuid(),
                        layout: '1/2+1/2',
                        gap: 'md',
                        align: 'center',
                        justify: 'start',
                        columns: [
                            { id: uuid(), width: { xs: '1/1', md: '1/2' }, elements: [] },
                            { id: uuid(), width: { xs: '1/1', md: '1/2' }, elements: [] },
                        ]
                    }
                ]
            };
        } else if (tipo === 'map') {
            content = {
                engine: 'osm',
                map_type: 'osm_standard',
                height: '450px',
                border_radius: 'xl',
                shadow: 'md',
                zoom: 14,
                center_lat: 40.416775,
                center_lng: -3.703790,
                auto_fit_bounds: true,
                cooperative_gesture: true,
                color_mode: 'default',
                saturation: 0,
                hue: '',
                lightness: 0,
                invert_lightness: false,
                controls: { zoom: true, map_type: true, fullscreen: true, street_view: false },
                mode: 'manual',
                dynamic_binding: {
                    source: 'current_article',
                    category_id: 'all',
                    location_field: 'coordenadas',
                    limit: 20,
                },
                marker_title_tag: 'div',
                marker_title_aspect: 'h4',
                items: [
                    {
                        id: uuid(),
                        title: 'Nuestra Ubicación',
                        description: 'Visítanos en nuestras oficinas.',
                        lat: 40.416775,
                        lng: -3.703790,
                        address: 'Puerta del Sol, Madrid',
                        icon_url: '',
                        link_url: '',
                        link_text: 'Cómo llegar',
                        open_popup: false,
                        title_tag: 'inherit',
                        title_aspect: 'inherit',
                    }
                ],
            };
        } else if (tipo === 'gallery') {
            content = {
                mode: 'manual',
                layout: 'grid',
                columns_desktop: 3,
                columns_tablet: 'auto',
                columns_mobile: '1',
                gap: 'md',
                gap_tablet: 'auto',
                gap_mobile: 'auto',
                image_ratio: '16:9',
                image_fit: 'cover',
                rounded: 'lg',
                hover_effect: 'zoom',
                overlay_mode: 'none',
                show_caption: false,
                caption_position: 'below',
                enable_lightbox: true,
                filter_tags: false,
                margin_top: 'none',
                margin_bottom: 'none',
                center_columns: false,
                center_rows: false,
                show_dividers: false,
                max_height_px: null,
                max_width_px: null,
                is_white: false,
                color_filter: 'none',
                carousel_rows: 1,
                carousel_speed: 60,
                carousel_direction: 'left',
                carousel_pause_on_hover: true,
                carousel_fade_edges: true,
                carousel_card_style: 'plain',
                carousel_item_size: 'md',
                items: [],
            };
        } else if (tipo === 'form') {
            content = {
                form_slug: '',
                layout_style: 'clean',
                submit_button_text: 'Enviar mensaje',
                show_title: true,
                show_description: true,
            };
        }

        const nuevoElem = {
            id: uuid(),
            type: tipo,
            content: content,
            style: { size: 'text-2xl' },
        };

        if (!Array.isArray(targetCol.elements)) {
            targetCol.elements = [];
        }
        targetCol.elements.push(nuevoElem);

        // Reconstruir layout para forzar reactividad en Vue 3
        layout.value = JSON.parse(JSON.stringify(layout.value));
        layoutPreview.value = JSON.parse(JSON.stringify(layout.value));
        columnaObjetivoId.value = null;
        modalAddElementoAbierto.value = false;

        // Buscar la referencia REAL del elemento dentro del layout reconstruido
        // para evitar que el inspector trabaje con un objeto huérfano
        const elemReal = buscarElementoPorId(nuevoElem.id) || nuevoElem;
        seleccionarElemento(elemReal, 'element', false);
        refrescarPrevisualizacion();
    }

    function clonarConNuevosIds(obj) {
        const json = JSON.parse(JSON.stringify(obj));

        function regenerarIds(item) {
            if (!item || typeof item !== 'object') return;
            if (item.id) {
                item.id = uuid();
            }
            if (Array.isArray(item.sections)) item.sections.forEach(regenerarIds);
            if (Array.isArray(item.rows)) item.rows.forEach(regenerarIds);
            if (Array.isArray(item.columns)) item.columns.forEach(regenerarIds);
            if (Array.isArray(item.elements)) item.elements.forEach(regenerarIds);
            if (Array.isArray(item.items)) item.items.forEach(i => { if (i && typeof i === 'object' && i.id) i.id = uuid(); });
            if (item.content && Array.isArray(item.content.items)) item.content.items.forEach(i => { if (i && typeof i === 'object' && i.id) i.id = uuid(); });
            if (item.content && Array.isArray(item.content.rows)) item.content.rows.forEach(regenerarIds);
        }

        regenerarIds(json);
        return json;
    }

    function insertarPresetBiblioteca(presetOriginal, targetIndex = null) {
        if (!presetOriginal) return;

        // 1. Deserializar si viene como string JSON
        let contentObj = presetOriginal;
        if (typeof contentObj === 'string') {
            try {
                contentObj = JSON.parse(contentObj);
            } catch (e) {
                console.error('Error al deserializar preset de biblioteca:', e);
            }
        }

        // Si contentObj tiene a su vez un atributo 'content_json', extraerlo
        if (contentObj && typeof contentObj === 'object' && contentObj.content_json) {
            let inner = contentObj.content_json;
            if (typeof inner === 'string') {
                try { inner = JSON.parse(inner); } catch (e) {}
            }
            if (inner && typeof inner === 'object') {
                contentObj = {
                    ...inner,
                    name: contentObj.name || inner.name || inner.admin_label,
                    type: contentObj.type || inner.type,
                };
            }
        }

        const presetClonado = clonarConNuevosIds(contentObj);

        // 2. Si es una SECCIÓN COMPLETA (contiene array 'rows')
        if (presetClonado.rows && Array.isArray(presetClonado.rows)) {
            if (!layout.value.sections) layout.value.sections = [];
            if (targetIndex !== null && targetIndex !== undefined && targetIndex >= 0 && targetIndex <= layout.value.sections.length) {
                layout.value.sections.splice(targetIndex, 0, presetClonado);
            } else {
                layout.value.sections.push(presetClonado);
            }
            layout.value = JSON.parse(JSON.stringify(layout.value));
            layoutPreview.value = JSON.parse(JSON.stringify(layout.value));
            columnaObjetivoId.value = null;
            modalAddElementoAbierto.value = false;
            const secReal = buscarElementoPorId(presetClonado.id) || presetClonado;
            seleccionarElemento(secReal, 'section', false);
            refrescarPrevisualizacion();
            return;
        }

        // 3. Si es una FILA COMPLETA (contiene array 'columns')
        if (presetClonado.columns && Array.isArray(presetClonado.columns)) {
            if (!layout.value.sections || !layout.value.sections.length) {
                crearSeccion();
            }
            const targetSec = layout.value.sections[layout.value.sections.length - 1] || layout.value.sections[0];
            if (targetSec) {
                if (!targetSec.rows) targetSec.rows = [];
                targetSec.rows.push(presetClonado);
            }
            layout.value = JSON.parse(JSON.stringify(layout.value));
            layoutPreview.value = JSON.parse(JSON.stringify(layout.value));
            columnaObjetivoId.value = null;
            modalAddElementoAbierto.value = false;
            const rowReal = buscarElementoPorId(presetClonado.id) || presetClonado;
            seleccionarElemento(rowReal, 'row', false);
            refrescarPrevisualizacion();
            return;
        }

        // 4. Si es un ELEMENTO MODULAR
        // Localizar la columna objetivo donde insertar
        let targetCol = null;

        if (columnaObjetivoId.value) {
            for (const sec of layout.value.sections || []) {
                for (const row of sec.rows || []) {
                    for (const col of row.columns || []) {
                        if (col.id === columnaObjetivoId.value) {
                            targetCol = col;
                            break;
                        }
                    }
                }
            }
        }

        // Fallback robusto: si no hay columna objetivo o no se encuentra, usar la última columna activa o estructurar una nueva
        if (!targetCol) {
            if (!layout.value.sections || !layout.value.sections.length) {
                crearSeccion();
            }
            let sec = layout.value.sections[layout.value.sections.length - 1] || layout.value.sections[0];
            if (sec) {
                if (!sec.rows || !sec.rows.length) {
                    sec.rows = [{ id: uuid(), type: 'row', columns: [{ id: uuid(), elements: [] }] }];
                }
                let row = sec.rows[sec.rows.length - 1] || sec.rows[0];
                if (!row.columns || !row.columns.length) {
                    row.columns = [{ id: uuid(), elements: [] }];
                }
                targetCol = row.columns[0];
            }
        }

        if (targetCol) {
            if (!Array.isArray(targetCol.elements)) {
                targetCol.elements = [];
            }

            // Normalizar las propiedades del elemento para garantizar que id, type, content, style y admin_label estén completos
            let contentPayload = {};
            if (presetClonado.content && typeof presetClonado.content === 'object') {
                contentPayload = JSON.parse(JSON.stringify(presetClonado.content));
            } else {
                // Si las propiedades del contenido están directamente en la raíz del objeto
                const { id, type, style, admin_label, name, resolved_data, ...resto } = presetClonado;
                contentPayload = JSON.parse(JSON.stringify(resto));
            }

            const elemValido = {
                id: uuid(),
                type: presetClonado.type || 'text',
                content: contentPayload,
                style: presetClonado.style || { size: 'text-base' },
                admin_label: presetClonado.admin_label || presetClonado.name || 'Elemento Guardado',
            };

            if (presetClonado.resolved_data) {
                elemValido.resolved_data = JSON.parse(JSON.stringify(presetClonado.resolved_data));
            }

            targetCol.elements.push(elemValido);

            // Actualizar inmediatamente la referencia del layout en Vue 3 para re-renderizado instantáneo
            layout.value = JSON.parse(JSON.stringify(layout.value));
            layoutPreview.value = JSON.parse(JSON.stringify(layout.value));
            columnaObjetivoId.value = null;

            // Buscar la referencia REAL dentro del layout reconstruido
            const elemReal = buscarElementoPorId(elemValido.id) || elemValido;
            seleccionarElemento(elemReal, 'element', false);
        }

        modalAddElementoAbierto.value = false;
        refrescarPrevisualizacion();
    }

    function eliminarElemento(columna, index) {
        const elem = columna.elements[index];
        const tipoNombre = elem?.content?.text || elem?.type || 'elemento';
        if (!confirm) {
            columna.elements.splice(index, 1);
            elementoSeleccionado.value = null;
            return;
        }
        confirm.require({
            message: `¿Estás seguro de que deseas eliminar este elemento (${tipoNombre})?`,
            header: 'Confirmar Eliminación de Elemento',
            icon: 'pi pi-exclamation-triangle',
            acceptProps: { label: 'Eliminar Elemento', severity: 'danger' },
            rejectProps: { label: 'Cancelar', severity: 'secondary', outlined: true },
            accept: () => {
                columna.elements.splice(index, 1);
                elementoSeleccionado.value = null;
            }
        });
    }

    /**
     * Buscar recursivamente un nodo (sección, fila, columna o elemento) por su ID
     * dentro del árbol de layout reconstruido tras JSON.parse/JSON.stringify.
     * Evita que el inspector trabaje con referencias huérfanas.
     */
    function buscarElementoPorId(id) {
        if (!id || !layout.value?.sections) return null;
        for (const sec of layout.value.sections) {
            if (sec.id === id) return sec;
            for (const row of sec.rows || []) {
                if (row.id === id) return row;
                for (const col of row.columns || []) {
                    if (col.id === id) return col;
                    for (const elem of col.elements || []) {
                        if (elem.id === id) return elem;
                        // Búsqueda recursiva en subdiseños
                        if (elem.type === 'sublayout' && Array.isArray(elem.content?.rows)) {
                            for (const subrow of elem.content.rows) {
                                if (subrow.id === id) return subrow;
                                for (const subcol of subrow.columns || []) {
                                    if (subcol.id === id) return subcol;
                                    for (const subelem of subcol.elements || []) {
                                        if (subelem.id === id) return subelem;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return null;
    }

    function seleccionarElemento(elem, tipo, cambiarTab = true) {
        if (elem && tipo === 'section' && !elem.settings) {
            elem.settings = {
                container: 'boxed',
                padding: 'md',
                margin: 'none',
                bg_preset: 'white',
                bg_color: '',
                bg_image: '',
                bg_overlay: '',
                bg_dark_mode: 'inherit',
                 sticky: '',
                text_color: '',
                vertical_align: 'top',
                height_type: 'none',
                transparent_header: false,
                transparent_header_pull: false,
                transparent_header_nav_color: 'inherit',
                transparent_header_nav_color_dark: 'same',
            };
        }
        if (elem && tipo === 'element') {
            normalizarElementoBuilder(elem);
        }
        elementoSeleccionado.value = elem;
        elementoSeleccionadoTipo.value = tipo;
        if (cambiarTab) {
            tabActiva.value = '1';
        }
    }

    function limpiarEspaciosNoRompibles(obj) {
        if (typeof obj === 'string') {
            return obj.replace(/&nbsp;/g, ' ').replace(/\u00a0/g, ' ');
        }
        if (Array.isArray(obj)) {
            return obj.map(limpiarEspaciosNoRompibles);
        }
        if (obj !== null && typeof obj === 'object') {
            const res = {};
            for (const [k, v] of Object.entries(obj)) {
                res[k] = limpiarEspaciosNoRompibles(v);
            }
            return res;
        }
        return obj;
    }

    const guardandoEnCurso = ref(false);
    const omitirAvisoSalida = ref(false);

    function descartarCambiosYNavegar(targetUrl) {
        omitirAvisoSalida.value = true;
        layoutInicialJson.value = JSON.stringify(layout.value);
        router.visit(targetUrl);
    }

    function guardarBuilder() {
        normalizarArbolLayout(layout.value);
        const layoutClean = limpiarEspaciosNoRompibles(JSON.parse(JSON.stringify(layout.value)));
        form.layout_json = layoutClean;
        const currentSelectedId = elementoSeleccionado.value?.id;

        const targetRoute = props.rutaGuardarBuilder
            || (props.esFooter
                ? route('configuracion.footer.builder.update')
                : (props.esPlantilla
                    ? route('plantillas.builder.update', props.pagina.id)
                    : route('paginas.builder.update', props.pagina.id)));

        guardandoEnCurso.value = true;
        form.put(targetRoute, {
            preserveScroll: true,
            onSuccess: () => {
                layoutInicialJson.value = JSON.stringify(layout.value);
                if (currentSelectedId) {
                    const reconnectedElem = buscarElementoPorId(currentSelectedId);
                    if (reconnectedElem) {
                        elementoSeleccionado.value = reconnectedElem;
                    }
                }
            },
            onFinish: () => {
                guardandoEnCurso.value = false;
            },
        });
    }

    function handleBeforeUnload(event) {
        if (tieneCambiosPendientes.value && !guardandoEnCurso.value) {
            event.preventDefault();
            event.returnValue = '';
        }
    }

    let confirm = null;
    try {
        confirm = useConfirm();
    } catch (e) {
        confirm = null;
    }

    let removeInertiaListener = null;

    onMounted(() => {
        window.addEventListener('beforeunload', handleBeforeUnload);

        removeInertiaListener = router.on('before', (event) => {
            const method = String(event.detail?.visit?.method || 'get').toLowerCase();
            if (guardandoEnCurso.value || omitirAvisoSalida.value || method !== 'get') {
                omitirAvisoSalida.value = false;
                return;
            }

            if (tieneCambiosPendientes.value) {
                event.preventDefault();
                const targetUrl = event.detail.visit.url;

                if (confirm) {
                    confirm.require({
                        message: 'Tienes cambios sin guardar en esta página. ¿Estás seguro de que quieres salir sin guardar los cambios?',
                        header: 'Cambios sin guardar',
                        icon: 'pi pi-exclamation-triangle',
                        acceptLabel: 'Salir sin guardar',
                        rejectLabel: 'Seguir editando',
                        acceptClass: 'p-button-danger',
                        accept: () => {
                            descartarCambiosYNavegar(targetUrl);
                        },
                    });
                } else {
                    const salir = window.confirm('Tienes cambios sin guardar en esta página. ¿Estás seguro de que quieres salir sin guardar los cambios?');
                    if (salir) {
                        descartarCambiosYNavegar(targetUrl);
                    }
                }
            }
        });
    });

    onUnmounted(() => {
        window.removeEventListener('beforeunload', handleBeforeUnload);
        if (removeInertiaListener) {
            removeInertiaListener();
        }
    });

    async function interceptarClicksNavegacionPreview(event) {
        const anchor = event.target.closest('a');
        if (!anchor) return;

        const rawHref = anchor.getAttribute('href');
        if (!rawHref || rawHref === '#' || rawHref.startsWith('javascript:')) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        try {
            if (rawHref.startsWith('http://') || rawHref.startsWith('https://')) {
                const urlObj = new URL(rawHref);
                if (urlObj.origin !== window.location.origin) {
                    window.open(rawHref, '_blank', 'noopener,noreferrer');
                    return;
                }
            }
        } catch (e) {
            // Ignorar fallo de parseo
        }

        let targetPath = rawHref;
        try {
            if (rawHref.startsWith('http://') || rawHref.startsWith('https://')) {
                targetPath = new URL(rawHref).pathname;
            } else {
                targetPath = rawHref.split('?')[0].split('#')[0];
            }
        } catch (e) {
            targetPath = rawHref.split('?')[0].split('#')[0];
        }

        if (!targetPath.startsWith('/')) {
            targetPath = '/' + targetPath;
        }
        if (targetPath.length > 1 && targetPath.endsWith('/')) {
            targetPath = targetPath.slice(0, -1);
        }

        const targetPage = (props.paginasList || []).find(p => {
            const pageUrl = p.url === '/' ? '/' : (p.url.endsWith('/') ? p.url.slice(0, -1) : p.url);
            return pageUrl === targetPath || p.slug === targetPath.replace('/', '');
        });

        if (targetPage) {
            if (targetPage.id === props.pagina.id) {
                const previewContainer = document.querySelector('main.flex-1.overflow-y-auto');
                if (previewContainer) {
                    previewContainer.scrollTo({ top: 0, behavior: 'smooth' });
                }
                return;
            }

            if (tieneCambiosPendientes.value) {
                try {
                    const saveUrl = props.rutaGuardarBuilder
                        || (props.esFooter
                            ? route('configuracion.footer.builder.update')
                            : (props.esPlantilla
                                ? route('plantillas.builder.update', props.pagina.id)
                                : route('paginas.builder.update', props.pagina.id)));
                    await axios.put(saveUrl, {
                        layout_json: layout.value
                    });
                    layoutInicialJson.value = JSON.stringify(layout.value);
                } catch (err) {
                    console.error('Autoguardado antes de cambiar de página:', err);
                }
            }

            const destUrl = targetPage.builder_url
                || (targetPage.type === 'template'
                    ? route('plantillas.builder', targetPage.id)
                    : route('paginas.builder', targetPage.id));
            router.visit(destUrl);
        }
    }

    return {
        siteSettings,
        menuHeader,
        menuFooter,
        layout,
        layoutPreview,
        tieneCambiosPendientes,
        tabActiva,
        elementoSeleccionado,
        elementoSeleccionadoTipo,
        vistaDispositivo,
        anchuraPrevisualizacion,
        form,
        modalAddElementoAbierto,
        modalBibliotecaModo,
        targetSectionIndex,
        columnaObjetivoId,
        catalogoElementos,
        resolvingPreview,
        opcionesCategoriasConTodas,
        opcionesContenedor,
        opcionesContenedorFila,
        opcionesAltura,
        opcionesAlineacionVertical,
        opcionesColorTexto,
        opcionesContrasteCabecera,
        opcionesElementoHtml,
        opcionesSticky,
        opcionesPadding,
        opcionesMargin,
        opcionesFondos,
        opcionesBgSize,
        opcionesBgPosition,
        opcionesBgAttachment,
        opcionesBgRepeat,
        opcionesBgBlendMode,
        opcionesBgFilter,
        opcionesBgHideMobile,
        opcionesLayoutFila,
        uuid,
        getElementIcon,
        getElementLabel,
        obtenerNombreElemento,
        añadirSeccion,
        eliminarSeccion,
        añadirFila,
        eliminarFila,
        eliminarFilaSeleccionada,
        cambiarLayoutFila,
        añadirColumnaAFila,
        eliminarColumnaDeFila,
        abrirModalAñadirElemento,
        abrirModalBibliotecaSecciones,
        insertarElemento,
        insertarPresetBiblioteca,
        eliminarElemento,
        seleccionarElemento,
        buscarElementoPorId,
        guardarBuilder,
        descartarCambiosYNavegar,
        refrescarPrevisualizacion,
        interceptarClicksNavegacionPreview,
        añadirItemManualGrid,
        startDragItemManual,
        dropItemManual,
        moverItemManual,
        eliminarItemManualGrid,
        añadirItemAcordeon,
        eliminarItemAcordeon,
    };
}

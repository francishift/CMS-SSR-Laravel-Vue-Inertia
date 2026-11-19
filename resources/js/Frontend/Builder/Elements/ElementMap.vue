<script>
export default {
    name: 'ElementMap',
};
</script>

<script setup>
/**
 * Componente ElementMap del Page Builder.
 * Renderiza un mapa interactivo con soporte dual para:
 *  - Google Maps JavaScript API (con API Key cifrada del CMS y stylers de saturación/tono/brillo/invertir)
 *  - OpenStreetMap (Leaflet) (cero coste, privado, sin API Key, con filtros de colorimetría CSS y CartoDB)
 *
 * Soporta múltiples marcadores (estáticos o dinámicos desde artículos del CMS),
 * auto-fit bounds, scroll cooperativo, altura responsiva o expandible a columna,
 * popups con TailwindCSS y 100% resiliente con SSR.
 */
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    style: { type: Object, default: () => ({}) },
    resolvedData: { type: [Object, Array], default: null },
});

const page = usePage();
const siteSettings = computed(() => page.props.siteSettings || {});

// En rutas de administración, la key viene en siteSettings.
// En páginas públicas es null (no se expone en el HTML) y se obtiene bajo demanda.
const googleMapsApiKeyGlobal = computed(() => siteSettings.value.google_maps_api_key || '');
const fetchedApiKey = ref('');

/**
 * Obtener la API key de Google Maps desde el endpoint seguro.
 * Solo se invoca en páginas públicas donde siteSettings no incluye la key.
 */
const obtenerApiKey = async () => {
    // Si ya la tenemos de siteSettings (admin) o de un fetch previo, no repetir
    if (googleMapsApiKeyGlobal.value || fetchedApiKey.value) return;
    if (typeof window === 'undefined') return;

    try {
        const resp = await fetch('/api/cms/maps-key', {
            headers: { 'Accept': 'application/json' },
        });
        if (resp.ok) {
            const data = await resp.json();
            fetchedApiKey.value = (data.key || '').trim();
        }
    } catch {
        // Fallo silencioso: se mostrará el mensaje de error de API key no disponible
    }
};

const effectiveApiKey = computed(() => (googleMapsApiKeyGlobal.value || fetchedApiKey.value || '').trim());

const mapContainer = ref(null);
const isMounted = ref(false);
const isInViewport = ref(false);
const mapInstance = ref(null);
const markersInstances = ref([]);
const isLoadingEngine = ref(false);
const errorMensaje = ref(null);

// Configuración general
const fallbackEngine = ref(null);
const activeEngine = computed(() => fallbackEngine.value || props.content?.engine || 'osm');
const autoFitBounds = computed(() => props.content?.auto_fit_bounds !== false);
const cooperativeGesture = computed(() => props.content?.cooperative_gesture !== false);
const defaultZoom = computed(() => Number(props.content?.zoom) || 14);
const markerTitleTag = computed(() => {
    const t = props.content?.marker_title_tag || props.content?.title_tag || 'div';
    return ['div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span'].includes(t) ? t : 'div';
});
const markerTitleAspect = computed(() => {
    const a = props.content?.marker_title_aspect || 'h4';
    return ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'base', 'sm', 'xs'].includes(a) ? a : 'h4';
});

function resolverAspectoTituloClases(aspecto) {
    const mapaAspectos = {
        h1: 'text-2xl font-bold tracking-tight text-zinc-900 mb-1.5 leading-tight',
        h2: 'text-xl font-bold text-zinc-900 mb-1.5 leading-snug',
        h3: 'text-lg font-bold text-zinc-900 mb-1 leading-snug',
        h4: 'text-base font-bold text-zinc-900 mb-1 leading-snug',
        h5: 'text-sm font-semibold text-zinc-900 mb-1 leading-snug',
        h6: 'text-xs font-semibold uppercase tracking-wider text-zinc-900 mb-1 leading-snug',
        base: 'text-base font-medium text-zinc-900 mb-1 leading-normal',
        sm: 'text-sm font-medium text-zinc-800 mb-1 leading-normal',
        xs: 'text-xs font-medium text-zinc-800 mb-1 leading-normal',
    };
    return mapaAspectos[aspecto] || mapaAspectos.h4;
}

// Marcadores efectivos (prioridad: resueltos dinámicamente > manuales del content)
const marcadoresEfectivos = computed(() => {
    let items = [];
    if (props.resolvedData && Array.isArray(props.resolvedData.markers)) {
        items = props.resolvedData.markers;
    } else if (Array.isArray(props.content?.items)) {
        items = props.content.items;
    }

    // Si solo hay un marcador y el usuario configuró center_lat/lng personalizado (no el default de Madrid),
    // pero el marcador se quedó con las coordenadas default de Madrid (40.416775, -3.703790),
    // sincronizar el marcador con center_lat/lng para que aparezca en la ubicación exacta elegida.
    if (items.length === 1) {
        const m = { ...items[0] };
        const cLat = Number(props.content?.center_lat);
        const cLng = Number(props.content?.center_lng);
        const mLat = Number(m.lat);
        const mLng = Number(m.lng);

        if (!isNaN(cLat) && !isNaN(cLng) && (cLat !== 40.416775 || cLng !== -3.703790)) {
            if (mLat === 40.416775 && mLng === -3.703790) {
                m.lat = cLat;
                m.lng = cLng;
            }
        }
        return [m];
    }

    return items;
});

const centerLat = computed(() => {
    const lat = Number(props.content?.center_lat);
    const primer = marcadoresEfectivos.value[0];
    const primerLat = primer ? Number(primer.lat) : NaN;

    // Si el primer marcador tiene una latitud personalizada (no el default de Madrid), usarla
    if (!isNaN(primerLat) && primerLat !== 0 && primerLat !== 40.416775) {
        return primerLat;
    }

    if (!isNaN(lat) && lat !== 0) return lat;
    if (!isNaN(primerLat) && primerLat !== 0) return primerLat;
    return 40.416775;
});

const centerLng = computed(() => {
    const lng = Number(props.content?.center_lng);
    const primer = marcadoresEfectivos.value[0];
    const primerLng = primer ? Number(primer.lng) : NaN;

    if (!isNaN(primerLng) && primerLng !== 0 && primerLng !== -3.703790) {
        return primerLng;
    }

    if (!isNaN(lng) && lng !== 0) return lng;
    if (!isNaN(primerLng) && primerLng !== 0) return primerLng;
    return -3.703790;
});

// Colorimetría y estilos
const colorMode = computed(() => props.content?.color_mode || 'default');
const saturation = computed(() => Number(props.content?.saturation) || 0);
const hue = computed(() => props.content?.hue || '');
const lightness = computed(() => Number(props.content?.lightness) || 0);
const invertLightness = computed(() => Boolean(props.content?.invert_lightness));

// Estilos de dimensiones y contenedores
const heightClass = computed(() => {
    const h = props.content?.height || '450px';
    if (h === 'match') return 'h-full min-h-[300px] flex-1';
    return '';
});

const heightStyle = computed(() => {
    const h = props.content?.height || '450px';
    if (h === 'match') return { height: '100%' };
    return { height: h };
});

const borderRadiusClass = computed(() => {
    const br = props.content?.border_radius || 'xl';
    return {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        xl: 'rounded-xl',
        '2xl': 'rounded-2xl',
        '3xl': 'rounded-3xl',
        full: 'rounded-full',
    }[br] || 'rounded-xl';
});

const shadowClass = computed(() => {
    const sh = props.content?.shadow || 'md';
    return {
        none: 'shadow-none',
        sm: 'shadow-xs',
        md: 'shadow-md',
        lg: 'shadow-lg',
        xl: 'shadow-xl',
        '2xl': 'shadow-2xl',
    }[sh] || 'shadow-md';
});

// Márgenes del constructor
const marginClass = computed(() => {
    const mt = props.content?.margin_top || props.style?.margin_top || 'none';
    const mb = props.content?.margin_bottom || props.style?.margin_bottom || 'none';

    const mtMap = { none: 'mt-0', xs: 'mt-1.5', sm: 'mt-3', md: 'mt-5', lg: 'mt-8', xl: 'mt-12', '2xl': 'mt-16' };
    const mbMap = { none: 'mb-0', xs: 'mb-1.5', sm: 'mb-3', md: 'mb-5', lg: 'mb-8', xl: 'mb-12', '2xl': 'mb-16' };

    return `${mtMap[mt] || 'mt-0'} ${mbMap[mb] || 'mb-0'}`;
});

// Filtro CSS de colorimetría para OpenStreetMap (Leaflet)
const osmFilterStyle = computed(() => {
    if (activeEngine.value !== 'osm') return '';

    const filters = [];

    if (colorMode.value === 'grayscale') {
        filters.push('grayscale(100%)');
    } else if (colorMode.value === 'dark' || invertLightness.value) {
        filters.push('invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%)');
    } else {
        if (saturation.value !== 0) {
            // Mapear saturación (-100 a 100) a filtro CSS saturate(0% a 200%)
            const satVal = Math.max(0, 100 + saturation.value);
            filters.push(`saturate(${satVal}%)`);
        }
        if (lightness.value !== 0) {
            const brVal = Math.max(0, 100 + lightness.value);
            filters.push(`brightness(${brVal}%)`);
        }
    }

    return filters.join(' ');
});

// Cargador de scripts de Google Maps con promesa singleton
const cargarGoogleMapsScript = (apiKey) => {
    if (typeof window === 'undefined') return Promise.reject(new Error('SSR'));
    if (window.google && window.google.maps) return Promise.resolve(window.google.maps);

    if (window.__googleMapsLoadingPromise) {
        return window.__googleMapsLoadingPromise;
    }

    // Callback de autenticación global de Google Maps para avisar si la clave tiene restricciones no coincidentes
    if (!window.gm_authFailure) {
        window.gm_authFailure = () => {
            errorMensaje.value = 'Google Maps: Error de autenticación. Verifica que la API Key esté activa y que el dominio actual (' + window.location.hostname + ') esté incluido en las restricciones de Google Cloud Console.';
            isLoadingEngine.value = false;
        };
    }

    window.__googleMapsLoadingPromise = new Promise((resolve, reject) => {
        const callbackName = '__initGoogleMapsCallback_' + Math.random().toString(36).substring(2, 9);
        window[callbackName] = () => {
            delete window[callbackName];
            resolve(window.google.maps);
        };

        const script = document.createElement('script');
        // Inyectar preconnect dinámico para acelerar la carga del script
        if (!document.querySelector('link[rel="preconnect"][href="https://maps.googleapis.com"]')) {
            const link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = 'https://maps.googleapis.com';
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        }
        // Usamos v=quarterly para máxima estabilidad y libraries=places,marker para compatibilidad con AdvancedMarkerElement
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places,marker&v=quarterly&loading=async&callback=${callbackName}`;
        script.async = true;
        script.defer = true;
        script.onerror = (e) => {
            delete window[callbackName];
            window.__googleMapsLoadingPromise = null;
            reject(new Error('No se pudo cargar Google Maps API. Verifica la clave y restricciones de dominio.'));
        };
        document.head.appendChild(script);
    });

    return window.__googleMapsLoadingPromise;
};

// Cargador de Leaflet JS y CSS con promesa singleton
const cargarLeaflet = () => {
    if (typeof window === 'undefined') return Promise.reject(new Error('SSR'));
    if (window.L) return Promise.resolve(window.L);

    if (window.__leafletLoadingPromise) {
        return window.__leafletLoadingPromise;
    }

    window.__leafletLoadingPromise = new Promise((resolve, reject) => {
        // Inyectar CSS de Leaflet si no existe
        if (!document.getElementById('leaflet-css')) {
            const link = document.createElement('link');
            link.id = 'leaflet-css';
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
        }

        // Inyectar JS de Leaflet
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.async = true;
        script.onload = () => resolve(window.L);
        script.onerror = () => {
            window.__leafletLoadingPromise = null;
            reject(new Error('No se pudo cargar la librería Leaflet para OpenStreetMap.'));
        };
        document.head.appendChild(script);
    });

    return window.__leafletLoadingPromise;
};

// Limpiar instancias previas de mapas y marcadores
const destruirMapa = () => {
    if (mapInstance.value) {
        try {
            if (activeEngine.value === 'osm' && typeof mapInstance.value.remove === 'function') {
                mapInstance.value.remove();
            } else if (activeEngine.value === 'google') {
                if (typeof window !== 'undefined' && window.google?.maps?.event) {
                    window.google.maps.event.clearInstanceListeners(mapInstance.value);
                }
            }
        } catch (e) {
            // Ignorar errores al desmontar
        }
        mapInstance.value = null;
    }

    if (markersInstances.value && markersInstances.value.length) {
        markersInstances.value.forEach((m) => {
            try {
                if (activeEngine.value === 'google') {
                    if (typeof window !== 'undefined' && window.google?.maps?.event) {
                        window.google.maps.event.clearInstanceListeners(m);
                    }
                    if (typeof m.setMap === 'function') {
                        m.setMap(null);
                    } else if ('map' in m) {
                        m.map = null;
                    }
                } else if (activeEngine.value === 'osm' && typeof m.remove === 'function') {
                    m.remove();
                }
            } catch (e) {
                // Ignorar errores
            }
        });
        markersInstances.value = [];
    }

    if (mapContainer.value) {
        mapContainer.value.innerHTML = '';
    }
};

// Inicializar Google Maps
const inicializarGoogleMaps = async () => {
    if (!mapContainer.value) return;

    if (!effectiveApiKey.value) {
        errorMensaje.value = 'Google Maps requiere una API Key. Configúrala en el panel de configuración global o selecciona OpenStreetMap.';
        return;
    }

    try {
        isLoadingEngine.value = true;
        errorMensaje.value = null;
        const googleMaps = await cargarGoogleMapsScript(effectiveApiKey.value);

        if (!mapContainer.value) return;
        destruirMapa();

        // Estilos de colorimetría para Google Maps (Stylers nativos)
        const mapStyles = [];
        if (colorMode.value === 'grayscale') {
            mapStyles.push({ stylers: [{ saturation: -100 }] });
        } else if (colorMode.value === 'dark' || invertLightness.value) {
            mapStyles.push(
                { elementType: 'geometry', stylers: [{ color: '#242f3e' }] },
                { elementType: 'labels.text.stroke', stylers: [{ color: '#242f3e' }] },
                { elementType: 'labels.text.fill', stylers: [{ color: '#746855' }] },
                {
                    featureType: 'administrative.locality',
                    elementType: 'labels.text.fill',
                    stylers: [{ color: '#d59563' }]
                },
                {
                    featureType: 'poi',
                    elementType: 'labels.text.fill',
                    stylers: [{ color: '#d59563' }]
                },
                {
                    featureType: 'poi.park',
                    elementType: 'geometry',
                    stylers: [{ color: '#263c3f' }]
                },
                {
                    featureType: 'road',
                    elementType: 'geometry',
                    stylers: [{ color: '#38414e' }]
                },
                {
                    featureType: 'road',
                    elementType: 'geometry.stroke',
                    stylers: [{ color: '#212a37' }]
                },
                {
                    featureType: 'road',
                    elementType: 'labels.text.fill',
                    stylers: [{ color: '#9ca5b3' }]
                },
                {
                    featureType: 'water',
                    elementType: 'geometry',
                    stylers: [{ color: '#17263c' }]
                }
            );
        } else {
            const stylers = [];
            if (saturation.value !== 0) stylers.push({ saturation: saturation.value });
            if (hue.value) stylers.push({ hue: hue.value });
            if (lightness.value !== 0) stylers.push({ lightness: lightness.value });
            if (invertLightness.value) stylers.push({ invert_lightness: true });
            if (stylers.length) {
                mapStyles.push({ stylers });
            }
        }

        const mapTypeMap = {
            roadmap: googleMaps.MapTypeId.ROADMAP,
            satellite: googleMaps.MapTypeId.SATELLITE,
            hybrid: googleMaps.MapTypeId.HYBRID,
            terrain: googleMaps.MapTypeId.TERRAIN,
        };

        const hasMapId = Boolean(props.content?.map_id);
        const mapOptions = {
            center: { lat: centerLat.value, lng: centerLng.value },
            zoom: defaultZoom.value,
            mapTypeId: mapTypeMap[props.content?.map_type] || googleMaps.MapTypeId.ROADMAP,
            gestureHandling: cooperativeGesture.value ? 'cooperative' : 'greedy',
            zoomControl: props.content?.controls?.zoom !== false,
            mapTypeControl: props.content?.controls?.map_type !== false,
            fullscreenControl: props.content?.controls?.fullscreen !== false,
            streetViewControl: Boolean(props.content?.controls?.street_view),
        };

        if (hasMapId) {
            // Google Maps exige mapId para AdvancedMarkerElement y Cloud Styling
            mapOptions.mapId = props.content.map_id;
        } else if (mapStyles.length) {
            // Los estilos locales JSON solo son compatibles en mapas sin mapId
            mapOptions.styles = mapStyles;
        }

        const map = new googleMaps.Map(mapContainer.value, mapOptions);
        mapInstance.value = map;

        const bounds = new googleMaps.LatLngBounds();
        let validMarkersCount = 0;

        // Si se configuró un map_id y la librería AdvancedMarkerElement está disponible, se utiliza.
        // En caso contrario, se utiliza google.maps.Marker para conservar total compatibilidad con estilos JSON locales.
        const useAdvancedMarkers = hasMapId && Boolean(googleMaps.marker?.AdvancedMarkerElement);

        marcadoresEfectivos.value.forEach((m) => {
            const lat = Number(m.lat);
            const lng = Number(m.lng);

            if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;

            const position = { lat, lng };
            bounds.extend(position);
            validMarkersCount++;

            let marker;
            if (useAdvancedMarkers) {
                let markerContent = null;
                if (m.icon_url) {
                    const img = document.createElement('img');
                    img.src = m.icon_url;
                    img.alt = m.title || 'Marker';
                    img.style.width = '36px';
                    img.style.height = '36px';
                    img.style.objectFit = 'contain';
                    markerContent = img;
                }

                marker = new googleMaps.marker.AdvancedMarkerElement({
                    position,
                    map,
                    title: m.title || '',
                    content: markerContent,
                });
            } else {
                const markerConfig = {
                    position,
                    map,
                    title: m.title || '',
                };

                if (m.icon_url) {
                    markerConfig.icon = {
                        url: m.icon_url,
                        scaledSize: new googleMaps.Size(36, 36),
                    };
                }

                marker = new googleMaps.Marker(markerConfig);
            }

            markersInstances.value.push(marker);

            // Popup InfoWindow
            if (m.title || m.description || m.link_url) {
                const tag = (m.title_tag && m.title_tag !== 'inherit' && ['div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span'].includes(m.title_tag))
                    ? m.title_tag
                    : markerTitleTag.value;
                const aspecto = (m.title_aspect && m.title_aspect !== 'inherit' && ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'base', 'sm', 'xs'].includes(m.title_aspect))
                    ? m.title_aspect
                    : markerTitleAspect.value;
                const clasesAspecto = resolverAspectoTituloClases(aspecto);
                let html = '<div class="pr-6 font-sans text-left">';
                if (m.title) html += `<${tag} class="${clasesAspecto}">${escapeHtml(m.title)}</${tag}>`;
                if (m.description) html += `<div class="text-xs text-zinc-600 mb-2 leading-relaxed">${escapeHtml(m.description)}</div>`;
                if (m.link_url) {
                    html += `<a href="${encodeURI(m.link_url)}" target="_blank" class="inline-block text-[11px] font-bold text-white bg-emerald-600 hover:bg-emerald-500 px-3 py-1.5 rounded-lg transition-colors">${escapeHtml(m.link_text || 'Cómo llegar')}</a>`;
                }
                html += '</div>';

                const infoWindow = new googleMaps.InfoWindow({ content: html });

                marker.addListener('click', () => {
                    infoWindow.open({
                        anchor: marker,
                        map,
                    });
                });

                if (m.open_popup) {
                    infoWindow.open({
                        anchor: marker,
                        map,
                    });
                }
            }
        });

        if (autoFitBounds.value) {
            if (validMarkersCount > 1) {
                map.fitBounds(bounds);
            } else if (validMarkersCount === 1) {
                const primerValido = marcadoresEfectivos.value.find(m => !isNaN(Number(m.lat)) && !isNaN(Number(m.lng)));
                if (primerValido) {
                    map.setCenter({ lat: Number(primerValido.lat), lng: Number(primerValido.lng) });
                    map.setZoom(defaultZoom.value);
                }
            }
        }
    } catch (err) {
        errorMensaje.value = err.message || 'Error al inicializar Google Maps.';
    } finally {
        isLoadingEngine.value = false;
    }
};

// Inicializar OpenStreetMap (Leaflet)
const inicializarOpenStreetMap = async () => {
    if (!mapContainer.value) return;

    try {
        isLoadingEngine.value = true;
        errorMensaje.value = null;
        const L = await cargarLeaflet();

        if (!mapContainer.value) return;
        destruirMapa();

        const map = L.map(mapContainer.value, {
            center: [centerLat.value, centerLng.value],
            zoom: defaultZoom.value,
            scrollWheelZoom: !cooperativeGesture.value,
            zoomControl: props.content?.controls?.zoom !== false,
        });

        // Tiles según tipo de mapa
        let tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        let tileAttrib = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

        if (props.content?.map_type === 'carto_dark' || colorMode.value === 'dark') {
            tileUrl = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
            tileAttrib = '&copy; CartoDB &copy; OpenStreetMap';
        } else if (props.content?.map_type === 'carto_light') {
            tileUrl = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
            tileAttrib = '&copy; CartoDB &copy; OpenStreetMap';
        }

        const tileLayer = L.tileLayer(tileUrl, {
            maxZoom: 19,
            attribution: tileAttrib,
        }).addTo(map);

        mapInstance.value = map;

        const bounds = [];
        let validMarkersCount = 0;

        marcadoresEfectivos.value.forEach((m) => {
            const lat = Number(m.lat);
            const lng = Number(m.lng);

            if (isNaN(lat) || isNaN(lng) || (lat === 0 && lng === 0)) return;

            bounds.push([lat, lng]);
            validMarkersCount++;

            let markerOptions = {};
            if (m.icon_url) {
                markerOptions.icon = L.icon({
                    iconUrl: m.icon_url,
                    iconSize: [36, 36],
                    iconAnchor: [18, 36],
                    popupAnchor: [0, -32],
                });
            }

            const marker = L.marker([lat, lng], markerOptions).addTo(map);
            markersInstances.value.push(marker);

            if (m.title || m.description || m.link_url) {
                const tag = (m.title_tag && m.title_tag !== 'inherit' && ['div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span'].includes(m.title_tag))
                    ? m.title_tag
                    : markerTitleTag.value;
                const aspecto = (m.title_aspect && m.title_aspect !== 'inherit' && ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'base', 'sm', 'xs'].includes(m.title_aspect))
                    ? m.title_aspect
                    : markerTitleAspect.value;
                const clasesAspecto = resolverAspectoTituloClases(aspecto);
                let html = '<div class="p-1 max-w-xs font-sans text-left">';
                if (m.title) html += `<${tag} class="${clasesAspecto}">${escapeHtml(m.title)}</${tag}>`;
                if (m.description) html += `<div class="text-[11px] text-zinc-600 mb-2 leading-relaxed">${escapeHtml(m.description)}</div>`;
                if (m.link_url) {
                    html += `<a href="${encodeURI(m.link_url)}" class="inline-block text-[10px] font-bold text-white bg-emerald-600 hover:bg-emerald-500 px-2.5 py-1 rounded transition-colors">${escapeHtml(m.link_text || 'Ver más')}</a>`;
                }
                html += '</div>';

                marker.bindPopup(html);

                if (m.open_popup) {
                    marker.openPopup();
                }
            }
        });

        if (autoFitBounds.value) {
            if (validMarkersCount > 1) {
                map.fitBounds(bounds, { padding: [30, 30] });
            } else if (validMarkersCount === 1) {
                const primerValido = marcadoresEfectivos.value.find(m => !isNaN(Number(m.lat)) && !isNaN(Number(m.lng)));
                if (primerValido) {
                    map.setView([Number(primerValido.lat), Number(primerValido.lng)], defaultZoom.value);
                }
            }
        }

        // Forzar actualización de tamaño tras renderizado en contenedor dinámico
        setTimeout(() => {
            if (mapInstance.value && typeof mapInstance.value.invalidateSize === 'function') {
                mapInstance.value.invalidateSize();
            }
        }, 200);
    } catch (err) {
        errorMensaje.value = err.message || 'Error al inicializar OpenStreetMap.';
    } finally {
        isLoadingEngine.value = false;
    }
};

// Control de concurrencia y bloqueo de inicializaciones múltiples simultáneas
let isInitializing = false;
let needsReinit = false;

const inicializarMapa = async () => {
    if (typeof window === 'undefined') return;
    if (!isInViewport.value || !mapContainer.value) return;

    if (isInitializing) {
        needsReinit = true;
        return;
    }

    isInitializing = true;
    try {
        if (activeEngine.value === 'google') {
            // Obtener la API key bajo demanda si no está disponible (páginas públicas)
            await obtenerApiKey();
            if (!mapContainer.value) return;
            await inicializarGoogleMaps();
        } else {
            await inicializarOpenStreetMap();
        }
    } finally {
        isInitializing = false;
        if (needsReinit && mapContainer.value) {
            needsReinit = false;
            nextTick(inicializarMapa);
        }
    }
};

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

let observer = null;
let debounceTimer = null;

onMounted(() => {
    isMounted.value = true;

    // Observar entrada en viewport para Lazy Loading
    if (typeof IntersectionObserver !== 'undefined' && mapContainer.value) {
        observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting) {
                    isInViewport.value = true;
                    inicializarMapa();
                    if (observer) {
                        observer.disconnect();
                        observer = null;
                    }
                }
            },
            { rootMargin: '250px' }
        );
        observer.observe(mapContainer.value);
    } else {
        isInViewport.value = true;
        nextTick(inicializarMapa);
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
        observer = null;
    }
    if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
    }
    destruirMapa();
});

// Reactividad profunda con debounce para evitar recreaciones concurrentes mientras la API Key o props cargan
watch(
    () => [props.content, props.resolvedData, effectiveApiKey.value],
    () => {
        if (isInViewport.value) {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                inicializarMapa();
            }, 60);
        }
    },
    { deep: true }
);
</script>

<template>
    <div
        :class="[
            'w-full relative overflow-hidden group',
            marginClass,
            borderRadiusClass,
            shadowClass,
            heightClass,
            style.custom_classes || ''
        ]"
        :style="heightStyle"
    >
        <!-- SSR Placeholder o Esqueleto de Carga Previo al Viewport -->
        <div
            v-if="!isMounted || !isInViewport || isLoadingEngine"
            class="absolute inset-0 bg-zinc-100 dark:bg-zinc-800/80 flex flex-col items-center justify-center text-zinc-400 z-10 transition-opacity"
        >
            <div class="flex flex-col items-center gap-2">
                <i class="pi pi-map text-3xl animate-pulse text-zinc-400 dark:text-zinc-500" />
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                    {{ isLoadingEngine ? 'Cargando mapa interactivo...' : 'Cargando mapa...' }}
                </span>
            </div>
        </div>

        <!-- Alerta de Error si la API Key no es válida o hay fallo -->
        <div
            v-if="errorMensaje"
            class="absolute inset-0 bg-zinc-900/90 text-white flex flex-col items-center justify-center p-6 text-center z-20 space-y-3"
        >
            <i class="pi pi-exclamation-triangle text-amber-400 text-3xl" />
            <div class="max-w-md space-y-1">
                <h4 class="text-sm font-bold">Aviso del Elemento Mapa</h4>
                <p class="text-xs text-zinc-300 leading-relaxed">{{ errorMensaje }}</p>
            </div>
            <button
                v-if="activeEngine === 'google'"
                type="button"
                @click="fallbackEngine = 'osm'; inicializarOpenStreetMap();"
                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg transition-colors cursor-pointer"
            >
                Cambiar temporalmente a OpenStreetMap (Sin clave)
            </button>
        </div>

        <!-- Contenedor del Mapa Real (Google Maps o Leaflet) -->
        <div
            ref="mapContainer"
            class="w-full h-full min-h-[250px] relative z-0"
            :style="osmFilterStyle ? { filter: osmFilterStyle } : {}"
        />
    </div>
</template>

<style>
/* Ajustes para InfoWindow (galleta) de Google Maps */
.gm-style .gm-style-iw-c {
    padding: 12px 14px 10px 14px !important;
    border-radius: 0.85rem !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    max-width: 320px !important;
}

/* Eliminar el espacio superior fantasma reservado para la cabecera en Google Maps */
.gm-style .gm-style-iw-chr {
    position: absolute !important;
    top: 4px !important;
    right: 4px !important;
    height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
}

.gm-style .gm-style-iw-d {
    overflow: hidden !important;
    padding: 0 !important;
    max-width: 280px !important;
}

/* Botón de cierre discreto y bien posicionado */
.gm-style .gm-ui-hover-effect {
    top: 4px !important;
    right: 4px !important;
    width: 24px !important;
    height: 24px !important;
    opacity: 0.6;
    transition: opacity 0.15s ease;
}

.gm-style .gm-ui-hover-effect:hover {
    opacity: 1;
}

/* Ajustes para popups de Leaflet alineados con el diseño del CMS */
.leaflet-popup-content-wrapper {
    border-radius: 0.75rem !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1) !important;
    padding: 0.25rem !important;
}
.leaflet-popup-tip {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
}
</style>

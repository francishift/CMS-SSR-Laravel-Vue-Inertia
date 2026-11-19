<script setup>
/**
 * Componente LocationPickerModal del Page Builder.
 * Selector visual interactivo de coordenadas con Leaflet / OpenStreetMap:
 *  - Pin arrastrable (Draggable Marker) con precisión milimétrica.
 *  - Clic en cualquier punto del mapa para posicionar el pin.
 *  - Buscador de direcciones y lugares con Nominatim Geocoding.
 *  - Botón de geolocalización GPS del navegador.
 *  - Sincronización en tiempo real de Latitud, Longitud y Dirección.
 */
import { ref, watch, nextTick, onUnmounted } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    visible: { type: Boolean, required: true },
    initialLat: { type: [Number, String], default: 40.416775 },
    initialLng: { type: [Number, String], default: -3.703790 },
    initialAddress: { type: String, default: '' },
    title: { type: String, default: 'Ajustar Punto Exacto en el Mapa' },
});

const emit = defineEmits(['update:visible', 'seleccionar']);

const mapContainer = ref(null);
const mapInstance = ref(null);
const markerInstance = ref(null);

const latActual = ref(40.416775);
const lngActual = ref(-3.703790);
const direccionActual = ref('');
const busquedaTexto = ref('');
const buscando = ref(false);
const localizando = ref(false);
const errorMensaje = ref(null);

// Cargar Leaflet singleton en el navegador
const cargarLeaflet = () => {
    if (typeof window === 'undefined') return Promise.reject(new Error('SSR'));
    if (window.L) return Promise.resolve(window.L);

    if (window.__leafletLoadingPromise) {
        return window.__leafletLoadingPromise;
    }

    window.__leafletLoadingPromise = new Promise((resolve, reject) => {
        if (!document.getElementById('leaflet-css')) {
            const link = document.createElement('link');
            link.id = 'leaflet-css';
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
        }

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.async = true;
        script.onload = () => resolve(window.L);
        script.onerror = () => {
            window.__leafletLoadingPromise = null;
            reject(new Error('No se pudo cargar Leaflet para el selector visual.'));
        };
        document.head.appendChild(script);
    });

    return window.__leafletLoadingPromise;
};

// Limpiar mapa al cerrar
const destruirMapa = () => {
    if (mapInstance.value) {
        try {
            mapInstance.value.remove();
        } catch (e) {
            // Ignorar errores al desmontar
        }
        mapInstance.value = null;
    }
    markerInstance.value = null;
};

// Inicializar mapa en el modal
const inicializarMapaModal = async () => {
    if (!mapContainer.value) return;

    try {
        const L = await cargarLeaflet();

        destruirMapa();

        const latInit = Number(latActual.value) || 40.416775;
        const lngInit = Number(lngActual.value) || -3.703790;

        const map = L.map(mapContainer.value, {
            center: [latInit, lngInit],
            zoom: 16,
            zoomControl: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        // Crear marcador arrastrable con icono llamativo
        const marker = L.marker([latInit, lngInit], {
            draggable: true,
            autoPan: true,
        }).addTo(map);

        markerInstance.value = marker;
        mapInstance.value = map;

        // Actualizar coordenadas al arrastrar el marcador
        marker.on('drag', () => {
            const pos = marker.getLatLng();
            latActual.value = Number(pos.lat.toFixed(6));
            lngActual.value = Number(pos.lng.toFixed(6));
        });

        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            latActual.value = Number(pos.lat.toFixed(6));
            lngActual.value = Number(pos.lng.toFixed(6));
            // Opcional: obtener dirección reversa al soltar
            reverseGeocode(pos.lat, pos.lng);
        });

        // Al hacer clic en cualquier parte del mapa, mover el marcador
        map.on('click', (e) => {
            const { lat, lng } = e.latlng;
            marker.setLatLng([lat, lng]);
            latActual.value = Number(lat.toFixed(6));
            lngActual.value = Number(lng.toFixed(6));
            reverseGeocode(lat, lng);
        });

        // Asegurar cálculo correcto del tamaño en PrimeVue Dialog
        setTimeout(() => {
            if (mapInstance.value) {
                mapInstance.value.invalidateSize();
                mapInstance.value.setView([latInit, lngInit], 16);
            }
        }, 300);
    } catch (err) {
        errorMensaje.value = err.message || 'Error al cargar el selector de mapa.';
    }
};

// Geocodificación inversa con Nominatim (OSM)
const reverseGeocode = async (lat, lng) => {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
            headers: { 'Accept-Language': 'es' }
        });
        if (res.ok) {
            const data = await res.json();
            if (data && data.display_name) {
                // Formatear dirección corta
                const a = data.address || {};
                const calle = a.road || a.pedestrian || a.suburb || '';
                const numero = a.house_number ? ` ${a.house_number}` : '';
                const ciudad = a.city || a.town || a.village || a.municipality || '';
                if (calle && ciudad) {
                    direccionActual.value = `${calle}${numero}, ${ciudad}`;
                } else {
                    direccionActual.value = data.display_name.split(',').slice(0, 3).join(', ');
                }
            }
        }
    } catch (e) {
        // Fallo no crítico de reverse geocode
    }
};

// Búsqueda de dirección con Nominatim
const buscarDireccion = async () => {
    const q = busquedaTexto.value.trim();
    if (!q) return;

    buscando.value = true;
    errorMensaje.value = null;

    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1&addressdetails=1`;
        const res = await fetch(url, {
            headers: { 'Accept-Language': 'es' }
        });

        if (!res.ok) throw new Error('Error al conectar con el servicio de búsqueda.');

        const results = await res.json();
        if (results && results.length > 0) {
            const first = results[0];
            const lat = Number(first.lat);
            const lng = Number(first.lon);

            latActual.value = Number(lat.toFixed(6));
            lngActual.value = Number(lng.toFixed(6));
            direccionActual.value = first.display_name.split(',').slice(0, 3).join(', ');

            if (mapInstance.value && markerInstance.value) {
                markerInstance.value.setLatLng([lat, lng]);
                mapInstance.value.flyTo([lat, lng], 17, { duration: 1.2 });
            }
        } else {
            errorMensaje.value = 'No se encontró ningún resultado para esa dirección. Prueba con otra descripción o ciudad.';
        }
    } catch (err) {
        errorMensaje.value = 'No se pudo buscar la dirección. Verifica tu conexión o intenta más tarde.';
    } finally {
        buscando.value = false;
    }
};

// Obtener ubicación GPS actual del dispositivo
const usarMiUbicacion = () => {
    if (!navigator.geolocation) {
        alert('Tu navegador no soporta geolocalización GPS.');
        return;
    }

    localizando.value = true;
    errorMensaje.value = null;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            localizando.value = false;
            const lat = Number(position.coords.latitude.toFixed(6));
            const lng = Number(position.coords.longitude.toFixed(6));

            latActual.value = lat;
            lngActual.value = lng;

            if (mapInstance.value && markerInstance.value) {
                markerInstance.value.setLatLng([lat, lng]);
                mapInstance.value.flyTo([lat, lng], 17, { duration: 1 });
                reverseGeocode(lat, lng);
            }
        },
        (error) => {
            localizando.value = false;
            errorMensaje.value = 'No se pudo obtener tu ubicación actual. Revisa los permisos de ubicación de tu navegador.';
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
};

// Observar visibilidad del modal para inicializar o destruir
watch(() => props.visible, (val) => {
    if (val) {
        latActual.value = Number(props.initialLat) || 40.416775;
        lngActual.value = Number(props.initialLng) || -3.703790;
        direccionActual.value = props.initialAddress || '';
        busquedaTexto.value = props.initialAddress || '';
        errorMensaje.value = null;

        nextTick(() => {
            setTimeout(inicializarMapaModal, 150);
        });
    } else {
        destruirMapa();
    }
});

const cerrarModal = () => {
    emit('update:visible', false);
};

const confirmarSeleccion = () => {
    emit('seleccionar', {
        lat: Number(latActual.value),
        lng: Number(lngActual.value),
        address: direccionActual.value,
    });
    cerrarModal();
};

onUnmounted(() => {
    destruirMapa();
});
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="cerrarModal"
        modal
        :header="title"
        class="w-full max-w-4xl mx-4"
        :dismissableMask="true"
    >
        <div class="space-y-3.5">
            <!-- Barra superior de búsqueda y utilidades -->
            <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center justify-between">
                <form @submit.prevent="buscarDireccion" class="flex-1 flex gap-2">
                    <IconField class="flex-1">
                        <InputIcon class="pi pi-search" />
                        <InputText
                            v-model="busquedaTexto"
                            placeholder="Buscar calle, ciudad o punto de interés (ej. Gran Vía 12, Madrid)..."
                            class="w-full text-xs"
                            @keyup.enter="buscarDireccion"
                        />
                    </IconField>
                    <Button
                        type="submit"
                        label="Buscar"
                        icon="pi pi-search"
                        size="small"
                        severity="secondary"
                        :loading="buscando"
                    />
                </form>

                <Button
                    type="button"
                    label="Mi Ubicación GPS"
                    icon="pi pi-compass"
                    size="small"
                    severity="secondary"
                    variant="outlined"
                    :loading="localizando"
                    @click="usarMiUbicacion"
                    v-tooltip.top="'Centrar en tu ubicación física actual'"
                />
            </div>

            <!-- Instrucciones visuales de uso -->
            <div class="flex items-center justify-between bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 rounded-xl px-3 py-2 text-xs text-emerald-800 dark:text-emerald-300">
                <div class="flex items-center gap-2">
                    <i class="pi pi-info-circle text-emerald-600 dark:text-emerald-400" />
                    <span>
                        <strong>Ajuste fino:</strong> Arrastra el pin rojo o haz clic en cualquier lugar del mapa para fijar la coordenada exacta.
                    </span>
                </div>
                <span class="text-[11px] font-mono opacity-80 hidden md:inline">Precisión al metro</span>
            </div>

            <!-- Alerta de Error si ocurre -->
            <div v-if="errorMensaje" class="p-2.5 bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 rounded-lg text-xs flex items-center gap-2">
                <i class="pi pi-exclamation-circle text-red-500" />
                <span>{{ errorMensaje }}</span>
            </div>

            <!-- Contenedor del Mapa Interactivo -->
            <div class="relative w-full h-[430px] rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 shadow-inner">
                <div ref="mapContainer" class="w-full h-full z-0" />
            </div>

            <!-- Pie de información y confirmación -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <!-- Lectura en vivo de coordenadas -->
                <div class="flex flex-wrap items-center gap-2 text-xs w-full sm:w-auto">
                    <div class="px-2.5 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg font-mono">
                        <span class="text-zinc-500 text-[10px] uppercase font-bold mr-1">Lat:</span>
                        <strong class="text-emerald-600 dark:text-emerald-400">{{ latActual }}</strong>
                    </div>
                    <div class="px-2.5 py-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg font-mono">
                        <span class="text-zinc-500 text-[10px] uppercase font-bold mr-1">Lng:</span>
                        <strong class="text-emerald-600 dark:text-emerald-400">{{ lngActual }}</strong>
                    </div>
                    <span v-if="direccionActual" class="text-[11px] text-zinc-500 max-w-xs truncate" :title="direccionActual">
                        {{ direccionActual }}
                    </span>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <Button
                        label="Cancelar"
                        size="small"
                        severity="secondary"
                        variant="text"
                        @click="cerrarModal"
                    />
                    <Button
                        label="Fijar este Punto Exacto"
                        icon="pi pi-check"
                        size="small"
                        severity="primary"
                        class="bg-emerald-600 hover:bg-emerald-500 border-none text-white font-bold"
                        @click="confirmarSeleccion"
                    />
                </div>
            </div>
        </div>
    </Dialog>
</template>

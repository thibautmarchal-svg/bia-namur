<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

const props = defineProps({
    places: { type: Array, required: true },
    center: { type: Object, required: true },        // { lat, lng }
    boundingBox: { type: Object, default: null },    // { sw: {lat,lng}, ne: {lat,lng} }
    typesAvailable: { type: Array, default: () => [] },
    neighborhoodsAvailable: { type: Array, default: () => [] },
});

const TYPE_LABELS = {
    cafe: 'Café',
    restaurant: 'Restaurant',
    bar: 'Bar',
    boulangerie: 'Boulangerie',
    librairie: 'Librairie',
    patrimoine: 'Patrimoine',
    parc: 'Parc',
    marche: 'Marché',
    culture: 'Lieu culturel',
    hidden_gem: 'Hidden gem',
};

// Icônes simples par type (lucide minces, en SVG inline pour le marker)
const TYPE_ICONS = {
    cafe: '☕',
    restaurant: '🍽',
    bar: '🍷',
    boulangerie: '🥐',
    librairie: '📖',
    patrimoine: '🏛',
    parc: '🌳',
    marche: '🥕',
    culture: '🎭',
    hidden_gem: '✦',
};

const mapContainer = ref(null);
const map = ref(null);
const markers = ref([]);
const userLocation = ref(null);
const userLocationMarker = ref(null);
const geolocStatus = ref('idle');     // idle | requesting | granted | denied | error

// Filtres reactifs
const selectedTypes = ref(new Set());
const selectedNeighborhood = ref(null);

const filteredPlaces = computed(() =>
    props.places.filter((p) => {
        if (selectedTypes.value.size > 0 && ! selectedTypes.value.has(p.type)) return false;
        if (selectedNeighborhood.value && p.neighborhood !== selectedNeighborhood.value) return false;
        return true;
    })
);

// Style Maplibre : OSM raster avec ajustements vers tons Bia (legere desaturation + chaleur)
const biaStyle = {
    version: 8,
    sources: {
        osm: {
            type: 'raster',
            tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
            tileSize: 256,
            maxzoom: 19,
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        },
    },
    layers: [
        {
            id: 'osm',
            type: 'raster',
            source: 'osm',
            paint: {
                'raster-saturation': -0.25,
                'raster-brightness-min': 0.05,
                'raster-brightness-max': 0.95,
            },
        },
    ],
};

const buildMarkerEl = (place) => {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'bia-marker';
    el.setAttribute('aria-label', place.name);
    el.title = place.name;

    const dot = document.createElement('span');
    dot.className = 'bia-marker__dot';
    dot.textContent = TYPE_ICONS[place.type] ?? '◉';

    const tail = document.createElement('span');
    tail.className = 'bia-marker__tail';

    el.appendChild(dot);
    el.appendChild(tail);
    return el;
};

const buildPopupHtml = (place) => {
    const photo = place.cover_photo;
    const photoBlock = photo
        ? `<div class="bia-popup__photo"><img src="${photo.src_jpg || photo.url}" alt="${(photo.alt || place.name).replace(/"/g, '&quot;')}" loading="lazy" /></div>`
        : '';

    const tagsBlock = (place.tags || []).slice(0, 3).map(
        (t) => `<li>${t}</li>`
    ).join('');

    return `
        ${photoBlock}
        <div class="bia-popup__body">
            <p class="bia-popup__type">${TYPE_LABELS[place.type] ?? place.type}${place.neighborhood ? ` · ${place.neighborhood}` : ''}</p>
            <h3 class="bia-popup__name">${place.name}</h3>
            ${place.description ? `<p class="bia-popup__desc">${place.description.length > 140 ? place.description.slice(0, 140) + '…' : place.description}</p>` : ''}
            ${tagsBlock ? `<ul class="bia-popup__tags">${tagsBlock}</ul>` : ''}
            <a href="/lieu/${place.slug}" class="bia-popup__link">Découvrir →</a>
        </div>
    `;
};

const renderMarkers = () => {
    markers.value.forEach((m) => m.remove());
    markers.value = [];

    filteredPlaces.value.forEach((place) => {
        if (place.latitude == null || place.longitude == null) return;

        const popup = new maplibregl.Popup({
            offset: 24,
            closeButton: true,
            closeOnClick: true,
            maxWidth: '280px',
            className: 'bia-popup',
        }).setHTML(buildPopupHtml(place));

        const marker = new maplibregl.Marker({ element: buildMarkerEl(place), anchor: 'bottom' })
            .setLngLat([place.longitude, place.latitude])
            .setPopup(popup)
            .addTo(map.value);

        markers.value.push(marker);
    });
};

const requestGeolocation = () => {
    if (! ('geolocation' in navigator)) {
        geolocStatus.value = 'error';
        return;
    }
    geolocStatus.value = 'requesting';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            geolocStatus.value = 'granted';
            const coords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            userLocation.value = coords;

            if (userLocationMarker.value) userLocationMarker.value.remove();

            const dot = document.createElement('div');
            dot.className = 'bia-user-dot';
            userLocationMarker.value = new maplibregl.Marker({ element: dot, anchor: 'center' })
                .setLngLat([coords.lng, coords.lat])
                .addTo(map.value);

            map.value.flyTo({ center: [coords.lng, coords.lat], zoom: 14, duration: 800 });
        },
        () => { geolocStatus.value = 'denied'; },
        { timeout: 10000, maximumAge: 60000, enableHighAccuracy: false },
    );
};

const toggleType = (type) => {
    if (selectedTypes.value.has(type)) {
        selectedTypes.value.delete(type);
    } else {
        selectedTypes.value.add(type);
    }
    selectedTypes.value = new Set(selectedTypes.value);
};

const setNeighborhood = (n) => {
    selectedNeighborhood.value = selectedNeighborhood.value === n ? null : n;
};

const fitBounds = () => {
    if (! map.value || filteredPlaces.value.length === 0) return;
    const bounds = new maplibregl.LngLatBounds();
    filteredPlaces.value.forEach((p) => {
        if (p.latitude != null && p.longitude != null) {
            bounds.extend([p.longitude, p.latitude]);
        }
    });
    if (bounds.isEmpty()) return;
    map.value.fitBounds(bounds, { padding: 64, maxZoom: 15, duration: 600 });
};

onMounted(() => {
    map.value = new maplibregl.Map({
        container: mapContainer.value,
        style: biaStyle,
        center: [props.center.lng, props.center.lat],
        zoom: 13,
        attributionControl: { compact: true },
        cooperativeGestures: true,    // Ctrl+wheel pour zoomer (evite scroll-jacking)
    });

    map.value.addControl(new maplibregl.NavigationControl({ visualizePitch: false, showCompass: false }), 'top-right');

    map.value.on('load', () => {
        renderMarkers();
        // Si bbox ville fournie, fit sur la ville
        if (props.boundingBox?.sw && props.boundingBox?.ne) {
            map.value.fitBounds(
                [
                    [props.boundingBox.sw.lng, props.boundingBox.sw.lat],
                    [props.boundingBox.ne.lng, props.boundingBox.ne.lat],
                ],
                { padding: 48, maxZoom: 14, duration: 0 },
            );
        }
    });
});

onBeforeUnmount(() => {
    markers.value.forEach((m) => m.remove());
    if (userLocationMarker.value) userLocationMarker.value.remove();
    if (map.value) map.value.remove();
});

// Re-render quand les filtres changent
watch([filteredPlaces], () => {
    if (map.value && map.value.loaded()) {
        renderMarkers();
    }
});

const visibleCount = computed(() => filteredPlaces.value.length);
const totalCount = computed(() => props.places.length);
</script>

<template>
    <div class="map-shell">
        <!-- Filtres -->
        <div class="map-filters" role="region" aria-label="Filtres carte">
            <div class="map-filters__row">
                <span class="map-filters__label">Type</span>
                <button
                    v-for="type in typesAvailable"
                    :key="type"
                    type="button"
                    @click="toggleType(type)"
                    :class="[
                        'map-chip',
                        selectedTypes.has(type) ? 'map-chip--active' : '',
                    ]"
                    :aria-pressed="selectedTypes.has(type)"
                >
                    <span class="map-chip__icon" aria-hidden="true">{{ TYPE_ICONS[type] }}</span>
                    {{ TYPE_LABELS[type] ?? type }}
                </button>
            </div>
            <div v-if="neighborhoodsAvailable.length" class="map-filters__row">
                <span class="map-filters__label">Quartier</span>
                <button
                    v-for="n in neighborhoodsAvailable"
                    :key="n"
                    type="button"
                    @click="setNeighborhood(n)"
                    :class="[
                        'map-chip map-chip--ghost',
                        selectedNeighborhood === n ? 'map-chip--active' : '',
                    ]"
                    :aria-pressed="selectedNeighborhood === n"
                >
                    {{ n }}
                </button>
            </div>
            <div class="map-filters__row map-filters__row--end">
                <button
                    type="button"
                    @click="requestGeolocation"
                    class="map-chip map-chip--accent"
                    :disabled="geolocStatus === 'requesting'"
                >
                    <span aria-hidden="true">◎</span>
                    {{ geolocStatus === 'requesting' ? 'Localisation…' : 'Autour de moi' }}
                </button>
                <button
                    type="button"
                    @click="fitBounds"
                    class="map-chip map-chip--ghost"
                    title="Recentrer sur tous les lieux"
                >
                    <span aria-hidden="true">⊞</span>
                    Tout voir
                </button>
                <p class="map-counter" aria-live="polite">
                    <strong>{{ visibleCount }}</strong> / {{ totalCount }} lieux
                </p>
            </div>
            <p
                v-if="geolocStatus === 'denied'"
                class="map-filters__notice"
            >
                Géolocalisation refusée. Tu peux toujours naviguer la carte à la main.
            </p>
            <p
                v-else-if="geolocStatus === 'error'"
                class="map-filters__notice"
            >
                Géolocalisation indisponible sur ce navigateur.
            </p>
        </div>

        <!-- Carte -->
        <div ref="mapContainer" class="map-canvas" aria-label="Carte interactive de Namur" role="region" />
    </div>
</template>

<style scoped>
.map-shell {
    --map-height: 70vh;
    --map-min-height: 480px;
    width: 100%;
}
.map-canvas {
    width: 100%;
    height: var(--map-height);
    min-height: var(--map-min-height);
    border-radius: theme('borderRadius.card');
    overflow: hidden;
    background: theme('colors.bia.cream-dk');
    box-shadow: 0 1px 2px rgba(26, 20, 16, 0.04), 0 4px 16px rgba(26, 20, 16, 0.06);
}
.map-filters {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.map-filters__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}
.map-filters__row--end {
    margin-top: 0.25rem;
}
.map-filters__label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: theme('colors.bia.ink-mute');
    margin-right: 0.5rem;
}
.map-filters__notice {
    font-size: 0.875rem;
    font-style: italic;
    color: theme('colors.bia.ink-mute');
}
.map-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.85rem;
    border-radius: theme('borderRadius.pill');
    border: 1px solid theme('colors.bia.cream-dk');
    background: white;
    color: theme('colors.bia.ink-soft');
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 150ms ease;
}
.map-chip:hover {
    border-color: theme('colors.bia.primary');
    color: theme('colors.bia.primary-dk');
}
.map-chip--active {
    background: theme('colors.bia.primary');
    color: theme('colors.bia.cream');
    border-color: theme('colors.bia.primary');
}
.map-chip--active:hover {
    background: theme('colors.bia.primary-dk');
    color: theme('colors.bia.cream');
}
.map-chip--ghost {
    background: transparent;
}
.map-chip--accent {
    background: theme('colors.bia.cream-dk');
    border-color: theme('colors.bia.primary');
    color: theme('colors.bia.primary-dk');
}
.map-chip--accent:hover {
    background: theme('colors.bia.primary');
    color: theme('colors.bia.cream');
}
.map-chip__icon {
    font-size: 1rem;
    line-height: 1;
}
.map-counter {
    margin-left: auto;
    font-size: 0.875rem;
    color: theme('colors.bia.ink-mute');
}
.map-counter strong {
    color: theme('colors.bia.ink');
    font-weight: 600;
}

/* Marqueur custom Bia (selection .bia-marker injectee dans le DOM, pas scoped) */
:global(.bia-marker) {
    position: relative;
    width: 36px;
    height: 36px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    transform: translateY(-4px);
}
:global(.bia-marker__dot) {
    position: absolute;
    top: 0;
    left: 0;
    width: 36px;
    height: 36px;
    background: theme('colors.bia.primary');
    color: theme('colors.bia.cream');
    border: 2px solid theme('colors.bia.cream');
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    box-shadow: 0 4px 8px rgba(26, 20, 16, 0.25);
    transition: transform 150ms ease;
}
:global(.bia-marker:hover .bia-marker__dot),
:global(.bia-marker:focus-visible .bia-marker__dot) {
    transform: scale(1.1);
}
:global(.bia-marker__tail) {
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%) rotate(45deg);
    width: 8px;
    height: 8px;
    background: theme('colors.bia.primary');
}
:global(.bia-user-dot) {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #2563eb;
    border: 3px solid white;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.25), 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Popup styling (selectors globales car HTML injecte par Maplibre) */
:global(.bia-popup .maplibregl-popup-content) {
    padding: 0;
    border-radius: theme('borderRadius.card');
    overflow: hidden;
    background: white;
    box-shadow: 0 8px 24px rgba(26, 20, 16, 0.18);
    font-family: theme('fontFamily.sans');
}
:global(.bia-popup__photo) {
    width: 100%;
    aspect-ratio: 4 / 3;
    background: theme('colors.bia.cream-dk');
    overflow: hidden;
}
:global(.bia-popup__photo img) {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
:global(.bia-popup__body) {
    padding: 0.85rem 1rem 1rem;
}
:global(.bia-popup__type) {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: theme('colors.bia.primary');
    margin: 0 0 0.4rem;
}
:global(.bia-popup__name) {
    font-family: theme('fontFamily.serif');
    font-size: 1.1rem;
    font-weight: 500;
    color: theme('colors.bia.ink');
    margin: 0 0 0.5rem;
    line-height: 1.25;
}
:global(.bia-popup__desc) {
    font-size: 0.85rem;
    color: theme('colors.bia.ink-soft');
    line-height: 1.5;
    margin: 0 0 0.6rem;
}
:global(.bia-popup__tags) {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    list-style: none;
    padding: 0;
    margin: 0 0 0.7rem;
}
:global(.bia-popup__tags li) {
    font-size: 0.7rem;
    color: theme('colors.bia.ink-soft');
    background: theme('colors.bia.cream-dk');
    border-radius: theme('borderRadius.pill');
    padding: 0.15rem 0.55rem;
}
:global(.bia-popup__link) {
    display: inline-block;
    font-size: 0.85rem;
    font-weight: 500;
    color: theme('colors.bia.primary');
    text-decoration: none;
}
:global(.bia-popup__link:hover) {
    color: theme('colors.bia.primary-dk');
    text-decoration: underline;
}

/* Maplibre attribution + controls — discreets, palette Bia */
:global(.maplibregl-ctrl-attrib) {
    background: rgba(245, 237, 220, 0.85) !important;
    color: theme('colors.bia.ink-mute') !important;
    font-size: 0.7rem !important;
}
:global(.maplibregl-ctrl-attrib a) {
    color: theme('colors.bia.primary-dk') !important;
}
</style>

// ============================================================
// M8 · GIS Spatial Mapping Layer — Leaflet + OpenStreetMap.
// SIMULATED PostGIS: client-side polygon overlap via ST_Intersects-
// style ray-casting. Plots are SYNTHETIC polygons.
// ============================================================
import { getPlots, getOcrDocs } from './store.js';

/** Translation helper (no-op fallback outside the browser). */
const t = (key, replace) =>
    (typeof window !== 'undefined' && window.__t ? window.__t(key, replace) : key);

let map, layerGroup;

export function initMap(elId) {
    if (map) return map;
    map = L.map(elId).setView([23.813, 90.416], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: t('js.map_attribution'),
    }).addTo(map);
    layerGroup = L.layerGroup().addTo(map);
    return map;
}

function pointInRing(pt, ring) {
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const [xi, yi] = ring[i], [xj, yj] = ring[j];
        if ((yi > pt[1]) !== (yj > pt[1]) && pt[0] < (xj - xi) * (pt[1] - yi) / (yj - yi) + xi) inside = !inside;
    }
    return inside;
}

/** Simulated ST_Intersects: any vertex of A inside B or vice versa. */
export function intersects(a, b) {
    return a.some(p => pointInRing(p, b)) || b.some(p => pointInRing(p, a));
}

/** Find overlapping plot pairs (simulated PostGIS overlap query). */
export function findOverlaps() {
    const plots = getPlots();
    const overlaps = [];
    for (let i = 0; i < plots.length; i++)
        for (let j = i + 1; j < plots.length; j++)
            if (intersects(plots[i].polygon, plots[j].polygon))
                overlaps.push([plots[i].id, plots[j].id]);
    return overlaps;
}

const COLORS = {
    MUTATED: '#16a34a', PENDING_MUTATION: '#f59e0b', NONE: '#64748b',
};

export function renderPlots(onSelect) {
    layerGroup.clearLayers();
    const plots = getPlots();
    const overlaps = new Set(findOverlaps().flat());
    plots.forEach(p => {
        const color = overlaps.has(p.id) ? '#dc2626' : (COLORS[p.mutationStatus] || '#64748b');
        // Popup content is HTML: escape every interpolated value so synthetic
        // record data can never inject markup.
        const esc = (v) => (window.__esc ? window.__esc(v) : String(v));
        const overlapNote = overlaps.has(p.id)
            ? `<span style="color:red"><b>${esc(t('gis-map.popup_overlap'))}</b></span>`
            : '';
        const poly = L.polygon(p.polygon, { color, fillOpacity: 0.35, weight: 2 })
            .bindPopup(`<b>${esc(p.plotNo)}</b> (${esc(p.mouza)}, ${esc(p.khatian)})<br>
                ${esc(t('js.owner_synthetic', { owner: p.owner }))}<br>
                ${esc(t('common.mutation'))}: ${esc(p.mutationStatus)}<br>
                ${overlapNote}`)
            .on('click', () => onSelect?.(p));
        poly.addTo(layerGroup);
    });
    return { total: plots.length, overlaps: [...overlaps] };
}

export function focusPlot(plotId) {
    const p = getPlots().find(x => x.id === plotId);
    if (p && map) {
        map.fitBounds(L.latLngBounds(p.polygon));
        L.polyline(p.polygon, { color: '#2563eb', weight: 4, dashArray: '6 6' }).addTo(layerGroup);
    }
}

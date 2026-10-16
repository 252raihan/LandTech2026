// ============================================================
// SYNTHETIC DEMO DATASET — 100% fictional. No real persons/land records.
// Rule Book §8 compliance: dummy data only.
// ============================================================
import { savePlots, saveDeeds, saveBuyers, saveLiens, saveKyc, saveEvents, saveLedger, saveOcrDocs, saveMutations, saveLocks } from './store.js';

const rnd = (a, b) => Math.floor(Math.random() * (b - a + 1)) + a;
const pick = (arr) => arr[rnd(0, arr.length - 1)];

const FIRST = ['Rahim', 'Karim', 'Salam', 'Jamil', 'Nasrin', 'Fatema', 'Rashed', 'Habib', 'Selina', 'Mizan'];
const LAST = ['Uddin', 'Ahmed', 'Hossain', 'Bhuiyan', 'Chowdhury', 'Islam', 'Mia', 'Sarkar'];
const NAME = () => `${pick(FIRST)} ${pick(LAST)}`;
const NID = () => `SIM-${rnd(100000, 999999)}`; // clearly fake, prefixed SIM-

export function fakeName() { return NAME(); }
export function fakeNID() { return NID(); }

// Synthetic plot polygon around a fictional Dhaka-area coordinate cluster.
// The offset `d` scales deterministically per plot (instead of using
// Math.random()), which guarantees the seeded demo polygons do NOT overlap,
// so the Overlap Report shows a genuine "no overlaps" result unless a plot is
// deliberately drawn into another.
function plotPolygon(baseLat, baseLng, i) {
    const d = 0.00040 + (i % 5) * 0.00008;
    return [
        [baseLat, baseLng],
        [baseLat + d, baseLng],
        [baseLat + d, baseLng + d * 1.3],
        [baseLat, baseLng + d * 1.3],
        [baseLat, baseLng],
    ];
}

export function seedAll() {
    const base = { lat: 23.8103, lng: 90.4123 };
    const plots = [], deeds = [], buyers = [], liens = [], kyc = [], ocrDocs = [];

    for (let i = 1; i <= 12; i++) {
        const district = 'Dhaka', upazila = 'Savar', mouza = `Mouza-${String.fromCharCode(64 + ((i - 1) % 4) + 1)}`;
        const khatian = `BS-${rnd(1000, 9999)}`;
        const plotNo = `P-${100 + i}`;
        plots.push({
            id: `plot-${i}`,
            district, upazila, mouza, khatian, plotNo,
            redlockKey: `LOCK:LAND:${district}:${upazila}:${mouza}:${khatian}:${plotNo}`,
            areaDecimal: rnd(5, 40),
            owner: NAME(),
            ownerNid: NID(),
            survey: pick(['CS', 'SA', 'RS', 'BS']),
            mutationStatus: pick(['MUTATED', 'PENDING_MUTATION', 'NONE']),
            lat: base.lat + (i % 4) * 0.002,
            lng: base.lng + Math.floor(i / 4) * 0.0025,
            polygon: plotPolygon(base.lat + (i % 4) * 0.002, base.lng + Math.floor(i / 4) * 0.0025, i),
            riskScore: null,
            flags: [],
        });
    }

    // Intentional fraud scenarios for demonstration:
    // plot-3: sold twice (multiple sale) ; plot-7: rapid transfer chain ; plot-9: bank lien
    const scenarios = [
        { plot: 'plot-3', buyer2: true },
        { plot: 'plot-7', rapidChain: true },
        { plot: 'plot-9', lien: true },
    ];

    scenarios.forEach(s => {
        const plot = plots.find(p => p.id === s.plot);
        if (s.lien) liens.push({
            id: 'lien-1', plotId: s.plot, bank: 'SIM-Bank Ltd (fictional)',
            amount: rnd(20, 80) * 100000, status: 'ACTIVE',
            createdAt: new Date(Date.now() - 86400000 * 30).toISOString(),
        });
    });

    // Seed 15 deeds across plots (some plots get 2-3 deeds = multiple sale)
    plots.forEach((plot, i) => {
        const nDeeds = (plot.id === 'plot-3') ? 2 : (plot.id === 'plot-7') ? 3 : 1;
        for (let d = 0; d < nDeeds; d++) {
            const daysAgo = (plot.id === 'plot-7') ? d * 3 + 1 : rnd(30, 900);
            const deed = {
                id: `deed-${i}-${d}`,
                plotId: plot.id,
                deedNo: `DL-${rnd(10000, 99999)}`,
                seller: d === 0 ? plot.owner : NAME(),
                sellerNid: NID(),
                buyer: NAME(),
                buyerNid: NID(),
                registeredAt: new Date(Date.now() - daysAgo * 86400000).toISOString(),
                registryOffice: 'Savar Sub-Registry (simulated)',
                hash: null, // computed later by crypto module
                mutationStatus: plot.mutationStatus,
                flags: [],
            };
            deeds.push(deed);
            buyers.push({ id: `buyer-${i}-${d}`, name: deed.buyer, nid: deed.buyerNid, deedId: deed.id, plotId: plot.id });
        }
    });

    savePlots(plots); saveDeeds(deeds); saveBuyers(buyers); saveLiens(liens);
    saveKyc(kyc); saveEvents([]); saveLedger([]); saveOcrDocs(ocrDocs); saveMutations([]); saveLocks({});
}

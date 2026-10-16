// ============================================================
// M5 · Bank Lien & Encumbrance Registry — SIMULATED CIB/CBS link.
// Blocks registration on plots with active liens until e-NOC.
// ============================================================
import { getLiens, saveLiens, getPlots, pushEvent, uid, nowISO } from './store.js';
import { appendLedger } from './crypto.js';

// Re-export the registry accessors so page scripts can import the whole
// lien API from one module (liens.js), matching how the pages already use it.
export { getLiens, saveLiens, getPlots };

export function activeLiensForPlot(plotId) {
    return getLiens().filter(l => l.plotId === plotId && l.status === 'ACTIVE');
}

export function plotHasActiveLien(plotId) {
    return activeLiensForPlot(plotId).length > 0;
}

export function addLien(plotId, bank, amount) {
    const lien = {
        id: uid('lien'), plotId, bank, amount: Number(amount),
        status: 'ACTIVE', createdAt: nowISO(),
        source: 'SIMULATED-CIB (not connected to any bank)',
    };
    const liens = getLiens();
    liens.push(lien);
    saveLiens(liens);
    pushEvent('LIEN_REGISTERED', { plotId, bank, amount });
    appendLedger('LIEN_REGISTERED', { plotId, bank, amount });
    return lien;
}

/** e-NOC: simulated electronic No-Objection Certificate releasing the lien. */
export function issueENOC(lienId) {
    const liens = getLiens();
    const lien = liens.find(l => l.id === lienId);
    if (!lien || lien.status !== 'ACTIVE') return null;
    lien.status = 'RELEASED';
    lien.releasedAt = nowISO();
    lien.eNoc = `eNOC-SIM-${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
    saveLiens(liens);
    pushEvent('LIEN_RELEASED', { plotId: lien.plotId, eNoc: lien.eNoc });
    appendLedger('LIEN_RELEASED', { plotId: lien.plotId, eNoc: lien.eNoc });
    return lien;
}

export function registrationBlockedByLien(plotId) {
    return plotHasActiveLien(plotId)
        ? 'BLOCKED: active bank lien — e-NOC required before registration (simulated CIB check)'
        : null;
}

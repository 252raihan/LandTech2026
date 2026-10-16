// ============================================================
// M2 · Cryptographic Verification Engine (REAL Web Crypto SHA-256)
// + M10 · Immutable Audit Ledger (hash-chained, browser-side)
// Real crypto primitives; storage/simulation only.
// ============================================================
import { getDeeds, saveDeeds, getLedger, saveLedger, uid, nowISO, pushEvent } from './store.js';

async function sha256Hex(text) {
    const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text));
    return [...new Uint8Array(buf)].map(b => b.toString(16).padStart(2, '0')).join('');
}

/** Canonical deed payload → SHA-256 document hash (REAL crypto). */
export async function computeDeedHash(deed) {
    const canonical = JSON.stringify({
        deedNo: deed.deedNo, plotId: deed.plotId, seller: deed.seller,
        sellerNid: deed.sellerNid, buyer: deed.buyer, buyerNid: deed.buyerNid,
        registeredAt: deed.registeredAt, registryOffice: deed.registryOffice,
    });
    return await sha256Hex(canonical);
}

/** Hash & register all deeds lacking a hash. */
export async function hashAllDeeds() {
    const deeds = getDeeds();
    let count = 0;
    for (const d of deeds) {
        if (!d.hash) { d.hash = await computeDeedHash(d); count++; }
    }
    saveDeeds(deeds);
    if (count) pushEvent('DEED_HASHED', { count });
    return count;
}

/** Simulated dynamic QR payload (SHA-256 based). */
export async function dynamicQrPayload(deed) {
    if (!deed.hash) deed.hash = await computeDeedHash(deed);
    const ts = Date.now();
    const sig = await sha256Hex(deed.hash + ts);
    return `LT26|${deed.deedNo}|${deed.hash.slice(0, 16)}|${ts}|${sig.slice(0, 16)}`;
}

/** Verify a pasted deed content hash against registered hash. */
export async function verifyDeed(deedId, pastedJson) {
    const deeds = getDeeds();
    const deed = deeds.find(d => d.id === deedId);
    if (!deed || !deed.hash) return { ok: false, reason: 'Deed not found or not hashed' };
    const recomputed = await sha256Hex(pastedJson.trim());
    const match = recomputed === deed.hash;
    appendLedger(match ? 'DEED_VERIFIED_OK' : 'DEED_TAMPER_DETECTED', { deedId, match });
    return { ok: match, recomputed, registered: deed.hash };
}

// ---- M10 Append-only hash-chained ledger ------------------------
export async function appendLedger(action, data) {
    const ledger = getLedger();
    const prevHash = ledger.length ? ledger[ledger.length - 1].blockHash : 'GENESIS';
    const entry = {
        id: uid('blk'), seq: ledger.length + 1, ts: nowISO(),
        action, data, prevHash,
    };
    entry.blockHash = await sha256Hex(prevHash + JSON.stringify({ action, data, seq: entry.seq, ts: entry.ts }));
    ledger.push(entry);
    saveLedger(ledger);
    return entry;
}

/** Verify full chain integrity (recompute hashes). */
export async function verifyLedgerChain() {
    const ledger = getLedger();
    let prev = 'GENESIS';
    for (const e of ledger) {
        const expected = await sha256Hex(prev + JSON.stringify({ action: e.action, data: e.data, seq: e.seq, ts: e.ts }));
        if (expected !== e.blockHash) return { ok: false, brokenAt: e.seq };
        if (e.prevHash !== prev) return { ok: false, brokenAt: e.seq };
        prev = e.blockHash;
    }
    return { ok: true, blocks: ledger.length };
}

/** Simulated tamper test: mutate a block to demo chain break detection. */
export function tamperLedger() {
    const ledger = getLedger();
    if (!ledger.length) return false;
    const idx = Math.floor(ledger.length / 2);
    ledger[idx].data = { ...ledger[idx].data, tampered: 'SIMULATED-ATTACK' };
    saveLedger(ledger);
    return true;
}

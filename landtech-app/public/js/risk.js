// ============================================================
// M9 · Public Risk Scoring Engine — rule-based, client-side.
// Aggregates signals from M2–M8 into a 0–100 public risk score.
// ============================================================
import { getPlots, savePlots, getDeeds, getLiens, getKyc, getOcrDocs } from './store.js';

/**
 * Translation helper. Falls back to the key itself when the dictionary is not
 * loaded (e.g. a unit-test context), so modules never throw on a missing __t.
 */
const t = (key, replace) =>
    (typeof window !== 'undefined' && window.__t ? window.__t(key, replace) : key);

/**
 * Score plot: lower = safer. Signals:
 *  +40 multiple sale · +25 active lien · +20 pending mutation
 *  +15 rapid transfer · +10 unverified (no KYC) · +10 khas/acquisition status
 */
export function scorePlot(plotId) {
    const deeds = getDeeds().filter(d => d.plotId === plotId);
    const liens = getLiens().filter(l => l.plotId === plotId && l.status === 'ACTIVE');
    const kycDone = getKyc().some(k => deeds.some(d => d.buyerNid === k.nid) && k.status === 'VERIFIED');
    const plot = getPlots().find(p => p.id === plotId);
    const lastScan = getOcrDocs()[0];
    const flags = lastScan?.conflicts?.filter(c => c.plotId === plotId) ?? [];

    let score = 0;
    const reasons = [];
    const buyers = new Set(deeds.map(d => d.buyerNid));
    if (deeds.length > 1) { score += 40; reasons.push(t('risk-scoring.reason_multiple_buyers')); }
    if (liens.length) { score += 25; reasons.push(t('risk-scoring.reason_active_lien')); }
    if (plot?.mutationStatus === 'PENDING_MUTATION') { score += 20; reasons.push(t('risk-scoring.reason_pending_mutation')); }
    if (flags.some(f => f.type === 'RAPID_TRANSFER')) { score += 15; reasons.push(t('risk-scoring.reason_rapid_transfer')); }
    if (!kycDone) { score += 10; reasons.push(t('risk-scoring.reason_no_kyc')); }
    if (plot?.survey === 'NONE') { score += 10; reasons.push(t('risk-scoring.reason_no_survey')); }
    score = Math.min(score, 100);
    return { plotId, score, band: bandOf(score), reasons };
}

function bandOf(s) {
    if (s >= 60) return { label: t('risk-scoring.band_high'), color: 'red' };
    if (s >= 30) return { label: t('risk-scoring.band_caution'), color: 'amber' };
    return { label: t('risk-scoring.band_low'), color: 'green' };
}

/** Score every plot and persist. */
export function scoreAll() {
    const plots = getPlots();
    plots.forEach(p => {
        const r = scorePlot(p.id);
        p.riskScore = r.score;
        p.flags = r.reasons;
    });
    savePlots(plots);
    return plots.map(p => ({ plotNo: p.plotNo, id: p.id, score: p.riskScore, band: bandOf(p.riskScore) }));
}

/** Public pre-purchase check ("check before you buy"). */
export function publicCheck(plotId) {
    const plot = getPlots().find(p => p.id === plotId);
    if (!plot) return null;
    const r = scorePlot(plotId);
    const deeds = getDeeds().filter(d => d.plotId === plotId);
    return {
        plot,
        risk: r,
        deedCount: deeds.length,
        mutation: plot.mutationStatus,
        khasOrAcquisition: plot.survey === 'NONE' ? t('risk-scoring.khas') : t('risk-scoring.registered'),
        pendingCases: r.reasons.filter(x => x.includes('lien') || x.includes('multiple')),
    };
}

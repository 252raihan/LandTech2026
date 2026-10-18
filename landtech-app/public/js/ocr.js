// ============================================================
// M6 · AI/OCR Conflict Detection Engine — SIMULATED (rule-based).
// No real OCR/AI model. Detects: multiple buyers, rapid transfers,
// registration-vs-mutation mismatch, survey-generation conflicts.
// ============================================================
import { getDeeds, getPlots, getOcrDocs, saveOcrDocs, pushEvent, uid, nowISO } from './store.js';
import { appendLedger } from './crypto.js';

/** Translation helper (no-op fallback outside the browser). */
const t = (key, replace) =>
    (typeof window !== 'undefined' && window.__t ? window.__t(key, replace) : key);

const MS_PER_DAY = 86400000;

/** Simulated OCR extraction: returns parsed fields with random-ish "scan" confidence. */
export function simulateOcrExtract(deedId) {
    const deed = getDeeds().find(d => d.id === deedId);
    if (!deed) return null;
    return {
        deedId,
        extracted: {
            deedNo: deed.deedNo,
            seller: deed.seller,
            buyer: deed.buyer,
            registeredAt: deed.registeredAt,
            survey: 'BS', // "OCR-read" survey generation
        },
        confidence: 0.82 + Math.random() * 0.15,
        engine: t('js.ocr_engine'),
    };
}

/** Core conflict engine: scans all deeds + plots. */
export async function detectConflicts() {
    const deeds = getDeeds();
    const plots = getPlots();
    const conflicts = [];

    // Group deeds by plot
    const byPlot = {};
    deeds.forEach(d => { (byPlot[d.plotId] ??= []).push(d); });

    for (const [plotId, pd] of Object.entries(byPlot)) {
        const plot = plots.find(p => p.id === plotId);
        pd.sort((a, b) => new Date(a.registeredAt) - new Date(b.registeredAt));

        // 1) Multiple sale: >1 buyer for same plot without mutation completion
        if (pd.length > 1) {
            conflicts.push({
                type: 'MULTIPLE_SALE',
                severity: 'CRITICAL',
                plotId, plotNo: plot?.plotNo ?? plotId,
                detail: t('ai-ocr-conflict.detail_multiple_sale', { count: pd.length, deeds: pd.map(d => d.deedNo).join(', ') }),
                deedIds: pd.map(d => d.id),
            });
        }

        // 2) Rapid transfer chain: successive deeds < 30 days apart
        for (let i = 1; i < pd.length; i++) {
            const gap = (new Date(pd[i].registeredAt) - new Date(pd[i - 1].registeredAt)) / MS_PER_DAY;
            if (gap < 30) {
                conflicts.push({
                    type: 'RAPID_TRANSFER',
                    severity: 'HIGH',
                    plotId, plotNo: plot?.plotNo ?? plotId,
                    detail: t('ai-ocr-conflict.detail_rapid_transfer', { days: Math.round(gap), from: pd[i - 1].deedNo, to: pd[i].deedNo }),
                    deedIds: [pd[i - 1].id, pd[i].id],
                });
            }
        }

        // 3) Registration-vs-mutation mismatch
        if (plot && plot.mutationStatus === 'PENDING_MUTATION' && pd.length > 0) {
            conflicts.push({
                type: 'MUTATION_MISMATCH',
                severity: 'MEDIUM',
                plotId, plotNo: plot.plotNo,
                detail: t('ai-ocr-conflict.detail_mutation_mismatch'),
                deedIds: pd.map(d => d.id),
            });
        }

        // 4) Survey generation conflict (CS/SA/RS/BS mismatch — simulated)
        if (plot && plot.survey !== 'BS' && pd.some(d => d.flags?.includes('OCR_BS'))) {
            conflicts.push({
                type: 'SURVEY_CONFLICT',
                severity: 'LOW',
                plotId, plotNo: plot.plotNo,
                detail: t('ai-ocr-conflict.detail_survey_conflict', { survey: plot.survey }),
                deedIds: pd.map(d => d.id),
            });
        }
    }

    // Persist an OCR doc record + audit entry for the latest run
    const docs = getOcrDocs();
    docs.unshift({ id: uid('ocr'), ts: nowISO(), conflicts, engine: t('js.ocr_engine') });
    saveOcrDocs(docs.slice(0, 50));
    if (conflicts.length) await appendLedger('CONFLICTS_DETECTED', { count: conflicts.length });
    pushEvent('OCR_SCAN_COMPLETED', { conflicts: conflicts.length });
    return conflicts;
}

export function getLatestScan() {
    const docs = getOcrDocs();
    return docs.length ? docs[0] : null;
}

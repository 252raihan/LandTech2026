// ============================================================
// M7 · Faraz Share Calculator — enforces Σ Share_i = 1.0 and
// S_transfer ≤ S_legal. Pure client-side math.
// ============================================================

/** Translation helper (no-op fallback outside the browser). */
const t = (key, replace) =>
    (typeof window !== 'undefined' && window.__t ? window.__t(key, replace) : key);

/** Validate faraz shares sum to exactly 1.0 */
export function validateShares(shares) {
    const sum = shares.reduce((a, s) => a + Number(s.share || 0), 0);
    return {
        sum: Number(sum.toFixed(6)),
        valid: Math.abs(sum - 1.0) < 1e-6,
    };
}

/**
 * Validate a proposed transfer against the legal (inherited/owned) share.
 * @param legalShares  [{holder, share}] legal ownership breakdown (fractions)
 * @param transfer     {seller, share}   proposed transfer fraction
 */
export function validateTransfer(legalShares, transfer) {
    const sellerShare = legalShares.find(s => s.holder === transfer.seller)?.share ?? 0;
    const exceeds = transfer.share > sellerShare + 1e-9;
    return {
        sellerLegalShare: sellerShare,
        transferShare: transfer.share,
        valid: !exceeds,
        message: exceeds
            ? t('faraz-calculator.invalid_transfer', { share: transfer.share, legal: sellerShare })
            : t('faraz-calculator.valid_transfer', { share: transfer.share, legal: sellerShare }),
    };
}

/** Auto-split an estate equally among heirs. */
export function equalSplit(heirs) {
    const s = Number((1 / heirs.length).toFixed(6));
    const shares = heirs.map(h => ({ holder: h, share: s }));
    // adjust rounding remainder on last heir
    const diff = 1 - shares.reduce((a, x) => a + x.share, 0);
    shares[shares.length - 1].share = Number((shares.at(-1).share + diff).toFixed(6));
    return shares;
}

/** Convert fraction to "shata/ankor" style display (16 Ankra system). */
export function fractionToAnkra(f) {
    const anka = Math.floor(f * 16);
    const shata = Math.round((f * 16 - anka) * 100);
    return t('js.ankra_format', { anka, shata });
}

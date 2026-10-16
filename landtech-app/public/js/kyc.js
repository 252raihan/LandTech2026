// ============================================================
// M4 · e-KYC & Biometric Gateway — SIMULATED Porichoy API.
// Optional real camera preview via getUserMedia (photo NOT uploaded
// anywhere; stays in the browser). Dual-confirmation model demo.
// ============================================================
import { getKyc, saveKyc, pushEvent, uid, nowISO } from './store.js';
import { appendLedger } from './crypto.js';

/** Simulated Porichoy verification — deterministic pass on SIM- NIDs with >85% confidence roll. */
export function verifyKyc(nid, name) {
    const result = {
        id: uid('kyc'), nid, name, ts: nowISO(),
        provider: 'SIMULATED-Porichoy (no real API call)',
        status: null, confidence: null,
        faceMatch: null, liveness: null,
    };
    if (!/^SIM-\d{6}$/.test(nid)) {
        result.status = 'FAILED_FORMAT';
        result.confidence = 0;
    } else {
        result.confidence = 0.75 + Math.random() * 0.24;
        result.faceMatch = result.confidence > 0.8;
        result.liveness = Math.random() > 0.1; // 90% pass rate
        result.status = (result.faceMatch && result.liveness) ? 'VERIFIED' : 'REJECTED';
    }
    const kyc = getKyc();
    kyc.unshift(result);
    saveKyc(kyc.slice(0, 100));
    pushEvent('KYC_RESULT', { nid, status: result.status });
    appendLedger('KYC_' + result.status, { nid, confidence: result.confidence });
    return result;
}

// ---- Real browser camera (optional, real getUserMedia) -----------
let stream = null;
export async function startCamera(videoEl) {
    stream = await navigator.mediaDevices.getUserMedia({ video: true });
    videoEl.srcObject = stream;
    return true;
}
export function stopCamera(videoEl) {
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    if (videoEl) videoEl.srcObject = null;
}
export function capturePhoto(videoEl, canvasEl) {
    const ctx = canvasEl.getContext('2d');
    canvasEl.width = videoEl.videoWidth || 320;
    canvasEl.height = videoEl.videoHeight || 240;
    ctx.drawImage(videoEl, 0, 0, canvasEl.width, canvasEl.height);
    return canvasEl.toDataURL('image/jpeg', 0.8); // stays local only
}

export function getKycHistory() { return getKyc(); }

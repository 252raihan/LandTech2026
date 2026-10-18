@extends('layouts.app')
@section('title', __('pages.ekyc-biometric.title')) @section('page-title', __('pages.ekyc-biometric.page_title'))
@section('page-sub', __('pages.ekyc-biometric.page_sub'))

@push('scripts')
<script type="module">
import { verifyKyc, startCamera, stopCamera, capturePhoto, getKycHistory } from '/js/kyc.js';
import { toast, fmtTime } from '/js/ui.js';

const video = document.getElementById('cam'), canvas = document.getElementById('snap');

document.getElementById('btn-cam').onclick = async () => {
    try {
        await startCamera(video);
        video.classList.remove('hidden');
        document.getElementById('cam-placeholder').classList.add('hidden');
        document.getElementById('cam-note').textContent = __t('pages.ekyc-biometric.camera_active');
    } catch (e) {
        document.getElementById('cam-note').textContent =
            __t('pages.ekyc-biometric.camera_unavailable', { message: e.message });
    }
};

document.getElementById('btn-cam-off').onclick = () => {
    stopCamera(video);
    video.classList.add('hidden');
    document.getElementById('cam-placeholder').classList.remove('hidden');
    document.getElementById('cam-note').textContent = __t('pages.ekyc-biometric.camera_stopped');
};

document.getElementById('btn-snap').onclick = () => {
    if (video.classList.contains('hidden') || !video.videoWidth) {
        document.getElementById('cam-note').textContent = __t('pages.ekyc-biometric.capture_needs_camera');
        return;
    }
    capturePhoto(video, canvas);
    canvas.classList.remove('hidden');
    document.getElementById('cam-note').textContent = __t('pages.ekyc-biometric.captured_local');
};

document.getElementById('btn-kyc').onclick = () => {
    const nid = document.getElementById('kyc-nid').value || __t('pages.saga.default_buyer_nid');
    const name = document.getElementById('kyc-name').value || __t('pages.saga.default_buyer_name');
    const r = verifyKyc(nid, name);
    const ok = r.status === 'VERIFIED';

    document.getElementById('kyc-out').innerHTML = `
        <div class="alert ${ok ? 'alert-success' : 'alert-danger'}" role="status">
            <span class="badge ${ok ? 'badge-green' : 'badge-red'} shrink-0">${__esc(ok ? __t('common.verified') : __t('common.rejected'))}</span>
            <div class="min-w-0">
                <p>${__tHtml('pages.ekyc-biometric.confidence', { percent: (r.confidence * 100).toFixed(1) })}</p>
                <p class="text-xs">${__tHtml('pages.ekyc-biometric.face_match', { value: r.faceMatch ?? __t('pages.ekyc-biometric.na') })} · ${__tHtml('pages.ekyc-biometric.liveness', { value: r.liveness ?? __t('pages.ekyc-biometric.na') })}</p>
                <p class="mt-1"><em class="text-xs not-italic text-slate-500">${__esc(r.provider)}</em></p>
            </div>
        </div>`;
    toast(ok ? __t('pages.ekyc-biometric.verified_toast') : __t('pages.ekyc-biometric.rejected_toast'), ok ? 'success' : 'error');
    renderHistory();
};

function renderHistory() {
    const history = getKycHistory().slice(0, 10);
    document.getElementById('kyc-history').innerHTML = history.length
        ? history.map(k => `
            <tr>
                <td class="mono">${__esc(k.nid)}</td>
                <td>${__esc(k.name)}</td>
                <td><span class="badge ${k.status === 'VERIFIED' ? 'badge-green' : 'badge-red'}">${__esc(k.status === 'VERIFIED' ? __t('common.verified') : __t('common.rejected'))}</span></td>
                <td class="whitespace-nowrap text-xs text-slate-500">${__esc(fmtTime(k.ts))}</td>
            </tr>`).join('')
        : `<tr><td colspan="4" class="table-empty">${__esc(__t('pages.ekyc-biometric.history_empty'))}</td></tr>`;
}
renderHistory();
</script>
@endpush

@section('content')
<x-page-header icon="id-card" :title="__('pages.ekyc-biometric.page_title')" :subtitle="__('pages.ekyc-biometric.page_sub')" />

<div class="grid gap-6 lg:grid-cols-2">
    <section class="card" aria-labelledby="kyc-form-title">
        <div class="card-header">
            <div>
                <h2 id="kyc-form-title" class="card-title">{{ __('pages.ekyc-biometric.form_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.ekyc-biometric.hint', ['format' => 'SIM-XXXXXX']) }}</p>
            </div>
            <span class="sim-badge">{{ __('pages.ekyc-biometric.badge_no_api') }}</span>
        </div>
        <div class="card-body space-y-4">
            <div>
                <label for="kyc-nid" class="label">{{ __('pages.ekyc-biometric.nid_label') }}</label>
                <input id="kyc-nid" class="input font-mono" placeholder="SIM-123456" inputmode="text" autocomplete="off">
            </div>
            <div>
                <label for="kyc-name" class="label">{{ __('pages.ekyc-biometric.name_label') }}</label>
                <input id="kyc-name" class="input" placeholder="{{ __('pages.ekyc-biometric.placeholder_name') }}" autocomplete="off">
            </div>
            <button type="button" id="btn-kyc" class="btn-primary">
                <x-icon name="id-card" class="h-4 w-4" />
                <span>{{ __('pages.ekyc-biometric.run') }}</span>
            </button>
            <div id="kyc-out" role="status"></div>
        </div>
    </section>

    <section class="card" aria-labelledby="kyc-camera-title">
        <div class="card-header">
            <div>
                <h2 id="kyc-camera-title" class="card-title">{{ __('pages.ekyc-biometric.camera_heading') }}</h2>
                <p class="text-xs text-slate-400">{{ __('pages.ekyc-biometric.camera_note') }}</p>
            </div>
            <span class="sim-badge">{{ __('pages.ekyc-biometric.badge_no_upload') }}</span>
        </div>
        <div class="card-body space-y-3">
            <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-navy-950">
                <video id="cam" class="hidden w-full" autoplay playsinline muted></video>
                <div id="cam-placeholder" class="flex h-48 items-center justify-center px-6 text-center text-xs text-slate-400">
                    {{ __('pages.ekyc-biometric.camera_placeholder') }}
                </div>
            </div>
            <canvas id="snap" class="hidden w-40 rounded-lg border border-slate-200" aria-hidden="true"></canvas>
            <div class="flex flex-wrap gap-2">
                <button type="button" id="btn-cam" class="btn-primary btn-sm">
                    <x-icon name="camera" class="h-4 w-4" />
                    <span>{{ __('pages.ekyc-biometric.start_camera') }}</span>
                </button>
                <button type="button" id="btn-snap" class="btn-ghost btn-sm">{{ __('pages.ekyc-biometric.capture') }}</button>
                <button type="button" id="btn-cam-off" class="btn-ghost btn-sm">{{ __('pages.ekyc-biometric.stop') }}</button>
            </div>
            <p id="cam-note" class="text-xs text-slate-500" role="status"></p>
        </div>
    </section>
</div>

<section class="card mt-6" aria-labelledby="kyc-history-title">
    <div class="card-header">
        <h2 id="kyc-history-title" class="card-title">{{ __('pages.ekyc-biometric.history_heading') }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table min-w-[560px]">
            <thead>
                <tr>
                    <th scope="col">{{ __('pages.ekyc-biometric.col_nid') }}</th>
                    <th scope="col">{{ __('pages.ekyc-biometric.col_name') }}</th>
                    <th scope="col">{{ __('pages.ekyc-biometric.col_status') }}</th>
                    <th scope="col">{{ __('pages.ekyc-biometric.col_time') }}</th>
                </tr>
            </thead>
            <tbody id="kyc-history"></tbody>
        </table>
    </div>
</section>
@endsection

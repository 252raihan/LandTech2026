<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
| LandTech 2026 — P3: Fraud & Multiple Sale Detection
|
| TWO LAYERS:
|   - The public M1–M10 demo pages (see $pages below) are browser-side
|     simulations (localStorage, synthetic data — see public/js/).
|   - The Laravel application below is real and database-backed: Breeze
|     authentication, `role:` middleware, controller-backed routes and
|     migrations.
|
| Reviewer workflows (officer review/approval, link approval, listing
| publication, record verification) are intentionally not implemented yet —
| submitted items remain `pending` / `pending_review` / `submitted`. See
| README §2b.
|
| History: Phase 1 added locale switching (en/bn, session-persisted) and the
| /lang.js translation loader.
*/

$pages = [
    'dashboard' => 'Dashboard Overview',
    'saga' => 'Saga Orchestration',
    'sync-webhook' => 'M1 · Sync & Webhook Layer',
    'crypto-verify' => 'M2 · Cryptographic Verification',
    'plot-lock' => 'M3 · Real-Time Plot Lock',
    'ekyc-biometric' => 'M4 · e-KYC & Biometric Gateway',
    'bank-lien' => 'M5 · Bank Lien Registry',
    'ai-ocr-conflict' => 'M6 · AI/OCR Conflict Detection',
    'faraz-calculator' => 'M7 · Faraz Share Calculator',
    'gis-map' => 'M8 · GIS Spatial Mapping',
    'risk-scoring' => 'M9 · Public Risk Scoring',
    'audit-ledger' => 'M10 · Immutable Audit Ledger',
];

foreach ($pages as $slug => $title) {
    Route::view("/{$slug}", "pages.{$slug}")->name($slug);
}

/*
| Root entry point — public Welcome / Login landing page.
|
| Guests are NOT redirected to the dashboard any more: `/` now serves the
| marketing/landing page, which links to the existing Breeze login route and
| (in local/testing Demo Mode) to the role panels. Authenticated users may also
| visit `/` to return here; the page adapts rather than redirecting.
|
| The public M1–M10 demo pages and the /dashboard demo route below are
| unchanged and remain publicly reachable.
*/
Route::get('/', \App\Http\Controllers\WelcomeController::class)->name('welcome');

Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');
Route::get('/lang.js', [LocaleController::class, 'script'])->name('locale.script');

/*
| Phase 2 additions: authentication (see routes/auth.php) and the protected
| /profile group. All 12 demo pages above remain public — only auth flows and
| /profile require a session.
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
| Citizen/User Panel — self-service pages for ordinary account holders.
|
| Authorization is ENFORCED SERVER-SIDE: every route carries auth + role:citizen.
| Hiding menu items is never treated as access control, so a user who manually
| types a URL still receives 403 unless they hold the citizen role. Admin and
| officer roles are deliberately NOT granted access to this group either — each
| panel is scoped to its own audience.
*/
Route::middleware(['auth', 'role:'.\App\Models\Role::CITIZEN])->prefix('citizen')->name('citizen.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Citizen\CitizenPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/land', [\App\Http\Controllers\Citizen\CitizenPanelController::class, 'land'])->name('land');
    Route::get('/deeds', [\App\Http\Controllers\Citizen\CitizenPanelController::class, 'deeds'])->name('deeds');
    Route::get('/ownership', [\App\Http\Controllers\Citizen\CitizenPanelController::class, 'ownership'])->name('ownership');
    Route::get('/applications', [\App\Http\Controllers\Citizen\CitizenPanelController::class, 'applications'])->name('applications');
    Route::get('/notifications', [\App\Http\Controllers\Citizen\CitizenPanelController::class, 'notifications'])->name('notifications');
    Route::get('/help', [\App\Http\Controllers\Citizen\CitizenPanelController::class, 'help'])->name('help');

    // Profile & Security — reuse the existing Laravel profile controller and
    // its validation; only the route names differ so citizen nav links resolve.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/security', [ProfileController::class, 'edit'])->name('security');
    Route::patch('/security/password', [ProfileController::class, 'update'])->name('security.password');
});

/*
| Officer / Admin landing dashboards (Phase 4).
|
| These named routes exist so the post-login redirect policy can send each role
| to a real destination (see App\Support\Auth\LandingPage). Each is protected by
| auth + its own role gate. The dashboard bodies are honest placeholders — the
| operational UIs are not implemented yet.
|
| The public /dashboard demo route above is untouched and still public.
*/
Route::middleware(['auth', 'role:' . \App\Models\Role::ADMIN])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\OfficerDashboardController::class, 'admin'])->name('dashboard');
    });

Route::middleware(['auth', 'role:' . \App\Models\Role::REGISTRY_OFFICER])
    ->prefix('registry')->name('registry.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\OfficerDashboardController::class, 'registry'])->name('dashboard');
    });

Route::middleware(['auth', 'role:' . \App\Models\Role::BANK_OFFICER])
    ->prefix('bank')->name('bank.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\OfficerDashboardController::class, 'bank'])->name('dashboard');
    });

/*
| Landing page for an authenticated account with no recognized role.
| `auth` only — every authenticated user must reach a destination, otherwise the
| login redirect would loop.
*/
Route::middleware('auth')
    ->get('/account/pending', [\App\Http\Controllers\OfficerDashboardController::class, 'pending'])
    ->name('account.pending');

/*
| Presentation-only demo entry (Phase 4 / Demo Mode).
|
| The route is ALWAYS registered, but the controller aborts with 404 unless
| DemoMode::enabled() is true — which requires BOTH the `landtech.demo_mode`
| config flag AND a local/testing environment. Registering it unconditionally
| keeps route:list honest and makes the production behaviour (404) testable,
| rather than hiding the route and making it look absent.
|
| The {role} segment can only select WHICH configured demo account is used; it
| grants no role and bypasses no authorization. See DemoPanelController.
*/
Route::get('/demo/panel/{role}', [\App\Http\Controllers\DemoPanelController::class, 'enter'])
    ->whereIn('role', array_keys(\App\Support\Demo\DemoMode::implementedPanels()))
    ->name('demo.panel');

/*
| Phase 5 — Citizen land-management and verification workflow.
|
| TWO MODES, ONE ROLE. "Landowner / Seller" and "Land Buyer" are views of the
| same `citizen` role, not separate roles. Every route below carries
| auth + role:citizen; the mode is a session navigation hint only and grants
| nothing. Authorization for each action is decided by the policies registered
| in AppServiceProvider — never by the mode, and never by the URL.
|
| All records are additionally owner-scoped inside the controllers, so a citizen
| cannot reach another citizen's data by guessing an ID.
*/
Route::middleware(['auth', 'role:' . \App\Models\Role::CITIZEN])
    ->prefix('citizen')->name('citizen.')
    ->group(function () {

        // ---- Mode selection (session-only; no role change) ----
        Route::get('/modes', [\App\Http\Controllers\Citizen\CitizenModeController::class, 'index'])->name('modes');
        Route::post('/mode', [\App\Http\Controllers\Citizen\CitizenModeController::class, 'switch'])->name('mode.switch');

        // ---- Secure User ↔ Party ownership linking ----
        Route::get('/ownership-link', [\App\Http\Controllers\Citizen\PartyLinkController::class, 'index'])->name('link.index');
        Route::get('/ownership-link/claim', [\App\Http\Controllers\Citizen\PartyLinkController::class, 'create'])->name('link.create');
        Route::post('/ownership-link', [\App\Http\Controllers\Citizen\PartyLinkController::class, 'store'])->name('link.store');
        Route::patch('/ownership-link/{id}', [\App\Http\Controllers\Citizen\PartyLinkController::class, 'update'])->name('link.update');
        Route::delete('/ownership-link/{id}', [\App\Http\Controllers\Citizen\PartyLinkController::class, 'destroy'])->name('link.destroy');

        // ---- Landowner: my land ----
        Route::get('/land', [\App\Http\Controllers\Citizen\CitizenLandRecordController::class, 'index'])->name('land.index');
        Route::get('/land/new', [\App\Http\Controllers\Citizen\CitizenLandRecordController::class, 'create'])->name('land.create');        Route::post('/land', [\App\Http\Controllers\Citizen\CitizenLandRecordController::class, 'store'])->name('land.store');
        Route::get('/land/{id}', [\App\Http\Controllers\Citizen\CitizenLandRecordController::class, 'show'])->whereNumber('id')->name('land.show');
        Route::get('/land/{id}/edit', [\App\Http\Controllers\Citizen\CitizenLandRecordController::class, 'edit'])->whereNumber('id')->name('land.edit');
        Route::patch('/land/{id}', [\App\Http\Controllers\Citizen\CitizenLandRecordController::class, 'update'])->whereNumber('id')->name('land.update');
        Route::post('/land/{id}/submit', [\App\Http\Controllers\Citizen\CitizenLandRecordController::class, 'submit'])->whereNumber('id')->name('land.submit');
        Route::post('/land/{id}/correction', [\App\Http\Controllers\Citizen\CitizenLandRecordController::class, 'requestCorrection'])->whereNumber('id')->name('land.correction');
        Route::delete('/land/{id}', [\App\Http\Controllers\Citizen\CitizenLandRecordController::class, 'destroy'])->whereNumber('id')->name('land.destroy');

        // ---- Landowner: private documents ----
        // NOTE: no public URL exists for a stored file. `download` is the only
        // path to bytes and it authorizes before touching the filesystem.
        Route::get('/documents', [\App\Http\Controllers\Citizen\LandDocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/upload', [\App\Http\Controllers\Citizen\LandDocumentController::class, 'create'])->name('documents.create');
        Route::post('/documents', [\App\Http\Controllers\Citizen\LandDocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}', [\App\Http\Controllers\Citizen\LandDocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/download', [\App\Http\Controllers\Citizen\LandDocumentController::class, 'download'])->name('documents.download');
        Route::delete('/documents/{document}', [\App\Http\Controllers\Citizen\LandDocumentController::class, 'destroy'])->name('documents.destroy');

        // ---- Landowner: sale listings ----
        Route::get('/listings', [\App\Http\Controllers\Citizen\SaleListingController::class, 'index'])->name('listings.index');
        Route::get('/listings/new', [\App\Http\Controllers\Citizen\SaleListingController::class, 'create'])->name('listings.create');
        Route::post('/listings', [\App\Http\Controllers\Citizen\SaleListingController::class, 'store'])->name('listings.store');
        Route::get('/listings/{id}', [\App\Http\Controllers\Citizen\SaleListingController::class, 'show'])->whereNumber('id')->name('listings.show');
        Route::get('/listings/{id}/edit', [\App\Http\Controllers\Citizen\SaleListingController::class, 'edit'])->whereNumber('id')->name('listings.edit');
        Route::patch('/listings/{id}', [\App\Http\Controllers\Citizen\SaleListingController::class, 'update'])->whereNumber('id')->name('listings.update');
        Route::post('/listings/{id}/submit', [\App\Http\Controllers\Citizen\SaleListingController::class, 'submit'])->whereNumber('id')->name('listings.submit');
        Route::post('/listings/{id}/withdraw', [\App\Http\Controllers\Citizen\SaleListingController::class, 'withdraw'])->whereNumber('id')->name('listings.withdraw');
        Route::delete('/listings/{id}', [\App\Http\Controllers\Citizen\SaleListingController::class, 'destroy'])->whereNumber('id')->name('listings.destroy');

        // ---- Landowner: interest received ----
        Route::get('/interest-received', [\App\Http\Controllers\Citizen\BuyerInterestController::class, 'received'])->name('interest.received');

        // ---- Buyer: find land (published listings only) ----
        Route::get('/find', [\App\Http\Controllers\Citizen\BuyerSearchController::class, 'index'])->name('find');
        Route::get('/find/locations', [\App\Http\Controllers\Citizen\BuyerSearchController::class, 'locations'])->name('find.locations');
        Route::get('/find/{id}', [\App\Http\Controllers\Citizen\BuyerSearchController::class, 'show'])->whereNumber('id')->name('find.show');

        // ---- Buyer: ownership verification requests ----
        Route::get('/verification', [\App\Http\Controllers\Citizen\VerificationRequestController::class, 'index'])->name('verification.index');
        Route::get('/verification/new', [\App\Http\Controllers\Citizen\VerificationRequestController::class, 'create'])->name('verification.create');
        Route::post('/verification', [\App\Http\Controllers\Citizen\VerificationRequestController::class, 'store'])->name('verification.store');
        Route::get('/verification/{id}', [\App\Http\Controllers\Citizen\VerificationRequestController::class, 'show'])->whereNumber('id')->name('verification.show');
        Route::patch('/verification/{id}', [\App\Http\Controllers\Citizen\VerificationRequestController::class, 'update'])->whereNumber('id')->name('verification.update');
        Route::post('/verification/{id}/checks', [\App\Http\Controllers\Citizen\VerificationRequestController::class, 'rerunChecks'])->whereNumber('id')->name('verification.checks');

        // ---- Buyer: my interest requests ----
        Route::get('/interest', [\App\Http\Controllers\Citizen\BuyerInterestController::class, 'myRequests'])->name('interest.index');
        Route::post('/interest/{listingId}', [\App\Http\Controllers\Citizen\BuyerInterestController::class, 'store'])->whereNumber('listingId')->name('interest.store');
        Route::get('/interest/{interest}', [\App\Http\Controllers\Citizen\BuyerInterestController::class, 'show'])->name('interest.show');
        Route::post('/interest/{interest}/respond', [\App\Http\Controllers\Citizen\BuyerInterestController::class, 'respond'])->name('interest.respond');
    });

require __DIR__.'/auth.php';

# LandTech 2026 — P3: Fraud & Multiple Sale Detection (Frontend Prototype)

> ⚠️ **This is a frontend-only demo prototype.** Every "integration" in this project —
> government registries, Porichoy e-KYC, bank/CIB lien checks, Redis locking, AI/OCR,
> PostGIS spatial queries and blockchain — is a **browser-side simulation with synthetic
> data**. No real integration exists and none is claimed. The app is not connected to any
> government system.

---

## 1. Project Overview & Problem Statement

**LandTech 2026, Problem P3 — Fraud & Double-Selling Detection.**
The problem statement calls for a system that can detect:

- **Forged documents** — deed copies that have been altered or fabricated.
- **Same plot sold to multiple buyers** — multiple valid-looking deeds against one khatian/plot.
- **Suspicious rapid transfers** — an implausibly fast chain of ownership changes.
- **Registration ↔ mutation record mismatch** — a deed that is registered but not reflected in the mutation record.

This repository implements that problem as a **demonstration front end**. It serves 12
Blade pages, drives all logic with browser JavaScript, and persists demo state in
LocalStorage. It is built to show judges the *workflow* — locking, verification, conflict
detection, Saga rollback — not to connect to real systems.

---

## 2. Features — 12 Pages & 10 Engine Modules

### Pages (12)

| Route | Page | What it demonstrates |
|---|---|---|
| `/dashboard` | Command Dashboard | Stat cards (plots, deeds, high-risk plots, KYC count), a Chart.js risk-score bar chart, a doughnut chart of simulated webhook event types, "Rescan" and "Reset demo" buttons. Seeds demo data on first load. |
| `/saga` | Saga Orchestration | The simulated distributed transaction workflow (see §7). |
| `/sync-webhook` | M1 · Sync & Webhook Layer | In-browser simulated event bus / topic feed with event history. |
| `/crypto-verify` | M2 · Cryptographic Verification | Real SHA-256 hashing of deed records via the Web Crypto API, hash verification, dynamic QR payload generation, and a simulated hash-chain ledger. |
| `/plot-lock` | M3 · Real-Time Plot Lock | Simulated Redlock-style in-browser locks with a 5-minute TTL, using the key pattern `LOCK:LAND:<District>:<Upazila>:<Mouza>:<Khatian>:<Plot>`. |
| `/ekyc-biometric` | M4 · e-KYC & Biometric Gateway | Simulated Porichoy KYC (NID + name check) plus an **optional real camera preview** via `getUserMedia` and a local canvas photo capture (photo is not uploaded anywhere). |
| `/bank-lien` | M5 · Bank Lien Registry | Simulated lien registry: add liens, issue a simulated e-NOC to release a lien, and see registration blocked while a lien is active. |
| `/ai-ocr-conflict` | M6 · AI/OCR Conflict Detection | **Rule-based** simulated OCR extraction on synthetic deed records plus cross-record conflict detection (duplicate buyers, survey-type mismatch, rapid chains). |
| `/faraz-calculator` | M7 · Faraz Share Calculator | Inheritance-share math: enforce Σ shares = 1.0, block transfers exceeding a seller's legal share, equal-split helper, ankra (fraction) display. |
| `/gis-map` | M8 · GIS Spatial Mapping | Leaflet + OpenStreetMap polygons for the 12 synthetic plots, with a rule-based bounding-box overlap report. |
| `/risk-scoring` | M9 · Public Risk Scoring | Per-plot composite risk score (0–100) with risk bands and a simulated "check before you buy" result. |
| `/audit-ledger` | M10 · Immutable Audit Ledger | Hash-chained append-only ledger (real SHA-256 for block hashes) with chain verification and a "Simulate Tamper Attack" button. |

### Engine modules (10, in `public/js/`)

| Module | File | Key exports |
|---|---|---|
| M1 · Sync & Webhook Layer | `webhook.js` | `TOPICS`, `subscribe`, `emit`, `recentEvents` |
| M2 · Cryptographic Verification | `crypto.js` | `computeDeedHash`, `hashAllDeeds`, `dynamicQrPayload`, `verifyDeed`, `appendLedger`, `verifyLedgerChain`, `tamperLedger` |
| M3 · Real-Time Plot Lock | `locks.js` | `activeLocks`, `isLocked`, `acquireLock`, `releaseLock`, `setPendingMutationFlag`, `isTransferBlocked` |
| M4 · e-KYC & Biometric Gateway | `kyc.js` | `verifyKyc`, `startCamera`, `stopCamera`, `capturePhoto`, `getKycHistory` |
| M5 · Bank Lien Registry | `liens.js` | `activeLiensForPlot`, `plotHasActiveLien`, `addLien`, `issueENOC`, `registrationBlockedByLien` |
| M6 · AI/OCR Conflict Detection | `ocr.js` | `simulateOcrExtract`, `detectConflicts`, `getLatestScan` |
| M7 · Faraz Share Calculator | `faraz.js` | `validateShares`, `validateTransfer`, `equalSplit`, `fractionToAnkra` |
| M8 · GIS Spatial Mapping | `map.js` | `initMap`, `intersects`, `findOverlaps`, `renderPlots`, `focusPlot` |
| M9 · Public Risk Scoring | `risk.js` | `scorePlot`, `scoreAll`, `publicCheck` |
| M10 · Immutable Audit Ledger | `crypto.js` (ledger half) + `ledger` storage in `store.js` | `appendLedger`, `verifyLedgerChain`, `tamperLedger` |

Supporting modules: `store.js` (LocalStorage mock "database"), `seed.js` (synthetic
dataset), `saga.js` (Saga orchestration engine).

---

## 3. Technology Stack

| Layer | Technology |
|---|---|
| Page serving | **Laravel 12** (view-serving only — `Route::view` per page, no controllers with business logic, no Eloquent models, no database) |
| Templating | **Blade** (`layouts/app.blade.php` + `pages/*.blade.php`) |
| CSS | **Tailwind CSS 4** via `@tailwindcss/vite` |
| Bundler | **Vite 7** via `laravel-vite-plugin` |
| Client JS | Vanilla **ES modules** (`public/js/*.js`) |
| Persistence | **LocalStorage** (mock database) |
| Mapping | **Leaflet 1.9** + OpenStreetMap tiles |
| Charts | **Chart.js 4** |
| Hashing | Browser **Web Crypto API** (SHA-256) |
| Camera | Browser **`getUserMedia`** (photo stays local) |
| Test tooling | **Playwright 1.64** (dev dependency, used by `smoke-test.cjs`) |

---

## 4. Prerequisites & Installation

- **PHP ≥ 8.2** and **Composer**
- **Node.js ≥ 18** and **npm**

### Windows

```bat
:: 1. Install PHP dependencies
composer install

:: 2. Create .env and generate an app key (Laravel boilerplate — no DB is used)
copy .env.example .env
php artisan key:generate

:: 3. Install Node dependencies
npm install
```

### Linux / macOS

```bash
# 1. Install PHP dependencies
composer install

# 2. Create .env and generate an app key (Laravel boilerplate — no DB is used)
cp .env.example .env
php artisan key:generate

# 3. Install Node dependencies
npm install
```

No database configuration or migration is needed — the app uses no database server.

---

## 5. Running the App

### Development

```bash
# Start the Laravel view server (terminal 1) — same on Windows, Linux and macOS
php artisan serve --host=127.0.0.1 --port=8000

# Start Vite for live Tailwind reload (terminal 2)
npm run dev
```

Open **http://127.0.0.1:8000** — it redirects to `/dashboard`.

### Production build

```bash
npm run build
```

This compiles the CSS/JS into `public/build/`. The Laravel server then serves the
prebuilt assets without Vite running.

---

## 6. Tests

The repository contains **one test script** and the default Laravel PHPUnit boilerplate.

### Page-load smoke test (`smoke-test.cjs`)

A Playwright script that visits all 12 pages and reports console errors, failed
requests, page title, and whether the "FRONTEND DEMO PROTOTYPE" disclaimer badge is
present.

**Requirements:** the Laravel server must already be running on
`http://127.0.0.1:8000`, and Playwright must be able to find a Chromium-based browser.
The script currently hard-codes the path
`C:\Program Files (x86)\Google\Chrome\Application\chrome.exe` — if Chrome is installed
elsewhere (or is 64-bit), edit that path before running.

```bash
php artisan serve --host=127.0.0.1 --port=8000   # in a separate terminal
node smoke-test.cjs
```

Last verified result on this machine: **12/12 pages clean** (no console errors, no
failed requests, disclaimer badge present on every page).

### PHPUnit boilerplate status (honest note)

Both `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` are **stock
Laravel 11/12 scaffold tests, kept in the repository as-is**:

- `tests/Unit/ExampleTest.php` — `test_that_true_is_true`, a trivial PHPUnit assertion;
  it passes but tests nothing about this application.
- `tests/Feature/ExampleTest.php` — `test_the_application_returns_a_successful_response`
  does `$this->get('/')` and asserts status 200. This app redirects `/` to `/dashboard`,
  so the test is **expected to fail against the current routes** — it was never adapted
  to this project and is left untouched on purpose.

The meaningful automated verification for this prototype is the Playwright smoke test
above (12/12 pages, last verified clean), not the PHPUnit suite. Adapting or removing
the PHPUnit boilerplate is intentionally left as future work.

---

## 6b. 3-Minute Demo Walkthrough

A judge-ready tour of the already-verified functionality (all data synthetic; every
page shows the "FRONTEND DEMO PROTOTYPE" banner):

| Minute | What to do | What it demonstrates |
|---|---|---|
| 0:00–0:20 | Open `/dashboard`. Press **Reset demo** to reseed the synthetic dataset (12 plots, 15 deeds). | Stat cards, Chart.js risk-score bar chart and webhook event doughnut render from real in-memory synthetic data. |
| 0:20–0:50 | Go to `/saga`. Run the transfer on an unlocked, lien-free plot with a KYC-passing buyer. | Happy path: 5 saga steps complete → `COMMITTED`, SHA-256 ledger entry appended, `PENDING_MUTATION` flag set. |
| 0:50–1:20 | On `/saga`, set the failure-injection control to fail at **Identity Verification** and run again. | Compensation in reverse order: the plot lock from step 1 is released, status becomes `ABORTED` with `failedAt` recorded, `SAGA_ABORTED` ledger entry. |
| 1:20–1:50 | Open `/bank-lien` — `plot-9` has an active lien. Then run the Saga on `plot-9` (or inject a failure at the **Bank Lien Check** step). | Lien blocks registration; the Saga aborts at the lien step. Issue the simulated **e-NOC** on `/bank-lien` to release it and re-run to commit. |
| 1:50–2:20 | Open `/crypto-verify` and hash a deed, verify it, and generate its dynamic QR. Then open `/audit-ledger` and press **Simulate Tamper Attack**, then **Verify chain**. | Real Web Crypto SHA-256 hashing and a genuinely hash-chained ledger — editing any block visibly breaks the chain (with the LocalStorage caveat from §8). |
| 2:20–2:50 | Open `/ai-ocr-conflict` and run a scan. `plot-3` (sold twice) and `plot-7` (3 deeds within days) get flagged; then check their risk bands on `/risk-scoring`. | Rule-based multiple-sale and rapid-transfer detection raising composite risk scores. |
| 2:50–3:00 | Open `/gis-map` and view the 12 Leaflet/OpenStreetMap plot polygons. | GIS visualization over real map tiles with synthetic geometry. |

All steps above correspond to functionality that was verified with the Playwright
smoke test (12/12 pages clean) — nothing in this walkthrough is speculative.

### PHPUnit boilerplate

Laravel's default `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php`
are present but are **not tailored to this project** (they are the stock Laravel
scaffold). The Composer script to run them is:

```bash
composer test
```

Note: `ExampleTest` expects to serve the Laravel welcome page, which this project has
removed in favour of the dashboard redirect — treat the PHPUnit suite as boilerplate
that has not been adapted to this app.

---

## 7. Demo Data & Example Scenarios

All demo data is generated by `seed.js` and stored in LocalStorage. Use
**Dashboard → Reset demo** (or clear browser storage) to wipe and reseed.

### Synthetic dataset

- **12 plots** in Dhaka / Savar across 4 synthetic mouzas, each with a khatian number,
  survey type, mutation status, risk score, and a Leaflet polygon (deterministic
  geometry — seeded polygons do **not** overlap, so any overlap shown is deliberate).
- **15 deeds** with synthetic seller/buyer names, NID-like numbers, registry office
  and registration dates. Names/NIDs are generated placeholders — they are not real
  people.

### Intentional fraud scenarios

| Scenario | Where seeded | How to observe it |
|---|---|---|
| **Multiple sale (same plot sold twice)** | `plot-3` has 2 deeds | Dashboard "Rescan" or the M6 page flags the duplicate; the plot receives a high risk score in M9. |
| **Rapid transfer chain** | `plot-7` has 3 deeds registered within days of each other | M6 flags the short interval between registrations; M9 raises the risk band. |
| **Bank lien blocking registration** | `plot-9` has an active lien | The M5 page shows the active lien; the Saga's lien step will abort a transfer on this plot unless an e-NOC is released. |

### Saga Orchestration — commit and failure/compensation

The Saga (`public/js/saga.js`) models a transfer as 5 ordered steps:

1. **Plot Lock** — simulated Redlock (`LOCK:LAND:…` key, 5-minute TTL). Compensating action: release the lock.
2. **Identity Verification** — simulated Porichoy KYC. Compensating action: record the KYC rejection.
3. **Bank Lien Check** — simulated CIB; aborts if the plot has an active lien. No compensating action needed (nothing was written yet).
4. **Boundary Validation** — simulated PostGIS overlap check; aborts if the plot polygon overlaps another.
5. **Hash Generation & Mutation Notification** — computes the deed's SHA-256, sets the `PENDING_MUTATION` flag, appends to the ledger and emits a simulated webhook event. Compensating action: revert the mutation flag.

**Happy path:** all 5 steps complete → status `COMMITTED`, ledger entry
`SAGA_COMMITTED`, pending-mutation flag set (which blocks further transfers on that
plot until the mutation completes).

**Failure path:** if any step fails (e.g. KYC rejects the buyer, or a lien is active),
the Saga stops, records which step failed, and runs the **compensating actions in
reverse order for every already-completed step** — e.g. if KYC fails, the lock acquired
in step 1 is released. The status becomes `ABORTED` with `failedAt: <step>`, a ledger
entry `SAGA_ABORTED` is appended, and a simulated webhook event is emitted.

To see compensation on a specific step, use the Saga page's failure-injection control
and choose which step to fail.

---

## 8. Real Browser Functionality vs. Simulated Integrations

### Genuinely real (browser-side)

| Feature | What is real | Limits |
|---|---|---|
| **SHA-256 hashing** (M2) | Computed with the Web Crypto API over the deed's canonical JSON. Deterministic, cryptographically real. | See the important caveat below. |
| **Hash-chained ledger** (M10) | Each block stores the previous block's hash and a SHA-256 of its own content, so editing any block visibly breaks the chain. | See the important caveat below. |
| **Leaflet map** (M8) | Real map tiles fetched from OpenStreetMap; polygons drawn from real coordinates. | Data itself is synthetic. |
| **Chart.js charts** (dashboard) | Real chart rendering from real in-memory data. | Data is synthetic. |
| **Camera capture** (M4) | Real `getUserMedia` preview and canvas capture. | The photo never leaves the browser; nothing is uploaded. |
| **Faraz math** (M7) | Real arithmetic with the Σ=1.0 and transfer-limit constraints. | Input data is user-entered/synthetic. |
| **LocalStorage persistence** | Real browser storage — state survives reloads. | Per-browser, per-origin only; not shared between users or machines. |

> **Important caveat on M2 and M10.** Browser-side hashing and this hash-chained ledger
> **do not independently establish official deed authenticity** and **do not provide
> tamper-proof centralized storage**. SHA-256 proves only that two copies of a document
> match — it cannot prove a document came from a real registry. The chain lives in a
> user's LocalStorage: it can be cleared, and a full rewrite of all blocks (including
> recomputing every hash) would leave it looking valid. A production system would need
> server-side/centralized append-only storage, key management, and third-party trust
> anchors.

### Simulated (labelled in the UI, no real integration)

Every one of these is shown with a **"Simulated"** badge in the page UI, and every page
carries a persistent banner: *"FRONTEND DEMO PROTOTYPE — All government registries,
Porichoy e-KYC, bank/CIB systems, Redis locks, AI/OCR and blockchain features are
browser simulations with synthetic data. No real integration exists."*

| Feature | What the simulation actually does |
|---|---|
| **Government e-KYC** (Porichoy API) | A rule-based name/NID lookup against synthetic KYC records in LocalStorage. No network call is made to Porichoy or any government service. |
| **Bank lien checks / CIB / e-NOC** | Local records in LocalStorage; "e-NOC issuance" just flips a lien's status locally. No bank or CIB system is contacted. |
| **Redis locking (Redlock)** | A JavaScript object keyed by plot with a 5-minute TTL, stored in LocalStorage. There is no Redis server. |
| **AI/OCR** | Rule-based text parsing of synthetic deed records with heuristic conflict rules. No ML model and no OCR engine are used. |
| **PostGIS spatial queries** | A bounding-box overlap heuristic in `map.js` against the synthetic polygons. No PostGIS or real GIS server. |
| **Blockchain / immutable ledger** | A SHA-256 hash chain in LocalStorage, not a real distributed ledger. No Hyperledger/other blockchain is connected. |
| **Webhook / event bus (M1)** | An in-page pub/sub emitter that also records events to LocalStorage. No real Kafka/webhook receiver. |
| **Mutation registry** | A `mutationStatus` field on the synthetic plot records, updated locally by the Saga. No real registry. |

---

## 9. Security & Privacy Limitations

This is a demo front end with **no security guarantees**:

- **No authentication or authorization.** Anyone who can open the page can act as any
  role (officer, bank, verifier) with no credentials. There are no user accounts, no
  roles, no session enforcement.
- **All data is client-side and mutable.** Everything lives in LocalStorage — the
  ledger, deeds, locks, KYC records. Any user can open DevTools and read, edit, or
  delete any of it. This is intentional for the demo but means nothing here is
  trustworthy evidence of anything.
- **No server-side validation.** Because there is no backend, every "check" (KYC pass,
  lien absence, lock state) is advisory and can be bypassed entirely by manipulating
  the browser.
- **No real personal data is used** — all names, NID-like numbers and addresses are
  synthetic placeholders generated by `seed.js`. The camera capture feature (M4) keeps
  the photo in the local page only and uploads nothing, but the photo still lives in
  browser memory/storage on the user's machine — treat it as sensitive in a real
  deployment.
- **No encryption at rest.** LocalStorage is plain text.
- **No HTTPS enforcement, no CSRF protection, no rate limiting, no audit logging
  outside the simulated ledger.**

For a production system you would need real server-side auth, database-backed state,
server-side validation for every rule, real integrations with consent, and a genuine
tamper-evident storage layer.

---

## 10. Known Limitations & Future Possibilities

### Known limitations

- **Single-user, single-browser.** Locks and state are not shared between two people or
  two tabs (each tab has its own LocalStorage copy in most browsers).
- **No real concurrency control.** "Lock contention" is simulated per-browser only.
- **Rule-based conflict detection**, not ML — it can only catch patterns it was
  explicitly coded to look for.
- **Overlap detection is bounding-box based**, not true polygon intersection.
- **Ledger is client-side only** — clearing storage loses the history, and nothing is
  verifiable by a third party.
- **The PHPUnit test suite is untouched Laravel boilerplate**, not adapted to this app.
- The smoke test's Chrome path is hard-coded (see §6).
- **Only a fixed 12-plot seed**; adding plots/deeds by hand requires editing LocalStorage
  or reseeding.

### Future integration possibilities (real implementations the simulation points to)

| Module | A real integration would look like |
|---|---|
| M1 Sync/Webhook | A real message bus (e.g. Kafka) or webhook receiver on a server, with retries and dead-letter handling. |
| M2 Crypto Verification | Server-side document hashing, PKI-based digital signatures, and a real government registry API for ground truth. |
| M3 Plot Lock | A real Redis (or similar) distributed lock with TTL and Quorum/Redlock semantics across nodes. |
| M4 e-KYC | A real Porichoy (or equivalent) API integration with consent, plus a biometric vendor with proper template security. |
| M5 Bank Lien Registry | A real CIB/bank integration with proper authorization, and a real e-NOC workflow. |
| M6 AI/OCR | A real OCR engine or ML model on actual document images, with human review of flagged cases. |
| M7 Faraz Calculator | Server-side share computation with legal/inherited-share data from a real records source. |
| M8 GIS Layer | A real PostGIS database with real cadastral polygons and true `ST_Intersects`/`ST_Overlaps` queries. |
| M9 Risk Scoring | Server-side scoring with real historical data and a transparent, auditable model. |
| M10 Audit Ledger | A real distributed ledger (e.g. Hyperledger Fabric) or a server-side append-only log with external anchoring. |

---

## File Changed in This Update

- **`README.md`** — replaced with the content above. No other files were
  created or modified.

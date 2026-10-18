# LandTech 2026 — Fraud & Multiple-Sale Detection

> ⚠️ **Two distinct halves — read this first.**
>
> **1. The public M1–M10 module pages (`/dashboard`, `/saga`, `/sync-webhook`, …) are a
> frontend-only demo prototype.** Every "integration" on those pages — government
> registries, Porichoy e-KYC, bank/CIB lien checks, Redis locking, AI/OCR, PostGIS
> spatial queries and blockchain — is a **browser-side simulation with synthetic data**,
> persisted in LocalStorage. No real integration exists on those pages and none is claimed.
>
> **2. The role-based panels (`/citizen/*`, `/admin/*`, `/registry/*`, `/bank/*`) are a
> real, database-backed Laravel application** with server-side authentication,
> authorization policies, Eloquent models, migrations and an automated test suite. These
> panels store genuine records in a database — but they are still a **development
> system**: no government API, e-KYC, bank/CIB or GIS integration is connected, and no
> land record is verified against any official registry. Every "verification" outcome in
> the app is either a self-reported claim or a rule-based check.

---

## 1. Project Overview & Problem Statement

**LandTech 2026, Problem P3 — Fraud & Double-Selling Detection.**
The problem statement calls for a system that can detect:

- **Forged documents** — deed copies that have been altered or fabricated.
- **Same plot sold to multiple buyers** — multiple valid-looking deeds against one khatian/plot.
- **Suspicious rapid transfers** — an implausibly fast chain of ownership changes.
- **Registration ↔ mutation record mismatch** — a deed that is registered but not reflected in the mutation record.

This repository contains **two layers**:

- A **Laravel 12 backend application** (`app/`, `routes/`, `database/`, `resources/views/`)
  implementing authentication, four user roles, ownership linking, land records,
  document storage, sale listings, buyer interest and verification requests — all
  enforced server-side by policies and middleware. This layer uses a real database
  (SQLite by default) and is covered by a PHPUnit feature suite.
- A **client-side demonstration layer** (12 Blade pages + `public/js/*.js`) that shows the
  intended M1–M10 workflows — locking, cryptographic hashing, conflict detection, Saga
  rollback — as browser simulations with synthetic data. This layer is *not* connected to
  the backend and is preserved as a presentation artifact.

---

## 2. Public Demo Module Pages — 12 Pages & 10 Engine Modules

> These 12 pages are the **frontend-only simulation layer** described in the warning above.
> They are publicly reachable, are not backed by the database, and share no data with the
> role-based panels in §2b.

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

## 2B. User Roles, Permissions, and Features

> **Scope.** This section documents the **database-backed Laravel application** (the
> role-based panels from the warning at the top), not the public M1–M10 simulation
> pages. It covers the four roles that actually exist in code — `admin`,
> `registry_officer`, `bank_officer`, `citizen` (`app/Models/Role.php`,
> `database/seeders/RoleSeeder.php`). `super_admin` does **not** exist: the Welcome
> page shows its card as "Coming Soon", and the role-assignment tests assert it cannot
> be obtained by registering.
>
> **How to read the permission claims.** Three independent layers exist and must not be
> conflated:
>
> 1. **Route middleware** (`EnsureRole`) — a user whose roles do not match receives 403
>    even when the URL is typed by hand. There is no citizen bypass for officer routes,
>    and officer/admin roles cannot enter `/citizen/*`.
> 2. **Policies** (`app/Policies/*`, registered in `AppServiceProvider`) — authorize each
>    object-level action (create/view/update/delete/review). Controllers call
>    `authorize()` before acting.
> 3. **UI visibility** — `@can` checks and the sidebar's `hasRole()` calls only decide
>    whether a link or button is rendered. They are cosmetic and are never the security
>    boundary.
>
> Matrix markers: **Yes** = policy allows it and a route/UI exposes it; **Yes\*** =
> policy allows it but **no route/UI invokes it in this build** (authorization exists,
> reachability is **Unverified** at HTTP level); **Owner** = owner-scoped (the citizen's
> own data only, via routes); **No** = denied by the policy.

### Roles at a glance

| Role (code) | Label (EN / BN) | Purpose | Implemented UI |
|---|---|---|---|
| `admin` | Administrator / প্রশাসক | Highest-privilege role: party management, bulk identity export, reviewer powers everywhere. | Placeholder landing page only |
| `registry_officer` | Registry Officer / রেজিস্ট্রি কর্মকর্তা | Reviewer of ownership links, land records, documents, listings and verification requests; full-NID and NID-search access. | Placeholder landing page only |
| `bank_officer` | Bank Officer / ব্যাংক কর্মকর্তা | Read-only due-diligence role: party read + masked NID read; no writes, no full NID. | Placeholder landing page only |
| `citizen` | Citizen / নাগরিক | Self-service account holder; the only role with fully implemented workflows. Public registration grants exactly this role. | Full `/citizen/*` panel |

Notes:

- An authenticated account holding **no recognized role** is sent to `/account/pending`
  (`auth` only) — deliberately not the public demo dashboard — and can additionally use
  `/profile`.
- When one account holds several roles, the post-login landing resolves by a fixed
  precedence — `admin` → `registry_officer` → `bank_officer` → `citizen`
  (`App\Support\Auth\LandingPage`). That is a landing preference only: roles do not
  cascade, and each panel stays behind its own `role:` gate.
- Roles are granted by seeding/admin database work only. There is no public path to a
  privileged role: registration discards role-shaped input, and the profile update
  cannot attach roles (`tests/Feature/Auth/RoleAssignmentSecurityTest.php`).

### `admin` — Administrator

- **Dashboard and route access:** `/admin/dashboard` (`admin.dashboard`, middleware
  `auth` + `role:admin`), plus `/profile` and all public pages. Any other role gets 403.
- **Features:** the dashboard body is an explicitly labelled placeholder
  (`resources/views/officer/pending.blade.php`) stating the operational dashboard is
  pending; it fabricates no data. There is no user-management, review, settings or
  audit UI.
- **Create / view / update / delete / approval (policy-level, all currently
  unrouted — Yes\*):** Party view/create/update/delete/export; reviewer abilities on
  ownership links, land records, documents, listings (including `publish`,
  `setVerificationStatus`, `markSold`) and verification requests — never on an item the
  admin submitted themselves; masked NID view, full NID view, NID search and bulk
  identity export.
- **Sensitive NID and document access:** broadest by policy. Identity reads are only
  legal through `IdentityAccessService`, which appends a hash-chained
  `SensitiveReadAudit` row per action; no HTTP route calls that service in this build,
  so full/masked NID disclosure is not reachable through the app.
- **Audit log and QR:** identity reads *would be* recorded in the server-side audit
  chain; there is no audit-viewer route for any role (the public `/audit-ledger` page is
  a browser-local simulation and unrelated). No backend QR feature exists.
- **Restrictions and incomplete workflows:** no admin operations UI at all; deleting a
  party or exporting identities cannot be done from the UI; `super_admin` is not
  implemented.

### `registry_officer` — Registry Officer

- **Dashboard and route access:** `/registry/dashboard` (`registry.dashboard`,
  `auth` + `role:registry_officer`), plus `/profile` and all public pages. Other roles
  get 403.
- **Features:** placeholder landing page only (`officer.pending`). No review queue.
- **Create / view / update / delete / approval (policy-level, all currently
  unrouted — Yes\*):** Party view/create/update (no delete, no export); reviewer
  abilities on all five citizen workflows, always excluding self-submitted items;
  masked NID view, full NID view, NID search. No identity export.
- **Sensitive NID and document access:** full NID + NID search allowed by policy and
  intended to be audited through `IdentityAccessService`; reviewer document reads
  allowed by policy. None of it is routed yet.
- **Audit log and QR:** same as `admin` — by design audited, no viewer in the UI, no QR.
- **Restrictions and incomplete workflows (important):** because `review`, `publish`,
  `recordCheck` and link approval have **no route or UI**, this role cannot actually
  complete any approval in the running app. Submitted records, claims, listings and
  requests stay in their pending statuses unless the database is changed outside the
  UI. This is the largest incomplete workflow in the backend.

### `bank_officer` — Bank Officer

- **Dashboard and route access:** `/bank/dashboard` (`bank.dashboard`,
  `auth` + `role:bank_officer`), plus `/profile` and all public pages. Other roles get
  403.
- **Features:** placeholder landing page only.
- **Create / view / update / delete / approval (policy-level, currently unrouted —
  Yes\*):** Party `viewAny`/`view` and masked identity view only.
- **Denied by policy:** party create/update/delete, full NID, NID search, identity
  export, and every reviewer ability. `PartyIdentityPolicy` deliberately bars bank
  officers from full NIDs — they need lien status, not identity numbers.
- **Sensitive NID and document access:** masked NID only by policy; no document access
  at all.
- **Audit log and QR:** none.
- **Restrictions and incomplete workflows:** no lien/CIB workflow exists in the backend;
  the "lien registry" is a browser simulation on the public M5 page only.

### `citizen` — Citizen

- **Dashboard and route access:** every `/citizen/*` route (`auth` + `role:citizen`),
  plus `/profile` and the public pages. Admin, registry and bank roles are refused on
  `/citizen/*` with 403 (and a citizen is refused on their dashboards). Post-login
  landing is `/citizen/dashboard`.
- **Features (implemented, database-backed):**
  - **Seller/buyer modes** — `/citizen/modes`; switching (`POST /citizen/mode`) writes a
    session navigation hint only and grants nothing. The mode-select page shows real
    counts (verified link, pending records, published listings, open requests).
  - **Land records** — `/citizen/land`: create, edit, submit for review, request a
    correction, delete while still a draft. A submission is a *claim*: it enters
    `pending_verification`, is owner-scoped end to end, and can never be self-verified.
    A `party_id` is attached only when a **verified** link exists.
  - **Private documents** — `/citizen/documents`: upload (PDF/JPG/PNG, max 10 MB,
    extension and MIME both checked), list, view metadata, download, delete while
    pending. Files are stored on the private `local` disk under a server-generated
    random name; the authorized download route is the only path to the bytes.
  - **Sale listings** — `/citizen/listings`: create, edit, submit, withdraw; the offered
    share cannot exceed the linked land record's claimed share. `verification_status` is
    never writable by a citizen, and `published`/`sold` require reviewer actions that
    have no route yet, so a citizen listing can reach `pending_review`/`withdrawn` but
    not `published` through the UI.
  - **Ownership link (user ↔ party)** — `/citizen/ownership-link`: file a claim, amend
    or withdraw it while pending. Only a `verified` link grants authority over that
    party; claims match on non-sensitive identifiers (display name, date of birth,
    organisation registration number) and no raw NID is accepted or revealed.
  - **Buyer search** — `/citizen/find`: browse/filter *published* listings only, via a
    fixed public-field projection (no NID, no private documents).
  - **Verification requests** — `/citizen/verification`: request verification for any
    land reference (a listing is optional), view per-check results, supply additional
    information when asked, re-run the internal checks. The checks are rule-based
    internal consistency tests; checks needing external authorities are stored as
    `not_available` with an explicit limitation, and `official_status` is permanently
    `official_verification_not_available` — the UI never claims government verification.
  - **Interest requests** — `/citizen/interest` and `/citizen/interest-received`:
    express interest in another citizen's published listing (not your own), view and
    withdraw your own enquiries, and respond as the listing owner.
  - **Account** — `/citizen/notifications` (honest empty state; no `notifications` table
    exists), `/citizen/help`, `/citizen/profile`, `/citizen/security`.
- **Create / view / update / delete / approval:** full owner CRUD on the workflows
  above; **no approval ability of any kind** — a citizen cannot verify a link, verify a
  record, review a document, publish/verify a listing or approve a verification request,
  not even their own. Separation of duties is enforced in the policies, not the UI.
- **Sensitive NID and document access:** no NID access at all (not even masked); only
  their own documents, through authorized routes.
- **Audit log and QR:** none in the backend. Public `/audit-ledger` and
  `/crypto-verify` (QR payload) pages are separate browser-local simulations.
- **Restrictions and incomplete workflows:** legacy placeholder pages at
  `/citizen/deeds`, `/citizen/ownership` and `/citizen/applications` are still routed
  but render honest empty states (the Phase 5 sidebar links to the real workflows
  instead); `/citizen/dashboard` shows zeroed counts while `/citizen/modes` shows real
  ones; email verification is scaffolded (Breeze) but not enforced on any panel route.

### Permission matrix (server-side policies)

| Resource · ability | admin | registry_officer | bank_officer | citizen |
|---|---|---|---|---|
| Party · viewAny (list) | Yes\* | Yes\* | Yes\* | No |
| Party · view one record | Yes\* | Yes\* | Yes\* | Yes\* (own party, verified link only) |
| Party · create / update | Yes\* | Yes\* | No | No |
| Party · delete | Yes\* | No | No | No |
| Party · export | Yes\* | No | No | No |
| Identity · view masked (•••• 1234) | Yes\* | Yes\* | Yes\* | No |
| Identity · view full / search by NID | Yes\* | Yes\* | No | No |
| Identity · bulk export | Yes\* | No | No | No |
| Ownership link · create | No | No | No | Yes (own) |
| Ownership link · view | Yes\* | Yes\* | No | Owner (own claims) |
| Ownership link · update / withdraw | No | No | No | Owner (pending claims only) |
| Ownership link · approve / reject | Yes\* (never own) | Yes\* (never own) | No | No |
| Land record · create / submit / request correction | No | No | No | Yes (own) |
| Land record · view | Yes\* | Yes\* | No | Owner |
| Land record · update | No | No | No | Owner (while editable) |
| Land record · delete | No | No | No | Owner (draft only) |
| Land record · review / verify / reject | Yes\* (never own) | Yes\* (never own) | No | No |
| Document · upload | No | No | No | Yes |
| Document · view / download | Yes\* (review) | Yes\* (review) | No | Owner |
| Document · update / delete | No | No | No | Owner (while pending) |
| Document · review (approve/reject/replacement) | Yes\* (never own) | Yes\* (never own) | No | No |
| Listing · create / submit / withdraw | No | No | No | Yes (own) |
| Listing · view | Yes\* | Yes\* | No | Owner, or any authenticated user for published (route group is citizen-only) |
| Listing · update / delete | No | No | No | Owner (draft/withdrawn only) |
| Listing · publish / set verification / mark sold | Yes\* (never own) | Yes\* (never own) | No | No |
| Verification request · create / view / supply info / re-run checks | No | No | No | Yes (own; info only when requested) |
| Verification request · review / record checks | Yes\* (never own) | Yes\* (never own) | No | No |
| Verification request · delete | No | No | No | No (retained by design) |
| Buyer interest · create / withdraw | No | No | No | Yes |
| Buyer interest · view | Yes\* | Yes\* | No | Buyer or listing owner |
| Buyer interest · respond (as listing owner) | No | No | No | Yes |
| Buyer interest · delete | No | No | No | No (retained by design) |

> **UI note.** Blade `@can` checks (e.g. on land-record, listing, document and
> verification detail pages) only hide buttons; every action is re-authorized in the
> controller, so calling the route directly still yields 403 when the policy denies it.
> Conversely, a policy **Yes\*** ability cannot be invoked at all until a route and UI
> are added — it is defined and testable, not exposed.

### Route surface (what each role can actually open)

| Route | Gate | admin | registry_officer | bank_officer | citizen | guest |
|---|---|---|---|---|---|---|
| `/`, `/login`, `/register`, public M1–M10 pages | none | OK | OK | OK | OK | OK |
| `/profile` (and citizen profile/security pages) | `auth` (+ `role:citizen` for `/citizen/*`) | OK | OK | OK | OK | → login |
| `/admin/dashboard` | `auth` + `role:admin` | OK | 403 | 403 | 403 | → login |
| `/registry/dashboard` | `auth` + `role:registry_officer` | 403 | OK | 403 | 403 | → login |
| `/bank/dashboard` | `auth` + `role:bank_officer` | 403 | 403 | OK | 403 | → login |
| `/citizen/*` (all workflows) | `auth` + `role:citizen` | 403 | 403 | 403 | OK | → login |
| `/account/pending` | `auth` | OK | OK | OK | OK | → login |

(`/account/pending` is the post-login landing target for an authenticated account that
holds no recognized role.)

Demo entry: `/demo/panel/{role}` is always registered but returns **404** unless Demo
Mode is active — which requires both the `landtech.demo_mode` config flag and a
`local`/`testing` environment (default `LANDTECH_DEMO_MODE=false`). It logs the browser
into a pre-seeded development demo account through the normal authentication stack; it
grants no role and bypasses no authorization (`DemoPanelController`).

### Feature summary by role

| Role | Implemented and reachable | Defined in policies, no route/UI yet (Unverified at HTTP level) | Not implemented |
|---|---|---|---|
| `admin` | Login, role landing, placeholder dashboard, profile | Party CRUD/export; masked/full NID, NID search, identity export; reviewer abilities on all workflows | Admin/user management UI, audit viewer, review screens |
| `registry_officer` | Login, role landing, placeholder dashboard, profile | Party view/create/update; masked/full NID, NID search; reviewer abilities on all workflows | Review queues/UI |
| `bank_officer` | Login, role landing, placeholder dashboard, profile | Party view; masked NID view | Lien/CIB workflow UI |
| `citizen` | Full Phase 5 workflows (land records, documents, listings, ownership link, buyer search, verification requests, interest requests), profile/security | — (owner abilities are routed; reviewer-dependent steps are blocked by design) | Listing publication, record verification and link approval (need the missing reviewer UI); payments/transfers/mutation |

### Implemented vs. simulated vs. planned

- **Implemented (backend, database, automated tests):** authentication and four-role
  middleware; registration that grants only `citizen` and is rate-limited to 5
  requests/minute per IP (`throttle:5,1`); the citizen Phase 5 workflows
  listed above; private file storage; rule-based internal verification checks; demo
  accounts created only in `local`/`testing`; Demo Mode entry (config-gated, default
  off).
- **Simulated / demo-only:** the entire public M1–M10 layer (browser-side, LocalStorage,
  synthetic data) and the Demo Mode panel shortcuts; the M2 QR payload is generated in
  the browser, not by the backend.
- **Planned / absent:** officer and admin operational dashboards; all reviewer
  endpoints and UI; party-management UI; audit-log viewer; notifications table; backend
  QR; any government, e-KYC, bank/CIB, GIS or blockchain integration; `super_admin`.

---

## 3. Technology Stack

The repository contains **two layers** (see the note at the top): the database-backed
Laravel application, and the public M1–M10 demo pages. Rows marked *(demo layer)* power
only the public pages; the rest describes the Laravel application.

| Layer | Technology |
|---|---|
| Backend framework | **Laravel 12** (PHP ≥ 8.2) — routes, controllers, Eloquent models, migrations, validation, policies, middleware |
| Database | **SQLite** by default (`DB_CONNECTION=sqlite`, file `database/database.sqlite`); sessions, cache and queue use the database driver by default |
| Authentication | **Laravel Breeze 2.4** (Blade stack) |
| Authorization | **Policies** (`app/Policies/`, explicitly registered via `Gate::policy()` in `AppServiceProvider`) + a `roles`/`role_user` schema and the custom `EnsureRole` middleware (`role:` route middleware) |
| Identity privacy | AES encryption for stored NID values + keyed HMAC-SHA256 lookup (`NidHasher`, `NID_HMAC_KEY`), with a hash-chained `sensitive_read_audits` table |
| File storage | Private Laravel `local` disk for land documents (no public URL; authorized download route only) |
| Templating | **Blade** (`resources/views/` — demo pages, welcome page and Breeze auth/panel views) |
| CSS | **Tailwind CSS 4** via `@tailwindcss/vite` |
| Bundler | **Vite 7** via `laravel-vite-plugin` |
| Client JS (app UI) | **Alpine.js 3** (Breeze components) |
| Client JS *(demo layer)* | Vanilla **ES modules** (`public/js/*.js`) |
| Persistence *(demo layer)* | **LocalStorage** (browser-side mock database — not the application database) |
| Mapping *(demo layer)* | **Leaflet 1.9** + OpenStreetMap tiles |
| Charts *(demo layer)* | **Chart.js 4** |
| Hashing *(demo layer)* | Browser **Web Crypto API** (SHA-256) |
| Camera *(demo layer)* | Browser **`getUserMedia`** (photo stays local) |
| Localization | EN/BN lang files + session-persisted locale switch |
| Test tooling | **PHPUnit 11** (feature suite) + **Playwright 1.64** (`smoke-test.cjs`) |
| Code formatting | **Laravel Pint** (dev dependency) |

---

## 4. Prerequisites & Installation

- **PHP ≥ 8.2** and **Composer**
- **Node.js `^20.19` or `≥ 22.12`** and **npm** (required by Vite 7)
- No database server is required: the default connection is a local **SQLite** file.

### Windows

```bat
:: 1. Install PHP dependencies
composer install

:: 2. Create .env and generate an app key
copy .env.example .env
php artisan key:generate

:: 3. Generate the NID lookup key and paste it into .env as NID_HMAC_KEY
php -r "echo base64_encode(random_bytes(32));"

:: 4. Create the SQLite file (if absent), then migrate and seed
if not exist database\database.sqlite type nul > database\database.sqlite
php artisan migrate --seed

:: 5. Install Node dependencies
npm install
```

### Linux / macOS

```bash
# 1. Install PHP dependencies
composer install

# 2. Create .env and generate an app key
cp .env.example .env
php artisan key:generate

# 3. Generate the NID lookup key and paste it into .env as NID_HMAC_KEY
php -r "echo base64_encode(random_bytes(32));"

# 4. Create the SQLite file (if absent), then migrate and seed
touch database/database.sqlite
php artisan migrate --seed

# 5. Install Node dependencies
npm install
```

Notes:

- **`NID_HMAC_KEY` is required before seeding or using any identity feature.** The
  seeder stores encrypted synthetic identity numbers, and identity operations **fail
  closed** without the key (see `config/privacy.php`, `fail_closed`). Set it in `.env`
  (git-ignored) — the snippet above prints a value to copy; never commit it.
- `php artisan migrate --seed` creates the schema, the geographic reference data, the
  four roles, and the synthetic parties/plots/ownerships. Demo accounts are created
  **only when `APP_ENV` is `local` or `testing`** (the default in `.env.example`).
- `composer setup` automates install, `.env` creation, `key:generate`, `migrate --force`,
  `npm install` and `npm run build` — but it does **not** create the SQLite file, set
  `NID_HMAC_KEY`, or run the seeder, so steps 3–4 above still apply.
- Optional: with `LANDTECH_DEMO_MODE=true` in a `local`/`testing` environment, the
  Welcome page offers one-click entry into the demo panels (see §5). Default is
  `false`; the flag is inert elsewhere.

---

## 5. Running the App

### Development

```bash
# Terminal 1 — Laravel dev server (same on Windows, Linux and macOS)
php artisan serve --host=127.0.0.1 --port=8000

# Terminal 2 — Vite for frontend assets and live reload
npm run dev
```

Alternatively, `composer dev` runs the server, a queue listener, log tailing (`pail`)
and Vite together via `concurrently`.

Open **http://127.0.0.1:8000**. Entry points:

| URL | Behaviour |
|---|---|
| `/` | Serves the Welcome/Login landing page. Guests are **no longer redirected** to `/dashboard`. |
| `/dashboard`, `/saga`, … (12 pages) | Public M1–M10 demo pages — reachable without an account (browser simulations, see §2). |
| `/login`, `/register` | Breeze authentication. Public registration grants the `citizen` role and is rate-limited to 5 requests/minute per IP (`throttle:5,1`). |
| After login | Role-aware landing: admin → `/admin/dashboard`, registry officer → `/registry/dashboard`, bank officer → `/bank/dashboard`, citizen → `/citizen/dashboard`; an account with no role → `/account/pending`. |

If `LANDTECH_DEMO_MODE=true` and the app runs in `local`/`testing`, the Welcome page
also shows one-click shortcuts that sign in the pre-seeded demo accounts (default off).

### Production build

```bash
npm run build
```

This compiles the CSS/JS into `public/build/`. Unlike the original prototype, the full
application also needs a migrated database: run `php artisan migrate --force` (and
`--seed` if you want the reference/demo data) as part of deployment.

---

## 6. Tests

The repository has two automated layers: a **PHPUnit feature suite** covering the
database-backed backend, and a **Playwright page-load smoke script** for the public demo
pages.

### PHPUnit suite (`php artisan test`)

Runs `tests/Feature` and `tests/Unit` against an in-memory SQLite database —
`phpunit.xml` sets `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
array session/cache/mail drivers and the sync queue — so no local database is touched.

**Last run on this machine: 242 passed (1048 assertions).**

```bash
php artisan test        # or: composer test
```

Coverage by area (representative files under `tests/Feature/`):

| Area | Files | What is asserted |
|---|---|---|
| Authentication (Breeze) | `Auth/AuthenticationTest`, `Auth/RegistrationTest`, `Auth/PasswordResetTest`, `Auth/PasswordConfirmationTest`, `Auth/PasswordUpdateTest`, `Auth/EmailVerificationTest` | login/logout, registration (rate-limited 5/minute), password reset/confirmation/update and email verification flows |
| Roles & redirects | `Auth/RoleAssignmentSecurityTest` | public registration grants only `citizen`; role-shaped input is ignored; each role lands on its own dashboard; an intended URL never overrides authorization; role-less users land on `/account/pending` |
| Citizen panel access | `Citizen/CitizenPanelAccessTest`, `Citizen/CitizenModeTest` | 403 for wrong roles on citizen routes, owner-scoped records and notifications, mode switching grants no extra access |
| Citizen land workflow | `Citizen/LandRecordTest` | create/submit/edit lifecycle, submitted records go to *pending verification* (never auto-verified), client-supplied statuses rejected, exact fractional shares, cross-citizen access denied |
| Party & ownership validation | `OwnershipValidationTest` | form-request rules for fractions, disposal dates, bilingual names, organization fields and identity-number format |
| Identity & audit | `NidStorageTest`, `IdentityAccessAuthorizationTest`, `AuditChainTest` | encryption at rest, keyed HMAC lookup (Bengali/ASCII digit convergence), masking, fail-closed behaviour, role-gated reads by purpose, hash-chained audit rows |
| Schema & seeders | `PartySchemaTest`, `GeoSchemaTest`, `SeederIdempotencyTest` | schema constraints, idempotent re-seeding with preserved IDs, privilege-safe seeding |
| Welcome & demo mode | `WelcomePageTest`, `DemoWalkthroughTest` | `/` serves the welcome page; Demo Mode is off by default, requires `local`/`testing`, grants no role, and each shortcut lands on its own role's dashboard |
| Localization | `LocaleSwitchingTest`, `LocaleSwitchingEverywhereTest`, `MissingTranslationsTest` | EN/BN switching across screens and missing-translation checks |
| Profile | `ProfileTest` | Breeze profile update/delete flows |
| Root page | `ExampleTest` | `/` serves the welcome view and `/dashboard` still responds |

> `tests/Unit/ExampleTest.php` remains a trivial scaffold test (`assertTrue(true)`);
> everything else in the suite is project-specific.

### Page-load smoke test (`smoke-test.cjs`)

A Playwright script that visits all 12 pages and reports console errors, failed
requests, page title, and whether the "FRONTEND DEMO PROTOTYPE" disclaimer badge is
present.

**Requirements:** the Laravel server must already be running on
`http://127.0.0.1:8000`, and Playwright must be able to find a Chromium-based browser.
The script currently hard-codes the path
`C:\Program Files (x86)\Google\Chrome\Application\chrome.exe` — if Chrome is installed
elsewhere (or is 64-bit), edit that path before running. OpenStreetMap tiles can make
the run slow; allow a few minutes.

```bash
php artisan serve --host=127.0.0.1 --port=8000   # in a separate terminal
node smoke-test.cjs
```

Last verified result on this machine: **12/12 pages clean** (no console errors, no
failed requests, disclaimer badge present on every page).

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

- **The public demo pages have no authentication or role checks.** The 12 M1–M10 pages
  are public views, so anyone can act as any simulated role (officer, bank, verifier)
  with no credentials. The Laravel application itself is not open: it has Breeze
  authentication and four roles (`admin`, `registry_officer`, `bank_officer`,
  `citizen`, see §2b) enforced by `auth` + `role:` middleware and server-side policies
  on all real panels (`/citizen/*`, `/registry/*`, `/bank/*`, `/admin/*`,
  `/account/pending`, `/profile`). Only the demo pages are public.
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
- **No HTTPS enforcement.** The app does not force HTTPS and leaves
  `SESSION_SECURE_COOKIE` unset; terminate TLS and set it in any real deployment.
  **CSRF protection is enabled** — Laravel's default `web` middleware
  (`ValidateCsrfToken`) covers every session route, and no exclusions are configured.
  **Rate limiting is partial** — login is limited to 5 attempts per email + IP
  (`LoginRequest`), registration to 5 requests/minute per IP (`throttle:5,1`,
  regression-tested in `RegistrationTest`), and the email-verification routes to
  6/minute; the password-reset request and `/locale` routes have no route throttle.
- **Email verification is scaffolded but not enforced.** Breeze's verification routes,
  views and tests exist, but `User` does not implement `MustVerifyEmail` and no route
  uses the `verified` middleware, so a new account can use every panel immediately
  after registration.
- **The sensitive-read audit chain is dormant.** `AuditChain` and
  `sensitive_read_audits` are implemented and tested, but no route invokes them, so no
  audit rows are written in normal use and nothing exposes the chain for verification.

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
- **Reviewer workflows remain demo-only and incomplete.** Officer review/approval,
  citizen ownership-link approval, listing publication, and land-record/verification
  review have policies and status constants but **no route, controller or UI**. In the
  running app the terminal states are `pending`, `pending_review` and `submitted`;
  buyer search filters to `published`, which nothing can reach, so it is empty by
  construction (see §2b). Treat the reviewer side as unbuilt, not production-ready.
- **Email verification is not enforced** (see §9) — registration immediately grants a
  usable session.
- **The PHPUnit suite covers the Laravel backend, not the demo layer** — it is a
  custom project suite (242 tests passed in the latest run), while the public M1–M10
  pages are only checked by the Playwright smoke test (see §6).
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

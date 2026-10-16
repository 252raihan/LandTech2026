# LandTech 2026 — P3: Fraud & Multiple Sale Detection (Frontend Prototype)

Frontend-only prototype for the LandTech 2026 hackathon track
**"Digital Land Records, Verification & Digitization"**, problem statement
**P3 — Fraud & Double-Selling Detection**.

## ⚠️ Simulation Disclaimer (READ FIRST)

This is a **frontend demo prototype**. The following are **browser simulations with
100% synthetic data** — **no real integrations exist**:

| Simulated feature | Reality |
|---|---|
| Sync & Webhook Layer (Kafka/RabbitMQ) | In-browser event feed via LocalStorage |
| Redis Redlock Plot Lock | JavaScript TTL map in LocalStorage |
| Porichoy e-KYC / biometrics | Rule-based simulation; optional real camera via `getUserMedia` (photos never leave the browser) |
| Bank CIB/CBS / e-NOC | Local mock registry |
| AI/OCR engine | Rule-based conflict detection, no AI model |
| PostGIS spatial queries | Client-side polygon point-in-polygon tests |
| Blockchain / Hyperledger ledger | SHA-256 hash-chained list in LocalStorage (crypto is real; storage is local) |

No government APIs, bank systems, biometric infrastructure, Redis, or blockchain
services are connected. Per the LandTech 2026 Rule Book §8, **all data is synthetic**
— no real persons, NIDs, or land records are used. All seeded NIDs are prefixed `SIM-`.

## Stack

- **Laravel 11 (Blade)** — used strictly as a view/template server. No controllers with
  business logic, no database, no Eloquent models.
- **Tailwind CSS v4** via Vite
- **Vanilla JavaScript (ES modules)** — all module logic in `public/js/`
- **LocalStorage** — demo data persistence ("database")
- **Leaflet + OpenStreetMap** — GIS visualization
- **Chart.js** — dashboard charts
- **Web Crypto API** — real SHA-256 hashing (deed hashes, dynamic QR, ledger chain)

## The 10 Modules

| # | Module | Page | JS |
|---|---|---|---|
| M1 | Sync & Webhook Layer | `/sync-webhook` | `store.js` (event feed) |
| M2 | Cryptographic Verification Engine | `/crypto-verify` | `crypto.js` |
| M3 | Real-Time Plot Lock Service | `/plot-lock` | `locks.js` |
| M4 | e-KYC & Biometric Gateway | `/ekyc-biometric` | `kyc.js` |
| M5 | Bank Lien Registry | `/bank-lien` | `liens.js` |
| M6 | AI/OCR Conflict Detection | `/ai-ocr-conflict` | `ocr.js` |
| M7 | Faraz Share Calculator | `/faraz-calculator` | `faraz.js` |
| M8 | GIS Spatial Mapping | `/gis-map` | `map.js` |
| M9 | Public Risk Scoring | `/risk-scoring` | `risk.js` |
| M10 | Immutable Audit Ledger | `/audit-ledger` | `crypto.js` |

Plus the **Saga Orchestration** workflow at `/saga` (`saga.js`):
`Plot Lock → e-KYC → Lien Check → Boundary Validation → Hash/Mutation Notification`
with compensating transactions (lock release, mutation-flag revert) on failure, and
injectable failure points for demonstration.

## Seeded Fraud Scenarios (synthetic)

- `plot-3` — same plot sold to **two buyers** (multiple sale)
- `plot-7` — **rapid transfer chain** (3 deeds within days)
- `plot-9` — **active bank lien** blocking registration until e-NOC

## Running

```bash
cd landtech-app
npm install
npm run build        # build Tailwind assets
php artisan serve    # view server only — no backend logic
# open http://127.0.0.1:8000/dashboard
```

Use **Reset Demo Data** on the dashboard to re-seed the synthetic dataset.

## Rules Compliance (Rule Book §8)

- 100% synthetic/dummy data; no real personal or land data
- No scraping, no government portal access, no security testing of live systems
- All simulated features clearly labelled in every UI page and in this README

## Credits

- [LandTech 2026 Rule Book](https://landtech.diu.edu.bd/storage/events/landtech/aUAMtAxBfVckIypiOSekkW9s8bVw8pETLg2hox1W.pdf)
- [Leaflet](https://leafletjs.com) · [OpenStreetMap](https://www.openstreetmap.org/copyright)
- [Chart.js](https://www.chartjs.org) · [Tailwind CSS](https://tailwindcss.com)
"# LandTech2026" 

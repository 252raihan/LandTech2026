<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Mouza;
use App\Models\SaleListing;
use App\Models\Upazila;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 5 — Buyer: find land.
 *
 * WHAT IS SEARCHABLE
 *   ONLY listings with `status = published`. Drafts, withdrawn listings, records
 *   pending review and every private land record are invisible here — a buyer
 *   cannot reach them by filtering, by guessing an ID, or by any parameter.
 *
 * WHAT IS RETURNED
 *   A fixed projection of public fields. No full NID, no private document, no
 *   internal review note, no audit reference. The query never eager-loads the
 *   owner's identity or documents.
 *
 * QUERY SAFETY
 *   Every filter is validated and applied through the query builder, so all
 *   values are bound parameters. There is no string interpolation into SQL.
 */
class BuyerSearchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SaleListing::class);

        $validated = $request->validate([
            'district' => ['nullable', 'string', 'max:128'],
            'upazila' => ['nullable', 'string', 'max:128'],
            'mouza' => ['nullable', 'string', 'max:128'],
            'jl_no' => ['nullable', 'string', 'max:32'],
            'khatian_no' => ['nullable', 'string', 'max:64'],
            'dag_no' => ['nullable', 'string', 'max:64'],
            'min_area' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'max_area' => ['nullable', 'numeric', 'min:0', 'max:9999', 'gte:min_area'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:999', 'gte:min_price'],
            'sale_type' => ['nullable', 'string', \Illuminate\Validation\Rule::in([
                SaleListing::SALE_FULL_PLOT,
                SaleListing::SALE_PARTIAL,
            ])],
            'verification_status' => ['nullable', 'string', \Illuminate\Validation\Rule::in([
                SaleListing::VERIFICATION_UNVERIFIED,
                SaleListing::VERIFICATION_PENDING,
                SaleListing::VERIFICATION_DOCUMENT_REVIEWED,
                SaleListing::VERIFICATION_VERIFIED,
            ])],
        ]);

        $listings = $this->buildQuery($validated)
            ->paginate(12)
            ->withQueryString();

        return view('citizen.buyer.search', [
            'listings' => $listings,
            'filters' => $validated,
            'districts' => District::query()->orderBy('name_en')->get(),
            'upazilas' => collect(),
            'mouzas' => collect(),
            'hasSearched' => $request->query() !== [],
        ]);
    }

    /**
     * A single published listing, from the buyer's perspective.
     */
    public function show(Request $request, int $id): View
    {
        $listing = SaleListing::query()
            ->published()
            ->with(['mouza.upazila.district'])
            ->findOrFail($id);

        $this->authorize('view', $listing);

        return view('citizen.buyer.listing', [
            'listing' => $listing,
            'alreadyInterested' => $listing->interestRequests()
                ->where('user_id', $request->user()->id)
                ->exists(),
            'isOwner' => $listing->user_id === $request->user()->id,
        ]);
    }

    /**
     * Location autocomplete data, scoped to the public geo hierarchy.
     */
    public function locations(Request $request): View
    {
        $this->authorize('viewAny', SaleListing::class);

        $upazilas = collect();
        $mouzas = collect();

        if ($request->filled('district_id')) {
            $upazilas = Upazila::query()
                ->where('district_id', $request->integer('district_id'))
                ->orderBy('name_en')
                ->get();
        }

        if ($request->filled('upazila_id')) {
            $mouzas = Mouza::query()
                ->where('upazila_id', $request->integer('upazila_id'))
                ->orderBy('name_en')
                ->get();
        }

        return view('citizen.buyer.locations', [
            'districts' => District::query()->orderBy('name_en')->get(),
            'upazilas' => $upazilas,
            'mouzas' => $mouzas,
        ]);
    }

    /**
     * The public search query. Published listings only.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function buildQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return SaleListing::query()
            // The single most important line in this controller: only published
            // listings are ever reachable through buyer search.
            ->published()
            ->select([
                'id',
                'reference_no',
                'title',
                'public_description',
                'district_name',
                'upazila_name',
                'mouza_name',
                'jl_no',
                'survey_type',
                'khatian_no',
                'dag_no',
                'area',
                'area_unit',
                'asking_price',
                'price_unit',
                'sale_type',
                'claim_share_numerator',
                'claim_share_denominator',
                'status',
                'verification_status',
                'published_at',
            ])
            ->when($filters['district'] ?? null, fn($q, $v) => $q->where('district_name', 'like', '%' . $v . '%'))
            ->when($filters['upazila'] ?? null, fn($q, $v) => $q->where('upazila_name', 'like', '%' . $v . '%'))
            ->when($filters['mouza'] ?? null, fn($q, $v) => $q->where('mouza_name', 'like', '%' . $v . '%'))
            ->when($filters['jl_no'] ?? null, fn($q, $v) => $q->where('jl_no', $v))
            ->when($filters['khatian_no'] ?? null, fn($q, $v) => $q->where('khatian_no', $v))
            ->when($filters['dag_no'] ?? null, fn($q, $v) => $q->where('dag_no', $v))
            ->when($filters['min_area'] ?? null, fn($q, $v) => $q->where('area', '>=', $v))
            ->when($filters['max_area'] ?? null, fn($q, $v) => $q->where('area', '<=', $v))
            ->when($filters['min_price'] ?? null, fn($q, $v) => $q->where('asking_price', '>=', $v))
            ->when($filters['max_price'] ?? null, fn($q, $v) => $q->where('asking_price', '<=', $v))
            ->when($filters['sale_type'] ?? null, fn($q, $v) => $q->where('sale_type', $v))
            ->when($filters['verification_status'] ?? null, fn($q, $v) => $q->where('verification_status', $v))
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }
}

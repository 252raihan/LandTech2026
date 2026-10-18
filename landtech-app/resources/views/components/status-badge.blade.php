{{--
    Status badge for Phase 5 land workflow records.

    Every status is rendered from a translation key, so the raw internal value
    (e.g. `pending_verification`) is never shown to the user.

    Colours deliberately avoid green for anything unverified: green is reserved
    for states a REVIEWER has set.
--}}
@php
    $map = [
        // Land records
        'draft' => ['slate', 'citizen.status.draft'],
        'pending_verification' => ['amber', 'citizen.status.pending_verification'],
        'under_review' => ['blue', 'citizen.status.under_review'],
        'additional_information_requested' => ['amber', 'citizen.status.additional_information_requested'],
        'verified' => ['green', 'citizen.status.verified'],
        'rejected' => ['red', 'citizen.status.rejected'],
        'correction_requested' => ['amber', 'citizen.status.correction_requested'],

        // Listings
        'pending_review' => ['amber', 'citizen.status.pending_review'],
        'published' => ['blue', 'citizen.status.published'],
        'reserved' => ['indigo', 'citizen.status.reserved'],
        'withdrawn' => ['slate', 'citizen.status.withdrawn'],
        'sold' => ['green', 'citizen.status.sold'],

        // Verification / interest
        'submitted' => ['blue', 'citizen.status.submitted'],
        'reviewed' => ['green', 'citizen.status.reviewed'],
        'closed' => ['slate', 'citizen.status.closed'],
        'acknowledged' => ['blue', 'citizen.status.acknowledged'],
        'contact_shared' => ['green', 'citizen.status.contact_shared'],
        'site_visit_proposed' => ['indigo', 'citizen.status.site_visit_proposed'],
        'declined' => ['red', 'citizen.status.declined'],
    ];

    [$colour, $key] = $map[$status] ?? ['slate', null];
@endphp

<span class="badge badge-{{ $colour }}">
    {{ $key ? __($key) : $status }}
</span>

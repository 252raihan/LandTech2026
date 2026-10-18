@php
    $mode = \App\Http\Controllers\Citizen\CitizenModeController::activeMode(request());
    $isSeller = $mode === \App\Http\Controllers\Citizen\CitizenModeController::MODE_SELLER;
    $isBuyer = $mode === \App\Http\Controllers\Citizen\CitizenModeController::MODE_BUYER;

    // Navigation is derived from the active mode. This is a display choice only:
    // every route behind both modes independently requires the citizen role.
    $sellerNav = [
        ['citizen.modes', 'dashboard', __('citizen.nav.modes')],
        ['citizen.land.index', 'map', __('citizen.nav.my_land')],
        ['citizen.land.create', 'document', __('citizen.nav.add_land')],
        ['citizen.documents.index', 'document', __('citizen.nav.my_documents')],
        ['citizen.link.index', 'shield-check', __('citizen.nav.ownership_info')],
        ['citizen.listings.index', 'banknotes', __('citizen.nav.my_listings')],
        ['citizen.interest.received', 'flag', __('citizen.nav.buyer_interest')],
    ];

    $buyerNav = [
        ['citizen.modes', 'dashboard', __('citizen.nav.modes')],
        ['citizen.find', 'search', __('citizen.nav.find_land')],
        ['citizen.verification.index', 'shield-check', __('citizen.nav.verification_requests')],
        ['citizen.interest.index', 'flag', __('citizen.nav.my_interest')],
    ];
@endphp

{{-- Mode switcher: a POST form so switching is an explicit, CSRF-protected
     action. It writes a session preference and nothing else. --}}
<div class="flex items-center gap-2 px-3 pb-2 pt-1">
    <form method="POST" action="{{ route('citizen.mode.switch') }}" class="flex w-full items-center gap-2">
        @csrf
        <label for="citizen-mode" class="sr-only">{{ __('citizen.mode.label') }}</label>
        <select id="citizen-mode" name="mode" onchange="this.form.submit()"
                class="input !w-full !px-2.5 !py-1.5 text-xs font-medium">
            <option value="seller" @selected($isSeller)>{{ __('citizen.mode.seller') }}</option>
            <option value="buyer" @selected($isBuyer)>{{ __('citizen.mode.buyer') }}</option>
        </select>
        <noscript>
            <button type="submit" class="btn-primary btn-sm">{{ __('citizen.mode.switch_button') }}</button>
        </noscript>
    </form>
</div>

@if ($isBuyer)
    <p class="app-nav-group-label sidebar-group-label">{{ __('citizen.nav.buyer_panel') }}</p>
    @foreach ($buyerNav as [$route, $icon, $label])
        <a href="{{ route($route) }}"
           class="app-nav-link {{ request()->routeIs($route) ? 'app-nav-link-active' : '' }}"
           title="{{ $label }}"
           @if (request()->routeIs($route)) aria-current="page" @endif>
            <x-icon :name="$icon" class="nav-icon" />
            <span class="sidebar-label truncate">{{ $label }}</span>
        </a>
    @endforeach
@else
    <p class="app-nav-group-label sidebar-group-label">{{ __('citizen.nav.seller_panel') }}</p>
    @foreach ($sellerNav as [$route, $icon, $label])
        <a href="{{ route($route) }}"
           class="app-nav-link {{ request()->routeIs($route) ? 'app-nav-link-active' : '' }}"
           title="{{ $label }}"
           @if (request()->routeIs($route)) aria-current="page" @endif>
            <x-icon :name="$icon" class="nav-icon" />
            <span class="sidebar-label truncate">{{ $label }}</span>
        </a>
    @endforeach
@endif

{{-- Account-level links, available in both modes. --}}
<p class="app-nav-group-label sidebar-group-label">{{ __('citizen.nav.account') }}</p>
@foreach ([
    ['citizen.notifications', 'bell', __('citizen.nav.notifications')],
    ['citizen.profile', 'user', __('citizen.nav.my_profile')],
    ['citizen.security', 'lock', __('citizen.nav.security_settings')],
    ['citizen.help', 'chat', __('citizen.nav.help_support')],
] as [$route, $icon, $label])
    <a href="{{ route($route) }}"
       class="app-nav-link {{ request()->routeIs($route) ? 'app-nav-link-active' : '' }}"
       title="{{ $label }}"
       @if (request()->routeIs($route)) aria-current="page" @endif>
        <x-icon :name="$icon" class="nav-icon" />
        <span class="sidebar-label truncate">{{ $label }}</span>
    </a>
@endforeach

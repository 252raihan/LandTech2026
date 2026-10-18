<header class="app-header">
    <button type="button" id="sidebar-toggle"
            class="btn-ghost btn-sm hidden !px-2 lg:inline-flex"
            aria-controls="app-sidebar" aria-expanded="true"
            aria-label="{{ __('pages.layout.collapse_sidebar') }}"
            title="{{ __('pages.layout.collapse_sidebar') }}">
        <x-icon name="menu" class="h-5 w-5" />
    </button>
    <button type="button" id="mobile-nav-toggle"
            class="btn-ghost btn-sm !px-2 lg:hidden"
            aria-controls="app-sidebar" aria-expanded="false"
            aria-label="{{ __('pages.layout.open_menu') }}"
            title="{{ __('pages.layout.open_menu') }}">
        <x-icon name="menu" class="h-5 w-5" />
    </button>

    <div class="min-w-0 flex-1">
        @isset($header)
            <div class="truncate text-sm font-semibold text-slate-800">{{ $header }}</div>
        @else
            <p class="truncate text-sm font-semibold text-slate-800">
                @yield('page-title', __('pages.layout.default_page_title'))
            </p>
            <p class="hidden truncate text-xs text-slate-400 sm:block">
                {{ __('pages.layout.app_name') }} · {{ __('pages.layout.app_tagline') }}
            </p>
        @endisset
    </div>

    <div class="flex shrink-0 items-center gap-2">
        <span class="sim-badge hidden xl:inline-flex">{{ __('pages.layout.sim_badge') }}</span>

        @include('partials.locale-switcher')

        @guest
            <a href="{{ route('login') }}" class="btn-ghost btn-sm hidden sm:inline-flex">{{ __('pages.layout.log_in') }}</a>
            <a href="{{ route('register') }}" class="btn-primary btn-sm">{{ __('pages.layout.register') }}</a>
        @endguest

        @auth
            <details class="relative" data-menu>
                <summary class="btn-ghost btn-sm menu-summary" aria-label="{{ Auth::user()->name }}">
                    <x-icon name="user" class="h-4 w-4" />
                    <span class="hidden max-w-[10rem] truncate sm:inline">{{ Auth::user()->name }}</span>
                    <x-icon name="chevron-down" class="h-3.5 w-3.5" />
                </summary>
                <div class="menu-panel">
                    <p class="truncate px-3 py-2 text-xs text-slate-400">{{ Auth::user()->email }}</p>
                    @if (auth()->user()?->hasAnyRole([\App\Models\Role::CITIZEN]) && ! auth()->user()->hasAnyRole([\App\Models\Role::ADMIN, \App\Models\Role::REGISTRY_OFFICER, \App\Models\Role::BANK_OFFICER]))
                        <a href="{{ route('citizen.profile') }}" class="menu-item">
                            <x-icon name="user" class="h-4 w-4" /> {{ __('citizen.nav.my_profile') }}
                        </a>
                        <a href="{{ route('citizen.security') }}" class="menu-item">
                            <x-icon name="lock" class="h-4 w-4" /> {{ __('citizen.nav.security_settings') }}
                        </a>
                    @else
                        <a href="{{ route('profile.edit') }}" class="menu-item">
                            <x-icon name="user" class="h-4 w-4" /> {{ __('pages.layout.profile') }}
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="menu-item">
                            <x-icon name="logout" class="h-4 w-4" /> {{ __('pages.layout.log_out') }}
                        </button>
                    </form>
                </div>
            </details>
        @endauth
    </div>
</header>

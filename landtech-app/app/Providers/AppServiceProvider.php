<?php

namespace App\Providers;

use App\Models\Party;
use App\Models\PartyIdentity;
use App\Policies\PartyIdentityPolicy;
use App\Policies\PartyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Phase 3 authorization. Registered explicitly (rather than relying on
        // name-based auto-discovery) so the mapping is greppable and testable.
        Gate::policy(Party::class, PartyPolicy::class);
        Gate::policy(PartyIdentity::class, PartyIdentityPolicy::class);
    }
}

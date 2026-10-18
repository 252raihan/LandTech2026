<?php

namespace App\Providers;

use App\Models\BuyerInterestRequest;
use App\Models\LandDocument;
use App\Models\LandRecord;
use App\Models\Party;
use App\Models\PartyIdentity;
use App\Models\PartyUserLink;
use App\Models\SaleListing;
use App\Models\VerificationRequest;
use App\Policies\BuyerInterestPolicy;
use App\Policies\LandDocumentPolicy;
use App\Policies\LandRecordPolicy;
use App\Policies\PartyIdentityPolicy;
use App\Policies\PartyPolicy;
use App\Policies\PartyUserLinkPolicy;
use App\Policies\SaleListingPolicy;
use App\Policies\VerificationRequestPolicy;
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

        // Phase 5 — citizen land workflow. Registered explicitly for the same
        // reason: every ownership and verification gate is auditable in one place.
        Gate::policy(PartyUserLink::class, PartyUserLinkPolicy::class);
        Gate::policy(LandRecord::class, LandRecordPolicy::class);
        Gate::policy(LandDocument::class, LandDocumentPolicy::class);
        Gate::policy(SaleListing::class, SaleListingPolicy::class);
        Gate::policy(VerificationRequest::class, VerificationRequestPolicy::class);
        Gate::policy(BuyerInterestRequest::class, BuyerInterestPolicy::class);
    }
}

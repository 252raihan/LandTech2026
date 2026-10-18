<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Buyer interest / purchase enquiries against a published listing.
 *
 * WHAT THIS IS NOT
 *   This is an ENQUIRY RECORD. It is not a contract, not a payment, not a
 *   transfer, and not a reservation of title. No deed registration, mutation or
 *   legal transfer happens anywhere in this workflow, and the UI must never
 *   imply otherwise.
 *
 * PRIVACY
 *   The buyer's contact details are NOT copied onto this row. Buyers express
 *   interest; the listing owner chooses whether to share contact information.
 *   Only the listing owner and authorized reviewers may read a request, which is
 *   enforced in BuyerInterestPolicy.
 */
return new class extends Migration
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_CONTACT_SHARED = 'contact_shared';
    public const STATUS_SITE_VISIT_PROPOSED = 'site_visit_proposed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CLOSED = 'closed';

    public function up(): void
    {
        Schema::create('buyer_interest_requests', function (Blueprint $table) {
            $table->id();

            $table->string('reference_no', 32)->unique();

            $table->foreignId('sale_listing_id')->constrained()->cascadeOnDelete();

            // The interested buyer. Distinct from the listing's user_id.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('message')->nullable();

            $table->string('status', 32)->default(self::STATUS_SUBMITTED);

            // Seller's reply, visible to the buyer.
            $table->text('seller_response')->nullable();
            $table->timestamp('responded_at')->nullable();

            // Whether the owner has agreed to reveal contact details. The actual
            // contact value is never stored here.
            $table->boolean('contact_shared')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // A buyer may only raise one interest per listing.
            $table->unique(['sale_listing_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['sale_listing_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_interest_requests');
    }
};

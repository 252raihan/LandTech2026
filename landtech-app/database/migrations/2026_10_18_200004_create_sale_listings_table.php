<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Sale listings.
 *
 * TWO INDEPENDENT STATUS AXES (this is the important design decision):
 *
 *   `status`              — the listing's own lifecycle, controlled by the owner
 *                           and the review process:
 *                           draft → pending_review → published
 *                                 → reserved / withdrawn / sold
 *
 *   `verification_status` — whether the underlying land claim has been verified,
 *                           controlled ONLY by a reviewer. An owner can never
 *                           set this. It defaults to `unverified`.
 *
 * Keeping them separate is what makes it impossible for an owner to publish a
 * listing that *looks* officially verified. The public search only ever exposes
 * `published` listings, and always labels the verification state alongside them.
 *
 * `claim_share_*` records the share offered for sale as an exact fraction. The
 * application refuses to publish an offer larger than the verified transferable
 * share (see SaleListingPolicy / SaleListingService).
 */
return new class extends Migration
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_SOLD = 'sold';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_DOCUMENT_REVIEWED = 'document_reviewed';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_REJECTED = 'rejected';

    public const SALE_FULL_PLOT = 'full_plot';
    public const SALE_PARTIAL = 'partial';

    public function up(): void
    {
        Schema::create('sale_listings', function (Blueprint $table) {
            $table->id();

            $table->string('reference_no', 32)->unique();

            // The owner offering the land. All owner queries scope by this.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // The citizen land record this listing is built on. Required: a
            // listing always derives from a submitted land claim, so the
            // ownership/share checks have something to validate against.
            $table->foreignId('land_record_id')->constrained()->cascadeOnDelete();

            $table->foreignId('plot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mouza_id')->nullable()->constrained()->nullOnDelete();

            // ---- Location (denormalised for public display + search) ----
            $table->string('district_name', 128)->nullable();
            $table->string('upazila_name', 128)->nullable();
            $table->string('mouza_name', 128)->nullable();
            $table->string('jl_no', 32)->nullable();

            // ---- Land identifiers ----
            $table->string('survey_type', 8)->nullable();
            $table->string('khatian_no', 64)->nullable();
            $table->string('dag_no', 64)->nullable();

            // ---- Area ----
            $table->decimal('area', 14, 4)->nullable();
            $table->string('area_unit', 16)->default('decimal');

            // ---- Price ----
            $table->decimal('asking_price', 16, 2)->nullable();
            $table->string('price_unit', 16)->default('BDT');

            // ---- Sale scope ----
            $table->string('sale_type', 16)->default(self::SALE_FULL_PLOT);
            $table->unsignedBigInteger('claim_share_numerator')->default(1);
            $table->unsignedBigInteger('claim_share_denominator')->default(1);

            // ---- Public presentation ----
            $table->string('title', 191)->nullable();
            $table->text('public_description')->nullable();

            // Contact preference only. The actual contact value is NOT stored
            // here; buyers express interest and the owner decides whether to
            // reveal contact details. See buyer_interest_requests.
            $table->string('contact_preference', 32)->default('via_platform');

            // ---- Lifecycle ----
            $table->string('status', 24)->default(self::STATUS_DRAFT);
            $table->string('verification_status', 24)->default(self::VERIFICATION_UNVERIFIED);

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Public search hits this index most often.
            $table->index(['status', 'verification_status']);
            $table->index('user_id');
            $table->index('land_record_id');
            $table->index(['district_name', 'upazila_name']);
            $table->index('mouza_id');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_listings');
    }
};

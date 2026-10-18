<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Ownership verification requests (buyer-initiated).
 *
 * A buyer may request verification for ANY land reference, including land that
 * has no listing. That is why the land identifiers are stored directly on this
 * table rather than being reachable only through a listing FK.
 *
 * SEPARATION OF DUTIES
 *   `user_id`     — who asked. Never permitted to review their own request.
 *   `reviewed_by` — the reviewer. Enforced to differ from `user_id` in the
 *                   application layer (see VerificationRequestPolicy).
 *
 * HONESTY
 *   `official_status` exists to distinguish the three genuinely different things
 *   that can be known about a claim:
 *
 *     1. document_reviewed  — a human looked at uploaded paperwork
 *     2. internal_consistency — our own records agree/disagree with the claim
 *     3. official_pending   — NO government integration exists; this is the
 *                             default and the honest answer
 *
 *   There is no government API integration in this project. `official_status`
 *   can therefore never legitimately become `officially_verified`, and the UI
 *   must never label a record "Government Verified".
 */
return new class extends Migration
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ADDITIONAL_INFO_REQUIRED = 'additional_information_requested';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_CLOSED = 'closed';

    public const OFFICIAL_NOT_AVAILABLE = 'official_verification_not_available';
    public const OFFICIAL_PENDING = 'official_verification_pending';

    public function up(): void
    {
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();

            $table->string('reference_no', 32)->unique();

            // The requesting buyer. Must never equal reviewed_by.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Optional link to a published listing, when the buyer found the
            // land through the platform. Nullable by design: verification may be
            // requested for land that is not listed at all.
            $table->foreignId('sale_listing_id')->nullable()->constrained()->nullOnDelete();

            // Optional link to a land record, when the reference resolves to one.
            $table->foreignId('land_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mouza_id')->nullable()->constrained()->nullOnDelete();

            // ---- Land reference, as supplied by the buyer ----
            $table->string('district_name', 128)->nullable();
            $table->string('upazila_name', 128)->nullable();
            $table->string('mouza_name', 128)->nullable();
            $table->string('jl_no', 32)->nullable();
            $table->string('survey_type', 8)->nullable();
            $table->string('khatian_no', 64)->nullable();
            $table->string('dag_no', 64)->nullable();
            $table->decimal('area', 14, 4)->nullable();

            $table->string('deed_no', 64)->nullable();
            $table->string('registration_office', 191)->nullable();

            // Why the buyer wants the check. Required — keeps the audit
            // meaningful and mirrors the Phase 3 purpose requirement.
            $table->text('reason');

            // ---- Outcome ----
            $table->string('status', 32)->default(self::STATUS_SUBMITTED);
            $table->string('official_status', 32)->default(self::OFFICIAL_NOT_AVAILABLE);

            // Deliberately NOT named "verified". This is a WARNING INDICATOR,
            // not a finding of fraud. 0-100, null when not assessed.
            $table->unsignedTinyInteger('risk_indicator')->nullable();

            // Human-readable summary and the explicit limits of what was checked.
            $table->text('outcome_summary')->nullable();
            $table->text('outcome_limitations')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index(['khatian_no', 'dag_no']);
            $table->index('sale_listing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Citizen-submitted land records.
 *
 * WHY A NEW TABLE RATHER THAN WRITING TO `plots`:
 *   `plots` is the authoritative registry table, guarded by
 *   PartyPolicy/registry_officer authorization and carrying a
 *   unique(mouza_id, khatian_no, plot_no) constraint. A citizen submitting a
 *   claim is NOT writing registry truth, so their submission lives here and
 *   only REFERENCES a plot when one already exists.
 *
 *   `plot_id` is therefore nullable: a citizen may describe land that has no
 *   registry plot row yet. Nothing here is ever marked verified automatically.
 *
 * OWNERSHIP IS NEVER ASSUMED:
 *   `claim_share_numerator` / `claim_share_denominator` store the share the user
 *   CLAIMS, as an exact fraction (matching the Phase 3 convention). It defaults
 *   to 1/1 for convenience but is explicitly a claim, not a determination, and
 *   `party_id` is nullable because a claim need not be tied to a verified party.
 */
return new class extends Migration
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ADDITIONAL_INFO_REQUIRED = 'additional_information_requested';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CORRECTION_REQUESTED = 'correction_requested';

    public const PURPOSE_RECORD_KEEPING = 'record_keeping';
    public const PURPOSE_SALE_LISTING = 'sale_listing';

    public function up(): void
    {
        Schema::create('land_records', function (Blueprint $table) {
            $table->id();

            // Owner of the submission. Every query in the citizen panel is
            // scoped by this column; there is no cross-user access path.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Optional reference to the authoritative registry plot, when one
            // already exists for this khatian/plot/mouza combination.
            $table->foreignId('plot_id')->nullable()->constrained()->nullOnDelete();

            // The party the user claims to be. Nullable: a claim can be made
            // before any verified link exists. Authority is derived from
            // party_user_links.status = verified, never from this column alone.
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();

            // ---- Location ----
            // Stored directly so a submission survives independently of the geo
            // hierarchy, and so free-text mouza/JL entry is possible.
            $table->foreignId('mouza_id')->nullable()->constrained()->nullOnDelete();
            $table->string('district_name', 128)->nullable();
            $table->string('upazila_name', 128)->nullable();
            $table->string('mouza_name', 128)->nullable();
            $table->string('jl_no', 32)->nullable();

            // ---- Land identifiers ----
            $table->string('survey_type', 8)->nullable();   // CS | SA | RS | BS
            $table->string('khatian_no', 64);
            $table->string('dag_no', 64);                   // dag / plot number
            $table->decimal('area', 14, 4)->nullable();
            $table->string('area_unit', 16)->default('decimal');

            // ---- Claimed ownership ----
            $table->unsignedBigInteger('claim_share_numerator')->default(1);
            $table->unsignedBigInteger('claim_share_denominator')->default(1);
            $table->string('ownership_basis', 32)->nullable();
            $table->string('ownership_basis_other', 191)->nullable();

            // ---- Deed reference ----
            $table->string('deed_no', 64)->nullable();
            $table->date('deed_date')->nullable();
            $table->string('registration_office', 191)->nullable();

            // ---- Mutation ----
            $table->string('mutation_status', 32)->nullable();
            $table->string('mutation_no', 64)->nullable();

            // ---- Intent + lifecycle ----
            $table->string('intended_use', 32)->default(self::PURPOSE_RECORD_KEEPING);
            $table->string('status', 32)->default(self::STATUS_DRAFT);

            // Reviewer trail. Separate actor from the submitter by design.
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['khatian_no', 'dag_no']);
            $table->index('mouza_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_records');
    }
};

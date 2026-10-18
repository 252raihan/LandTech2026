<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Verified user ↔ party linking.
 *
 * WHY A SEPARATE TABLE (and not users.party_id):
 *   `PartySchemaTest::test_users_table_schema_is_unchanged_by_phase_three`
 *   asserts the `users` table columns are EXACTLY the Phase 2 set. Adding
 *   `users.party_id` would break that contract, so the link lives here instead.
 *
 * WHY A STATUS COLUMN:
 *   A user must never gain authority over a party's land merely by asserting an
 *   identity number. A link therefore starts `pending` and only confers any
 *   ownership authority once a reviewer has set it to `verified`. This is the
 *   single source of truth for "may this user act for this party".
 *
 * `evidence_document_id` is intentionally a nullable FK to land_documents
 * (created later in this phase) — the constraint is added in that migration.
 */
return new class extends Migration
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    public function up(): void
    {
        Schema::create('party_user_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();

            $table->string('status', 16)->default(self::STATUS_PENDING);

            // The user's stated basis for the claim. Never treated as proof.
            $table->string('claim_basis', 32)->nullable();
            $table->text('claim_note')->nullable();

            // Reviewer decision — deliberately separate from the claimant.
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->timestamps();

            // One link per (user, party) pair: re-claiming updates in place.
            $table->unique(['user_id', 'party_id']);
            $table->index(['user_id', 'status']);
            $table->index(['party_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_user_links');
    }
};

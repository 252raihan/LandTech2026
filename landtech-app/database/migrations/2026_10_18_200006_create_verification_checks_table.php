<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Individual verification checks belonging to a request.
 *
 * WHY A CHILD TABLE:
 *   A verification outcome is never a single boolean. Each check has its own
 *   result, its own evidence and its own explicit limitation, so the UI can show
 *   precisely what WAS and WAS NOT established. Collapsing these into one
 *   "verified" flag would overstate what the system actually knows.
 *
 * `is_automated` distinguishes a machine consistency check (string/field
 * comparison against our own records) from a human reviewer's assessment.
 * Nothing here contacts any government system — there is no such integration.
 */
return new class extends Migration
{
    public const RESULT_PASS = 'pass';
    public const RESULT_FAIL = 'fail';
    public const RESULT_INCONCLUSIVE = 'inconclusive';
    public const RESULT_NOT_AVAILABLE = 'not_available';
    public const RESULT_PENDING = 'pending';

    /**
     * The checks the system knows how to run or record.
     *
     * @var list<string>
     */
    public const CHECKS = [
        'record_identity_consistency',   // our records match the supplied identifiers
        'deed_record_consistency',       // submitted deed reference is self-consistent
        'ownership_share_consistency',   // claimed share vs recorded shares
        'ownership_chain_evidence',      // historical ownership rows exist and agree
        'mutation_status',               // only when a reliable source supports it
        'lien_mortgage_status',          // only when an authorized source supports it
        'duplicate_sale_indicators',     // same plot claimed by multiple live listings
        'court_dispute_status',          // only when reliable evidence supports it
    ];

    public function up(): void
    {
        Schema::create('verification_checks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('verification_request_id')->constrained()->cascadeOnDelete();

            $table->string('check_code', 48);
            $table->string('result', 16)->default(self::RESULT_PENDING);

            // What the check actually looked at, in plain language.
            $table->text('detail')->nullable();

            // Explicit statement of what this check CANNOT establish.
            $table->text('limitation')->nullable();

            $table->boolean('is_automated')->default(false);

            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->nullable();

            $table->timestamps();

            $table->unique(['verification_request_id', 'check_code']);
            $table->index('result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_checks');
    }
};

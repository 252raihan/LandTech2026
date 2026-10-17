<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Sensitive read audit log (append-only, hash-chained).
 *
 * Every read of identity material is recorded: masked views, full views,
 * NID searches and exports. The `created_at`-only timestamp (no updated_at) is
 * intentional — rows are meant to be immutable.
 *
 * Chain integrity:
 *   record_hash = sha256(canonical_payload + '|' + prev_hash)
 *   chain_position is UNIQUE, so a forked chain fails loudly on insert.
 *
 * LIMITATION (documented, not hidden): a database-backed hash chain is
 * TAMPER-EVIDENT, NOT TAMPER-PROOF. Anyone with direct write access to this
 * table can recompute the entire chain from genesis. There is no external trust
 * anchor and no third-party verifiability. Production would require append-only
 * storage (WORM), external anchoring, and key custody outside the application
 * database. The frontend LocalStorage ledger is a DEMO ARTIFACT ONLY and is
 * never treated as authoritative.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensitive_read_audits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 32);
            $table->string('purpose', 191)->nullable();

            $table->foreignId('subject_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('subject_identity_id')->nullable()->constrained('party_identities')->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 191)->nullable();

            // Hash-chain columns.
            $table->unsignedBigInteger('chain_position');
            $table->string('prev_hash', 64);
            $table->string('record_hash', 64);

            $table->timestamp('created_at')->nullable();

            $table->unique('chain_position');
            $table->index('action');
            $table->index('subject_party_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensitive_read_audits');
    }
};

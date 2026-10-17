<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Party identities: the sensitive NID layer.
 *
 * Kept in a SEPARATE table from `parties` on purpose, so the sensitive columns
 * can be locked down, audited, and rotated independently of ordinary party data.
 *
 * Security model:
 *  - id_number_encrypted     : Laravel Crypt (AES-256-CBC, APP_KEY). Randomized
 *                              ciphertext — NEVER indexed, NEVER queried, NEVER
 *                              serialized.
 *  - id_number_lookup_hmac   : keyed HMAC-SHA256 over the NORMALIZED value using
 *                              NID_HMAC_KEY. Deterministic (so it is indexable
 *                              and searchable) but non-reversible.
 *                              A bare sha256(nid) is NOT used: the NID space is
 *                              small enough to brute-force in seconds.
 *  - id_last4                : safe display fragment for masking (•• 1234).
 *  - key_version             : which HMAC key generation produced the lookup hash,
 *                              enabling staged, zero-downtime key rotation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();

            $table->string('id_type', 32)->default('nid');

            $table->text('id_number_encrypted');
            $table->string('id_number_lookup_hmac', 64);
            $table->string('id_last4', 4)->nullable();
            $table->unsignedSmallInteger('key_version')->default(1);

            $table->boolean('is_primary')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // The only index on identity material — and it is over the HMAC, not the value.
            $table->unique(['id_type', 'id_number_lookup_hmac']);
            $table->index('party_id');
            $table->index('key_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_identities');
    }
};

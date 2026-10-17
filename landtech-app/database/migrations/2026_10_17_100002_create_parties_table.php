<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Parties: the identity root for both individuals and organizations.
 *
 * Bilingual columns follow the Phase 1 geo convention (name_en / name_bn).
 * Merges are non-destructive: a merged party points at its survivor via
 * merged_into_party_id rather than being deleted, so historical ownership
 * records stay resolvable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('party_type', 16)->default('individual');
            $table->string('display_name_en');
            $table->string('display_name_bn');
            $table->string('legal_name_en')->nullable();
            $table->string('legal_name_bn')->nullable();

            // Organization-only identifier. Synthetic (e.g. SIM-ORG-0001) in this phase.
            $table->string('org_registration_no', 64)->nullable()->unique();

            // Individual-only attributes.
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 16)->nullable();

            $table->string('status', 16)->default('active');

            $table->foreignId('merged_into_party_id')->nullable()
                ->constrained('parties')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('party_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};

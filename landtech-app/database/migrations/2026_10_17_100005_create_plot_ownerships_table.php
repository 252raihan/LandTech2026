<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Plot ownerships: the join between parties and plots.
 *
 * Shares are stored as EXACT integer fractions (numerator / denominator) rather
 * than floats or percentages, so fractional co-ownership sums cleanly and future
 * Faraz/inheritance arithmetic stays lossless. A share of 1/3 is 1 and 3, never
 * 0.3333.
 *
 * Temporal model: disposed_on IS NULL means the ownership is still current.
 * Setting disposed_on (instead of deleting the row) preserves the historical
 * chain that later double-sale detection will read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plot_ownerships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();

            $table->string('ownership_role', 32)->default('sole');

            $table->unsignedBigInteger('share_numerator')->default(1);
            $table->unsignedBigInteger('share_denominator')->default(1);

            $table->date('acquired_on')->nullable();
            $table->date('disposed_on')->nullable();

            // Deed linkage is intentionally a loose string in Phase 3; the deeds
            // table and its FK belong to a later phase.
            $table->string('source_deed_no', 64)->nullable();

            $table->string('status', 16)->default('current');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['plot_id', 'status']);
            $table->index(['party_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plot_ownerships');
    }
};

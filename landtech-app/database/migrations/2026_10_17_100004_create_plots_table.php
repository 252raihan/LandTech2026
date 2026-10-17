<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Plots: the land side of the party↔plot relationship.
 *
 * SCOPE NOTE: plot geometry, GeoJSON storage, PostGIS columns, spatial indexes
 * and overlap queries are deliberately DEFERRED to the later GIS phase, per the
 * approved Phase 3 scope. Nothing here is PostgreSQL-specific.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plots', function (Blueprint $table) {
            $table->id();

            // Ties the plot into the Phase 1 geo hierarchy (district > upazila > mouza).
            $table->foreignId('mouza_id')->constrained()->cascadeOnDelete();

            $table->string('khatian_no', 64);
            $table->string('plot_no', 64);
            $table->string('survey_type', 32)->nullable();

            $table->decimal('area_decimal', 12, 4)->nullable();

            $table->string('status', 16)->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['mouza_id', 'khatian_no', 'plot_no']);
            $table->index('mouza_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plots');
    }
};

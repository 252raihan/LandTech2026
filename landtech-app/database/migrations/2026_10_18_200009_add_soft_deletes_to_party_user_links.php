<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 follow-up — add the soft-delete column that PartyUserLink requires.
 *
 * `App\Models\PartyUserLink` uses SoftDeletes, so every query appends
 * `where deleted_at is null`. Without this column those queries fail at runtime.
 *
 * Added as a separate migration rather than editing the already-applied
 * create_party_user_links_table migration, so existing databases migrate forward
 * without a rebuild.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_user_links', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('party_user_links', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Evidence-document links.
 *
 * Deferred from earlier migrations because `land_documents` had to exist first.
 *
 *   party_user_links.evidence_document_id  — paperwork supporting a user's claim
 *                                            to be a given party.
 *   verification_requests.…                — evidence a buyer attached to a
 *                                            verification request.
 *
 * Both are nullable and nullOnDelete, so removing a document never destroys the
 * link record or the request itself — the history stays readable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_user_links', function (Blueprint $table) {
            $table->foreignId('evidence_document_id')->nullable()
                ->after('claim_note')
                ->constrained('land_documents')
                ->nullOnDelete();
        });

        Schema::table('verification_requests', function (Blueprint $table) {
            // Evidence the requester supplied, when any.
            $table->foreignId('evidence_document_id')->nullable()
                ->after('reason')
                ->constrained('land_documents')
                ->nullOnDelete();

            // Reviewer's conclusion document, when any.
            $table->foreignId('outcome_document_id')->nullable()
                ->after('outcome_limitations')
                ->constrained('land_documents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('party_user_links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evidence_document_id');
        });

        Schema::table('verification_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evidence_document_id');
            $table->dropConstrainedForeignId('outcome_document_id');
        });
    }
};

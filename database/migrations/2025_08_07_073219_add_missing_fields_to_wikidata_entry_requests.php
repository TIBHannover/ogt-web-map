<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add missing fields for person data
     */
    public function up(): void
    {
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            // Add status_token column if it doesn't exist
            if (!Schema::hasColumn('wikidata_entry_requests', 'status_token')) {
                $table->string('status_token')->nullable()->after('status');
            }
            
            // Add status_history column if it doesn't exist
            if (!Schema::hasColumn('wikidata_entry_requests', 'status_history')) {
                $table->json('status_history')->nullable()->after('status_token');
            }
            
            // Add status_updated_at column if it doesn't exist
            if (!Schema::hasColumn('wikidata_entry_requests', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status_history');
            }
            
            // Add comment column if it doesn't exist
            if (!Schema::hasColumn('wikidata_entry_requests', 'comment')) {
                $table->text('comment')->nullable()->after('status_updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            // Remove columns in reverse order
            if (Schema::hasColumn('wikidata_entry_requests', 'comment')) {
                $table->dropColumn('comment');
            }
            
            if (Schema::hasColumn('wikidata_entry_requests', 'status_updated_at')) {
                $table->dropColumn('status_updated_at');
            }
            
            if (Schema::hasColumn('wikidata_entry_requests', 'status_history')) {
                $table->dropColumn('status_history');
            }
            
            if (Schema::hasColumn('wikidata_entry_requests', 'status_token')) {
                $table->dropColumn('status_token');
            }
        });
    }
};

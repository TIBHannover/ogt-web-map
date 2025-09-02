<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add time qualifier columns to wikidata_entry_requests table
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            $table->string('time_point_qualifier')->nullable()->after('time_point');
            $table->string('time_period_start_qualifier')->nullable()->after('time_period_start');
            $table->string('time_period_end_qualifier')->nullable()->after('time_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            $table->dropColumn('time_point_qualifier');
            $table->dropColumn('time_period_start_qualifier');
            $table->dropColumn('time_period_end_qualifier');
        });
    }
};
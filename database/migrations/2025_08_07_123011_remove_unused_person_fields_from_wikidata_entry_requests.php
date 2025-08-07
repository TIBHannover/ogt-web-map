<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to remove unused person fields that are now stored in victims/perpetrators arrays.
     */
    public function up(): void
    {
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            // Remove these fields as they're now stored in the victims/perpetrators arrays
            $table->dropColumn([
                'gender',
                'nationality',
                'place_of_birth',
                'place_of_death',
                'place_of_detention'
            ]);
        });
    }

    /**
     * Reverse the migrations - add back the columns if needed.
     */
    public function down(): void
    {
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            // Add columns back for rollback
            $table->json('gender')->nullable()->after('sources');
            $table->json('nationality')->nullable()->after('gender');
            $table->json('place_of_birth')->nullable()->after('nationality');
            $table->json('place_of_death')->nullable()->after('place_of_birth');
            $table->json('place_of_detention')->nullable()->after('place_of_death');
        });
    }
};

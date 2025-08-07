<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wikibase_entry_requests', function (Blueprint $table) {
            // Remove person-specific fields from the main event table
            // These are now stored in the victim/perpetrator arrays
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wikibase_entry_requests', function (Blueprint $table) {
            // Add back the columns if we need to roll back
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('place_of_death')->nullable();
            $table->string('place_of_detention')->nullable();
        });
    }
};
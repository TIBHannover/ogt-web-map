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
        Schema::table("wikidata_entry_requests", function (Blueprint $table) {
            $table->json("gender")->nullable();
            $table->json("nationality")->nullable();
            $table->json("place_of_birth")->nullable();
            $table->json("place_of_death")->nullable();
            $table->json("place_of_detention")->nullable();
            $table->string("victim_count_qualifier")->nullable();
            $table->string("survivor_count_qualifier")->nullable();
            $table->string("death_count_qualifier")->nullable();
            $table->string("arrest_count_qualifier")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("wikidata_entry_requests", function (Blueprint $table) {
            $table->dropColumn([
                "gender",
                "nationality",
                "place_of_birth",
                "place_of_death",
                "place_of_detention",
            ]);
        });
    }
};

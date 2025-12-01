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
            $table
                ->boolean("is_standalone_person")
                ->default(false)
                ->after("type");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("wikidata_entry_requests", function (Blueprint $table) {
            $table->dropColumn("is_standalone_person");
        });
    }
};

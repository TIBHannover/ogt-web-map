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
        Schema::create("wikidata_entry_requests", function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->text("description");
            $table->string("type");
            $table->string("coordinates")->nullable();
            $table->json("victims")->nullable();
            $table->json("perpetrators")->nullable(); // Täter
            $table->string("time_point")->nullable(); // Zeitpunkt, falls das Ereignis zu einem bestimmten Zeitpunkt stattfand
            $table->string("time_period_start")->nullable(); // Beginn des Zeitraums
            $table->string("time_period_end")->nullable(); // Ende des Zeitraums
            $table->integer("victim_count")->nullable(); // Anzahl der Opfer
            $table->integer("survivor_count")->nullable(); // Anzahl der Überlebenden
            $table->integer("death_count")->nullable(); // Anzahl der Todesfälle
            $table->integer("arrest_count")->nullable(); // Anzahl der Festnahmen
            $table->text("cause")->nullable(); // Ursache der Verfolgung (Einzelursache)
            $table->json("selected_causes")->nullable(); // Ursachen der Verfolgung (Mehrere)
            $table->string("image_url")->nullable();
            $table->string("image_filename")->nullable();
            $table->json("sources")->nullable();
            $table->json("created_entities")->nullable()->after("wikidata_id");
            $table->enum("status", [
                "pending",
                "approved",
                "rejected",
                "error",
            ]);
            $table->string("wikidata_id")->nullable();
            $table->string("submitted_by")->nullable(); // String statt User-ID
            $table->string("approved_by")->nullable(); // String statt User-ID
            $table->string("rejected_by")->nullable(); // String statt User-ID
            $table->text("rejection_reason")->nullable();
            $table->text("error_message")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("wikidata_entry_requests");
    }
};

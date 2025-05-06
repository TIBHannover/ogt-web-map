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
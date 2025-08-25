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
            if (
                !Schema::hasColumn(
                    "wikidata_entry_requests",
                    "notification_sent_at",
                )
            ) {
                $table
                    ->timestamp("notification_sent_at")
                    ->nullable()
                    ->after("notify_on_publish");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("wikidata_entry_requests", function (Blueprint $table) {
            if (
                Schema::hasColumn(
                    "wikidata_entry_requests",
                    "notification_sent_at",
                )
            ) {
                $table->dropColumn("notification_sent_at");
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailNotificationFieldsToWikidataEntryRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            $table->string('email')->nullable()->after('status_token');
            $table->boolean('notify_on_publish')->default(false)->after('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wikidata_entry_requests', function (Blueprint $table) {
            $table->dropColumn(['email', 'notify_on_publish']);
        });
    }
}

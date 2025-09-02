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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->string('wikidata_id')->nullable()->after('is_admin');
            $table->string('wikidata_username')->nullable()->after('wikidata_id');
            $table->text('wikidata_token')->nullable()->after('wikidata_username');
            $table->text('wikidata_token_secret')->nullable()->after('wikidata_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_admin',
                'wikidata_id',
                'wikidata_username',
                'wikidata_token',
                'wikidata_token_secret'
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConvertGestapoPlaceFieldsToArrays extends Migration
{
    /**
     * The fields to convert from string to array format
     */
    protected $fieldsToConvert = [
        'key_event',
        'director',
        'employees',
        'parent_organization',
        'child_organization',
        'predecessor_organization',
        'successor_organization',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get all entry requests that have any of the GestapoPlace fields
        $requests = DB::table('wikidata_entry_requests')
            ->whereNotNull('key_event')
            ->orWhereNotNull('director')
            ->orWhereNotNull('employees')
            ->orWhereNotNull('parent_organization')
            ->orWhereNotNull('child_organization')
            ->orWhereNotNull('predecessor_organization')
            ->orWhereNotNull('successor_organization')
            ->get();

        foreach ($requests as $request) {
            $updates = [];

            // Check each field and convert string values to arrays
            foreach ($this->fieldsToConvert as $field) {
                if (isset($request->$field) && !empty($request->$field)) {
                    // Skip if already JSON array format
                    if (substr($request->$field, 0, 1) === '[') {
                        continue;
                    }

                    // Convert string value to JSON array with single item
                    $updates[$field] = json_encode([$request->$field]);
                }
            }

            // Only update if we have changes
            if (!empty($updates)) {
                DB::table('wikidata_entry_requests')
                    ->where('id', $request->id)
                    ->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Get all entry requests with array values for the GestapoPlace fields
        $requests = DB::table('wikidata_entry_requests')
            ->where(function ($query) {
                foreach ($this->fieldsToConvert as $field) {
                    $query->orWhere($field, 'like', '[%]');
                }
            })
            ->get();

        foreach ($requests as $request) {
            $updates = [];

            // Check each field and convert arrays back to strings (first item only)
            foreach ($this->fieldsToConvert as $field) {
                if (isset($request->$field) && !empty($request->$field) && substr($request->$field, 0, 1) === '[') {
                    $array = json_decode($request->$field, true);

                    // Take only the first item if available
                    if (!empty($array) && isset($array[0])) {
                        $updates[$field] = $array[0];
                    } else {
                        $updates[$field] = null;
                    }
                }
            }

            // Only update if we have changes
            if (!empty($updates)) {
                DB::table('wikidata_entry_requests')
                    ->where('id', $request->id)
                    ->update($updates);
            }
        }
    }
}

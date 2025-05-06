<?php

use App\Http\Controllers\PingController;
use App\Http\Controllers\WikidataController;
use App\Http\Controllers\WikidataEntityController;
use App\Http\Controllers\WikidataSearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware("auth:api")->get("/user", function (Request $request) {
    return $request->user();
});

Route::get("/wikidata/persons", [WikidataController::class, "getPersons"]);
Route::get("/wikidata/persons/cache", [
    WikidataController::class,
    "getCachedPersons",
]);
Route::get("/wikidata/places", [WikidataController::class, "getPlaces"]);

Route::get("/wikidata/entities", [
    WikidataSearchController::class,
    "searchByTitle",
]);
Route::post("/wikidata/entities", [WikidataEntityController::class, "create"]);
Route::get("/wikidata/requests", [
    WikidataEntityController::class,
    "getPendingRequests",
]);
Route::post("/wikidata/requests/{id}/approve", [
    WikidataEntityController::class,
    "approveRequest",
]);
Route::post("/wikidata/requests/{id}/reject", [
    WikidataEntityController::class,
    "rejectRequest",
]);
Route::post("/wikidata/requests/{id}/retry", [
    WikidataEntityController::class,
    "retryRequest",
]);

Route::get("/ping", [PingController::class, "ping"]);

// Route for searching people in Wikidata
Route::get('/wikidata/search/people', [WikidataSearchController::class, 'searchPeople']);

// Route for searching concepts in Wikidata (subclasses of specific entity)
Route::get('/wikidata/search/concepts', [WikidataSearchController::class, 'searchConcepts']);

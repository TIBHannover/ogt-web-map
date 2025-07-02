<?php

use App\Http\Controllers\PingController;
use App\Http\Controllers\WikidataController;
use App\Http\Controllers\WikidataEntityController;
use App\Http\Controllers\WikidataOAuthController;
use App\Http\Controllers\WikidataSearchController;
use App\Http\Controllers\WikimediaCommonsController;
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
Route::get("/wikidata/requests/{id}", [
    WikidataEntityController::class,
    "getRequest",
]);
Route::post("/wikidata/requests/{id}/update-image", [
    WikidataEntityController::class,
    "updateImage",
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
Route::post("/wikidata/requests/{id}/retract", [
    WikidataEntityController::class,
    "retractRequest",
]);

// OAuth-Routen für Wikidata
Route::get("/wikidata/oauth", [
    WikidataOAuthController::class,
    "redirectToWikidata",
]);
Route::get("/wikidata/oauth/callback", [
    WikidataOAuthController::class,
    "handleCallback",
]);
Route::get("/wikidata/oauth/status", [
    WikidataOAuthController::class,
    "status",
]);

// Route für die Suche nach Bildern in Wikimedia Commons
Route::get("/wikimedia/images", [
    WikimediaCommonsController::class,
    "searchImages",
]);

Route::get("/ping", [PingController::class, "ping"]);

// Route for searching people in Wikidata
Route::get("/wikidata/search/people", [
    WikidataSearchController::class,
    "searchPeople",
]);

// Route for searching concepts in Wikidata (subclasses of specific entity)
Route::get("/wikidata/search/cause", [
    WikidataSearchController::class,
    "searchCauses",
]);

// Route for checking entry status
Route::get("/entry-status/{token}", [
    App\Http\Controllers\EntryStatusController::class,
    "getStatus",
]);

// Route for searching books in Wikidata
Route::get("/wikidata/search/books", [
    WikidataSearchController::class,
    "searchBooks",
]);

// Route for searching archives in Wikidata
Route::get("/wikidata/search/archives", [
    WikidataSearchController::class,
    "searchArchives",
]);

// Route for searching places in Wikidata
Route::get("/wikidata/search/places", [
    WikidataSearchController::class,
    "searchPlaces",
]);

// General Wikidata search route
Route::get("/wikidata/search", [
    App\Http\Controllers\Api\WikidataSearchController::class,
    "search",
]);

// Route for creating Wikidata entities programmatically
Route::post("/wikidata/entity", [
    WikidataEntityController::class,
    "createEntity",
]);

// Route for entity creation webhook callback
Route::post("/wikidata/entity-callback", [
    WikidataEntityController::class,
    "handleEntityCallback",
]);

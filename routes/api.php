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

Route::get("/wikidata/persons", [WikidataController::class, "getPersons"]);
Route::get("/wikidata/persons/cache", [
    WikidataController::class,
    "getCachedPersons",
]);
Route::get("/wikidata/places", [WikidataController::class, "getPlaces"]);
Route::get("/wikidata/places/cache", [
    WikidataController::class,
    "getCachedPlaces",
]);

// Occupation search endpoint
Route::get("/wikidata/occupations", [
    WikidataSearchController::class,
    "searchOccupations",
]);

Route::get("/wikidata/nationalities", [
    WikidataSearchController::class,
    "searchNationalities",
]);

// Route for searching entities by title
Route::get("/wikidata/entities", [
    WikidataSearchController::class,
    "searchByTitle",
])
    ->withoutMiddleware(["throttle:api"])
    ->middleware(["throttle:600,1"]);

// Route for getting a single entity by ID
Route::get("/wikidata/entity/{id}", [
    WikidataSearchController::class,
    "getEntityById",
])
    ->withoutMiddleware(["throttle:api"])
    ->middleware(["throttle:600,1"]);

// Route for getting a single entity by ID
Route::get("/wikidata/entity/{id}", [
    WikidataSearchController::class,
    "getEntityById",
])
    ->withoutMiddleware(["throttle:api"])
    ->middleware(["throttle:600,1"]);
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
Route::get("/eintrag-status/{token}", [
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

Route::get("/wikidata/search/nationalities", [
    WikidataSearchController::class,
    "searchNationalities",
]);

// Endpoint for occupations search
Route::get("/wikidata/search/occupations", [
    WikidataSearchController::class,
    "searchOccupations",
]);

// Route for commemorates search
Route::get("/wikidata/search/commemorates", [
    WikidataSearchController::class,
    "searchCommemorates",
]);

// Route for organizations search
Route::get("/wikidata/search/organizations", [
    WikidataSearchController::class,
    "searchOrganizations",
]);

// General Wikidata search route
Route::get("/wikidata/search", [WikidataSearchController::class, "search"]);

// Route for creating Wikidata entities programmatically
Route::post("/wikidata/entity", [
    WikidataEntityController::class,
    "createEntity",
]);

// Route for sending status link to user via email
Route::post("/eintrag-status/send-email", [
    App\Http\Controllers\EntryStatusController::class,
    "sendStatusLinkToUser",
]);

// Route for manually triggering notifications (for testing)
Route::post("/eintrag-status/send-manual-notification", [
    App\Http\Controllers\EntryStatusController::class,
    "sendManualNotification",
]);

// Route for checking if email notifications are enabled for an entry
Route::get("/eintrag-status/check-notifications/{token}", [
    App\Http\Controllers\EntryStatusController::class,
    "checkNotificationStatus",
]);

// PDF Thumbnail routes
Route::get("/pdf/thumbnails", [
    App\Http\Controllers\PDFThumbnailController::class,
    "getAllThumbnails",
]);
Route::post("/pdf/generate-thumbnail", [
    App\Http\Controllers\PDFThumbnailController::class,
    "generateThumbnail",
]);
Route::get("/pdf/{filename}", [
    App\Http\Controllers\PDFThumbnailController::class,
    "servePdf",
])->where("filename", ".*");

// Additional route to check if a PDF exists
Route::get("/pdf/check/{filename}", [
    App\Http\Controllers\PDFThumbnailController::class,
    "checkPdfExists",
])->where("filename", ".*");

// Route for downloading multiple PDFs as a ZIP archive
Route::post("/download-pdfs", [
    App\Http\Controllers\PDFDownloadController::class,
    "downloadPdfs",
]);

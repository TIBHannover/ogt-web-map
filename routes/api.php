<?php

use App\Http\Controllers\PingController;
use App\Http\Controllers\WikibaseController;
use App\Http\Controllers\WikibaseSearchController;
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

// Using WikibaseController that selects data source based on configuration
Route::get("/wikidata/persons", [WikibaseController::class, "getPersons"]);
Route::get("/wikidata/persons/cache", [
    WikibaseController::class,
    "getCachedPersons",
]);
Route::get("/wikidata/places", [WikibaseController::class, "getPlaces"]);
Route::get("/wikidata/places/cache", [
    WikibaseController::class,
    "getCachedPlaces",
]);

// Optional routes with factgrid prefix that use the same controller
Route::get("/factgrid/persons", [WikibaseController::class, "getPersons"]);
Route::get("/factgrid/persons/cache", [
    WikibaseController::class,
    "getCachedPersons",
]);
Route::get("/factgrid/places", [WikibaseController::class, "getPlaces"]);
Route::get("/factgrid/places/cache", [
    WikibaseController::class,
    "getCachedPlaces",
]);

// Diagnostic route to test FactGrid connection
Route::get("/factgrid/test-connection", [
    WikibaseController::class,
    "testFactgridConnection",
]);

// Occupation search endpoint
Route::get("/wikidata/occupations", [
    WikibaseSearchController::class,
    "searchOccupations",
]);

Route::get("/wikidata/nationalities", [
    WikibaseSearchController::class,
    "searchNationalities",
]);

// Route for searching entities by title
Route::get("/wikidata/entities", [
    WikibaseSearchController::class,
    "searchByTitle",
])
    ->withoutMiddleware(["throttle:api"])
    ->middleware(["throttle:600,1"]);

// Route for getting a single entity by ID
Route::get("/wikidata/entity/{id}", [
    WikibaseSearchController::class,
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
    WikibaseSearchController::class,
    "searchPeople",
]);

// Route for searching concepts in Wikidata (subclasses of specific entity)
Route::get("/wikidata/search/cause", [
    WikibaseSearchController::class,
    "searchCauses",
]);

// Route for checking entry status
Route::get("/eintrag-status/{token}", [
    App\Http\Controllers\EntryStatusController::class,
    "getStatus",
]);

// Route for searching books in Wikidata
Route::get("/wikidata/search/books", [
    WikibaseSearchController::class,
    "searchBooks",
]);

// Route for searching archives in Wikidata
Route::get("/wikidata/search/archives", [
    WikibaseSearchController::class,
    "searchArchives",
]);

// Route for searching places in Wikidata
Route::get("/wikidata/search/places", [
    WikibaseSearchController::class,
    "searchPlaces",
]);

Route::get("/wikidata/search/nationalities", [
    WikibaseSearchController::class,
    "searchNationalities",
]);

// Endpoint for occupations search
Route::get("/wikidata/search/occupations", [
    WikibaseSearchController::class,
    "searchOccupations",
]);

// Route for commemorates search
Route::get("/wikidata/search/commemorates", [
    WikibaseSearchController::class,
    "searchCommemorates",
]);

// Route for organizations search
Route::get("/wikidata/search/organizations", [
    WikibaseSearchController::class,
    "searchOrganizations",
]);

// Route for services search
Route::get("/wikidata/services", [
    WikibaseSearchController::class,
    "searchServices",
]);

// Route for services search (alternate endpoint)
Route::get("/wikidata/search/services", [
    WikibaseSearchController::class,
    "searchServices",
]);

// General Wikidata search route
Route::get("/wikidata/search", [WikibaseSearchController::class, "search"]);

// Add FactGrid search routes with the same controller
Route::get("/factgrid/entities", [
    WikibaseSearchController::class,
    "searchByTitle",
]);
Route::get("/factgrid/entity/{id}", [
    WikibaseSearchController::class,
    "getEntityById",
]);
Route::get("/factgrid/search", [WikibaseSearchController::class, "search"]);
Route::get("/factgrid/search/people", [
    WikibaseSearchController::class,
    "searchPeople",
]);
Route::get("/factgrid/search/places", [
    WikibaseSearchController::class,
    "searchPlaces",
]);
Route::get("/factgrid/search/organizations", [
    WikibaseSearchController::class,
    "searchOrganizations",
]);

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

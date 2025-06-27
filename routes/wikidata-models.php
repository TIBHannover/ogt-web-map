<?php

use App\Http\Controllers\WikidataModelExampleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Wikidata Model Example Routes
|--------------------------------------------------------------------------
|
| These routes demonstrate the usage of the new Wikidata entity models
|
*/

Route::prefix('wikidata-models')->group(function () {
    // Example route using models directly
    Route::post('/event', [WikidataModelExampleController::class, 'createEventWithModels']);
    
    // Example route using the WikidataFormatterModels service
    Route::post('/event-formatter', [WikidataModelExampleController::class, 'createEventWithFormatterModels']);
});
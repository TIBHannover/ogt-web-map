<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public export routes
Route::post("/export/download", [
    \App\Http\Controllers\ExportController::class,
    "export",
])->name("public.export.download");

Route::get("/export/download", [
    \App\Http\Controllers\ExportController::class,
    "export",
])->name("public.export.download.get");

Route::get("{any}", function () {
    return view("app");
})->where("any", ".*");

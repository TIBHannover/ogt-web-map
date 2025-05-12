<?php

use Illuminate\Support\Facades\Route;

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

Route::get("{any}", function () {
    return view("app");
})->where("any", ".*");

// Admin-Routen mit Auth-Middleware
Route::get("/admin/wikidata-requests/{id?}", function () {
    return view("admin.wikidata-requests");
})->name("admin.wikidata-requests");

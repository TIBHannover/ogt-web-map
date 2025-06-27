<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WikidataAuthController;
use App\Http\Controllers\WikidataController;
use App\Http\Controllers\WikidataPropertyController;
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

// Public frontend routes - let Vue.js router handle them
Route::get('/{vue_capture?}', function () {
    return view('app');
})->where('vue_capture', '^(?!admin|api|auth|login|register|profile).*$')->name('home');

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Wikidata Auth routes
Route::get('/auth/wikidata', [WikidataAuthController::class, 'redirectToWikidata'])->name('auth.wikidata');
Route::get('/auth/wikidata/callback', [WikidataAuthController::class, 'handleCallback'])->name('auth.wikidata.callback');

// Verbindungsrouten (erfordern Authentifizierung)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');
    
    Route::get('/connect/wikidata', [WikidataAuthController::class, 'connectAccount'])->name('connect.wikidata');
    Route::post('/disconnect/wikidata', [WikidataAuthController::class, 'disconnectAccount'])->name('disconnect.wikidata');
});

// Admin routes (erfordern Admin-Berechtigungen)
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');
    Route::get('/wikidata-settings', [DashboardController::class, 'wikidataSettings'])->name('admin.wikidata.settings');
    Route::get('/requests', [DashboardController::class, 'requests'])->name('admin.requests');
    Route::get('/requests/{id}', [DashboardController::class, 'showRequest'])->name('admin.requests.show');
    Route::get('/users', [DashboardController::class, 'users'])->name('admin.users');
    
    // Jobs management routes
    Route::get('/jobs', [\App\Http\Controllers\Admin\JobsController::class, 'index'])->name('admin.jobs.index');
    Route::delete('/jobs/{id}', [\App\Http\Controllers\Admin\JobsController::class, 'destroy'])->name('admin.jobs.destroy');
    Route::delete('/jobs', [\App\Http\Controllers\Admin\JobsController::class, 'destroyAll'])->name('admin.jobs.destroy-all');
    
    // Failed jobs management routes
    Route::delete('/jobs/failed/{id}', [\App\Http\Controllers\Admin\JobsController::class, 'destroyFailed'])->name('admin.jobs.destroy-failed');
    Route::delete('/jobs/failed', [\App\Http\Controllers\Admin\JobsController::class, 'destroyAllFailed'])->name('admin.jobs.destroy-all-failed');
    Route::post('/jobs/failed/{id}/retry', [\App\Http\Controllers\Admin\JobsController::class, 'retryFailed'])->name('admin.jobs.retry-failed');
    Route::post('/jobs/failed/retry-all', [\App\Http\Controllers\Admin\JobsController::class, 'retryAllFailed'])->name('admin.jobs.retry-all-failed');
});

// Wikidata API routes
Route::prefix('api/wikidata')->group(function () {
    Route::get('/entity/{qNumber}', [WikidataPropertyController::class, 'getByQNumber'])->name('api.wikidata.entity');
});
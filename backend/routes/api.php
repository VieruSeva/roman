<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Main API endpoints
Route::get('/', [ApiController::class, 'index']);
Route::get('/preview', [ApiController::class, 'preview']);
Route::get('/new-preview', [ApiController::class, 'newPreview']);
Route::get('/links', [ApiController::class, 'links']);
Route::get('/demo', [ApiController::class, 'demo']);
Route::get('/test-image-extraction', [ApiController::class, 'testImageExtraction']);

// Status Check endpoints
Route::post('/status', [ApiController::class, 'createStatusCheck']);
Route::get('/status', [ApiController::class, 'getStatusChecks']);
Route::get('/status/{status_id}', [ApiController::class, 'getStatusCheck']);

// News image extraction endpoints
Route::post('/fetch-news-image', [ApiController::class, 'fetchNewsImage']);
Route::post('/fetch-multiple-news-images', [ApiController::class, 'fetchMultipleNewsImages']);
Route::delete('/clear-image-cache', [ApiController::class, 'clearImageCache']);
Route::delete('/clear-all-image-cache', [ApiController::class, 'clearAllImageCache']);

// File download endpoints
Route::get('/download/industria-bauturilor.pdf', [ApiController::class, 'downloadIndustriaBauturilor']);
Route::get('/download/oferta-lactate-ro.pdf', [ApiController::class, 'downloadOfertaLactate']);
Route::get('/download/oferta-carne-si-oua-ro.pdf', [ApiController::class, 'downloadOfertaCarne']);
Route::get('/download/minist1.pdf', [ApiController::class, 'downloadMinist1']);
Route::get('/download/minist2.pdf', [ApiController::class, 'downloadMinist2']);
Route::get('/download/minist3.docx', [ApiController::class, 'downloadMinist3']);
Route::get('/download/minist4.pdf', [ApiController::class, 'downloadMinist4']);

// News ticker endpoint
Route::get('/news-ticker', [ApiController::class, 'getNewsTicker']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

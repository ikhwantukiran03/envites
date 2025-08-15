<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;

// Your existing routes...

// File upload routes
Route::prefix('files')->group(function () {
    Route::post('upload', [FileController::class, 'upload']);
    Route::post('upload-multiple', [FileController::class, 'uploadMultiple']);
    Route::delete('delete', [FileController::class, 'delete']);
    Route::get('list', [FileController::class, 'list']);
    Route::post('url', [FileController::class, 'getUrl']);
});
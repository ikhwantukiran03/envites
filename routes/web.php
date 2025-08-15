<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;

Route::view('/', 'welcome');

Route::get('/test-upload', function () {
    return view('test-upload');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

<?php

declare(strict_types=1);

use App\Http\Controllers\BlogController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ToolsPagesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/{slug}', PagesController::class)->where('slug', '[a-zA-Z0-9\-]+')->name('page');
Route::get('/blogs/{slug}', BlogController::class)->where('slug', '[a-zA-Z0-9\-]+')->name('blog');

Route::get('/tools/{slug}', ToolsPagesController::class)
    ->where('slug', '[a-zA-Z0-9\-]+')
    ->name('tools');

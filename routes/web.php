<?php

use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [NewsController::class, 'index'])->name('news.index');

Route::prefix('api')->group(function () {
    Route::get('/news', [NewsController::class, 'getNews'])->name('api.news');
    Route::get('/news/search', [NewsController::class, 'search'])->name('api.news.search');
});

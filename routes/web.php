<?php

use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NewsController::class, 'index'])->name('news.index');

Route::prefix('api')->group(function () {
    Route::get('/news', [NewsController::class, 'getNews'])->name('news.get');
    Route::get('/news/search', [NewsController::class, 'searchNews'])->name('news.search');
});

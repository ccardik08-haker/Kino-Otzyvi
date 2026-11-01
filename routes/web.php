<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PeopleController;

Route::get('/', [MovieController::class, 'index'])->name('home');
Route::get('/category/{id}', [MovieController::class, 'byCategory'])->name('category');
Route::get('/movie/{id}', [MovieController::class, 'show'])->name('movie');
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth', 'role:Модератор,Администратор'])->group(function () {
    Route::get('/moderator', [MovieController::class, 'create'])->name('moderator');
    Route::post('/moderator', [MovieController::class, 'store']);
    Route::get('/my-movies', [MovieController::class, 'myMovies'])->name('my-movies');
    Route::get('/my-movies/{id}/edit', [MovieController::class, 'edit'])->name('movie.edit');
    Route::post('/my-movies/{id}/update', [MovieController::class, 'update'])->name('movie.update');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/movie/{id}/review', [ReviewController::class, 'store']);
});

Route::middleware(['auth', 'role:Администратор'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::post('/admin/user/{id}/ban', [AdminController::class, 'toggleBan']);
    Route::post('/admin/user/{id}/score', [AdminController::class, 'changeScore']);
    Route::post('/admin/user/{id}/role', [AdminController::class, 'changeRole']);
    Route::delete('/admin/movie/{movieId}/comment/{reviewId}',[AdminController::class, 'deleteComment'])->whereNumber(['movieId', 'reviewId'])->name('admin.comment.delete');
});

Route::middleware(['auth', 'role:Модератор,Администратор',])->prefix('people')->name('people.')->group(function () {
        Route::get('/{type}', [PeopleController::class, 'index'])->where('type', 'actors|directors')->name('index');
        Route::post('/{type}', [PeopleController::class, 'store'])->where('type', 'actors|directors')->name('store');
        Route::get('/{type}/{id}/edit',[PeopleController::class, 'edit'])->where('type', 'actors|directors')->whereNumber('id')->name('edit');
        Route::put('/{type}/{id}',[PeopleController::class, 'update']
        )->where('type', 'actors|directors')->whereNumber('id')->name('update');
});
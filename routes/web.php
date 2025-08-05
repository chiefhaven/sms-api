<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});


Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/profile', [HomeController::class, 'index'])->name('profile');

Route::get('/clients', [ClientController::class, 'index'])->name('clients');

Auth::routes();

Route::get('/virtual-reality', [HomeController::class, 'index'])->name('virtual-reality');

Route::get('/rtl', [HomeController::class, 'index'])->name('rtl');

Route::get('/profile-static', [HomeController::class, 'index'])->name('profile-static');

Route::get('/sign-in-static', [HomeController::class, 'index'])->name('sign-in-static');

Route::get('/sign-up-static', [HomeController::class, 'index'])->name('sign-up-static');
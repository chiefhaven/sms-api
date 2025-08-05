<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Api\SMSController;
use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/home', [HomeController::class, 'index'])->name('page');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [AuthController::class, 'register'])->name('register.api'); // Signup
Route::post('login', [AuthController::class, 'login'])->name('login.perform'); // Login


Route::post('/send-sms', [SMSController::class, 'sendSms'])->name('send-sms')->middleware('auth:sanctum');

Route::middleware(['web'])->group(function () {
    Route::get('/clients', [ClientController::class, 'fetchClients'])->name('clients.fetch');
    Route::post('/updateClientStatus/{client}', [ClientController::class, 'updateStatus'])->name('clients.updateStatus');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
});
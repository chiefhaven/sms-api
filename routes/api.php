<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Api\SMSController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClientController;
use App\Models\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth:sanctum');

Route::get('/home', [HomeController::class, 'index'])->name('page')->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register', [AuthController::class, 'register'])->name('register.api'); // Signup
Route::post('login', [AuthController::class, 'login'])->name('login.perform'); // Login


Route::post('/send-sms', [SMSController::class, 'sendSms'])->name('send-sms')->middleware('auth:sanctum');
Route::get('/client-balance', [ClientController::class, 'fetchClientBalance'])->name('client-balance')->middleware('auth:sanctum');

Route::middleware(['web', 'auth:sanctum'])->group(function () {
    Route::get('/clients', [ClientController::class, 'fetchClients'])->name('clients.fetch');
    Route::post('/updateClientStatus/{client}', [ClientController::class, 'updateStatus'])->name('clients.updateStatus');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
});

Route::middleware(['web','auth:sanctum'])->group(function () {
    Route::post('/clients', [ClientController::class, 'store']);
    Route::put('/clients/{id}', [ClientController::class, 'update']);
    Route::post('/updateClientStatus/{id}', [ClientController::class, 'updateStatus']);
});

Route::middleware(['web','auth:sanctum'])->group(function () {
    Route::post('/billing', [BillingController::class, 'store'])->name('bills.store');
    Route::put('/billing/{id}', [BillingController::class, 'update'])->name('bills.update');
    Route::delete('/billing/{id}', [BillingController::class, 'destroy'])->name('bills.destroy');
    Route::get('/bills', [BillingController::class, 'fetchBills'])->name('bills.fetchBills');
    Route::get('/billing/{id}', [BillingController::class, 'show'])->name('bills.show');
    Route::get('/billing/create', [BillingController::class, 'create'])->name('bills.create');
    Route::get('/billing/{id}/edit', [BillingController::class, 'edit'])->name('bills.edit');
});
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('throttle:30,1')->group(function () {  // 30 requests per minute for these routes
    Route::get('balance', [WalletController::class, 'balance']);
    Route::get('transaction-history', [WalletController::class, 'transactionHistory']);
});

Route::middleware('throttle:30,1')->group(function () {  // 5 requests per minute for sensitive actions
    Route::post('add-funds', [WalletController::class, 'addFunds']);
    Route::post('withdraw-funds', [WalletController::class, 'withdrawFunds']);
    Route::post('transfer-funds', [WalletController::class, 'transferFunds']);
    Route::get('/get-currency', [WalletController::class, 'getCurrency']);
    Route::post('/update-currency', [WalletController::class, 'updateCurrency']);
    Route::post('/convert-currency', [WalletController::class, 'convertCurrency']);
});

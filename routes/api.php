<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;

Route::get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/user-details', [WalletController::class, 'info']);
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('balance', [WalletController::class, 'balance']);
Route::get('transaction-history', [WalletController::class, 'transactionHistory']);
Route::get('/get-currency', [WalletController::class, 'getCurrency']);
Route::post('/update-currency', [WalletController::class, 'updateCurrency']);
Route::post('/convert-currency', [WalletController::class, 'convertCurrency']);

Route::middleware('throttle:5,1')->group(function () {  // 5 requests per minute for sensitive actions
    Route::post('add-funds', [WalletController::class, 'addFunds']);
    Route::post('withdraw-funds', [WalletController::class, 'withdrawFunds']);
    Route::post('transfer-funds', [WalletController::class, 'transferFunds']);

});

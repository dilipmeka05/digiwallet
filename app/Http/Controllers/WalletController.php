<?php

namespace App\Http\Controllers;

use App\CurrencyConverter;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DB;

class WalletController extends Controller
{
    private $dailyLimit = 10000;
    private $suspiciousThreshold = 5000;
    private $suspiciousTimeFrame = 10;

    public function balance()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json(['balance' => $user->wallet->balance]);
    }

    public function info()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json($user);
    }

    public function addFunds(Request $request)
    {
        $validated = $request->validate(['amount' => 'required|numeric|min:1']);
        $user = JWTAuth::parseToken()->authenticate();

        if ($this->hasReachedDailyLimit($user, $validated['amount'])) {
            return response()->json(['message' => 'Daily transaction limit exceeded'], 400);
        }

        if ($this->isSuspiciousActivity($user, $validated['amount'])) {
            Log::warning("Suspicious activity detected for user {$user->id}");
            return response()->json(['message' => 'Suspicious activity detected'], 403);
        }

        $wallet = $user->wallet;
        $wallet->balance += $validated['amount'];
        $wallet->save();

        Transaction::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => $validated['amount'],
            'balance_after' => $wallet->balance,
        ]);

        return response()->json(['balance' => $wallet->balance]);
    }

    public function withdrawFunds(Request $request)
    {
        $validated = $request->validate(['amount' => 'required|numeric|min:1']);
        $user = JWTAuth::parseToken()->authenticate();

        if ($this->hasReachedDailyLimit($user, $validated['amount'])) {
            return response()->json(['message' => 'Daily transaction limit exceeded'], 400);
        }

        if ($this->isSuspiciousActivity($user, $validated['amount'])) {
            Log::warning("Suspicious activity detected for user {$user->id}");
            return response()->json(['message' => 'Suspicious activity detected'], 403);
        }

        $wallet = $user->wallet;
        if ($wallet->balance < $validated['amount']) {
            return response()->json(['message' => 'Insufficient funds'], 400);
        }

        $wallet->balance -= $validated['amount'];
        $wallet->save();

        Transaction::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => $validated['amount'],
            'balance_after' => $wallet->balance,
        ]);

        return response()->json(['balance' => $wallet->balance]);
    }

    public function transferFunds(Request $request)
    {
        $validated = $request->validate([
            'recipient_email' => 'required|email',
            'amount' => 'required|numeric|min:1',
        ]);

        $user = JWTAuth::parseToken()->authenticate();
        $recipient = User::where('email', $validated['recipient_email'])->first();

        if (!$recipient) {
            return response()->json(['message' => 'Recipient not found'], 404);
        }

        if ($this->hasReachedDailyLimit($user, $validated['amount'])) {
            return response()->json(['message' => 'Daily transaction limit exceeded'], 400);
        }

        if ($this->isSuspiciousActivity($user, $validated['amount'])) {
            Log::warning("Suspicious activity detected for user {$user->id}");
            return response()->json(['message' => 'Suspicious activity detected'], 403);
        }

        $wallet = $user->wallet;
        if ($wallet->balance < $validated['amount']) {
            return response()->json(['message' => 'Insufficient funds'], 400);
        }

        $wallet->balance -= $validated['amount'];
        $wallet->save();

        $recipient->wallet->balance += $validated['amount'];
        $recipient->wallet->save();

        Transaction::create([
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => $validated['amount'],
            'balance_after' => $wallet->balance,
        ]);

        Transaction::create([
            'user_id' => $recipient->id,
            'type' => 'credit',
            'amount' => $validated['amount'],
            'balance_after' => $recipient->wallet->balance,
        ]);

        return response()->json(['message' => 'Transfer successful']);
    }

    public function transactionHistory()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $transactions = Transaction::where('user_id', $user->id)->get();
        return response()->json(['transactions' => $transactions]);
    }

    private function hasReachedDailyLimit($user, $amount)
    {
        $todayTotal = Transaction::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');

        return ($todayTotal + $amount) > $this->dailyLimit;
    }

    private function isSuspiciousActivity($user, $amount)
    {
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->where('amount', '>=', $this->suspiciousThreshold)
            ->where('created_at', '>=', now()->subMinutes($this->suspiciousTimeFrame))
            ->count();

        return $recentTransactions >= 3;
    }

    public function getCurrency(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $wallet = DB::table('wallets')
                ->where('user_id', $user->id)
                ->first();

            if (!$wallet) {
                return response()->json(['error' => 'Wallet not found for user'], 404);
            }

            Log::info("User currency: " . $wallet->currency);

            return response()->json(['currency' => $wallet->currency], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching currency: ' . $e->getMessage());
            return response()->json(['error' => 'Token is invalid or expired'], 401);
        }
    }


    public function updateCurrency(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $validated = $request->validate([
                'currency' => 'required|string|in:USD,EUR,GBP',
            ]);

            $wallet = Wallet::where('user_id', $user->id)->first();

            if ($wallet) {
                $currentCurrency = $wallet->currency;

                if ($currentCurrency === $validated['currency']) {
                    return response()->json(['success' => true, 'message' => 'Currency is already set to ' . $currentCurrency], 200);
                }

                $currencyConverter = new CurrencyConverter();
                $convertedBalance = $currencyConverter->convertCurrency($currentCurrency, $validated['currency'], $wallet->balance);

                if ($convertedBalance === null) {
                    return response()->json(['error' => 'Failed to fetch exchange rate or conversion error.'], 500);
                }

                $wallet->currency = $validated['currency'];
                $wallet->balance = round($convertedBalance, 2);
                $wallet->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Currency and balance updated successfully.',
                    'convertedBalance' => $convertedBalance
                ], 200);
            }

            return response()->json(['error' => 'Wallet not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update currency and balance: ' . $e->getMessage());

            return response()->json(['error' => 'An error occurred while updating currency and balance.'], 500);
        }
    }



    protected $currencyConverter;

    public function __construct(CurrencyConverter $currencyConverter)
    {
        $this->currencyConverter = $currencyConverter;
    }

    public function convertCurrency(Request $request)
    {
        $fromCurrency = $request->input('from_currency');
        $toCurrency = $request->input('to_currency');
        $amount = $request->input('amount');

        $convertedAmount = $this->currencyConverter->convertCurrency($fromCurrency, $toCurrency, $amount);

        if ($convertedAmount !== null) {
            return response()->json(['convertedAmount' => $convertedAmount]);
        }

        return response()->json(['error' => 'Currency conversion failed.'], 400);
    }
}

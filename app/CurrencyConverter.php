<?php

namespace App;

use Illuminate\Support\Facades\Http;

class CurrencyConverter
{
    protected $apiKey;
    protected $apiUrl = 'https://v6.exchangerate-api.com/v6/{API_KEY}/latest/';

    public function __construct()
    {
        $this->apiKey = env('EXCHANGE_RATE_API_KEY');
    }

    public function getExchangeRates($baseCurrency)
    {
        $url = str_replace('{API_KEY}', $this->apiKey, $this->apiUrl) . $baseCurrency;
        $response = Http::get($url);

        if ($response->successful()) {
            return $response->json(); 
        }

        return null;
    }

    public function convertCurrency($fromCurrency, $toCurrency, $amount)
    {
        $rates = $this->getExchangeRates($fromCurrency);

        if ($rates && isset($rates['conversion_rates'][$toCurrency])) {
            $rate = $rates['conversion_rates'][$toCurrency];
            return $amount * $rate;
        }

        return null;
    }
}

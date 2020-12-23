<?php

/**
 * Return an array containing all available currencies in Jeeb Gateway
 *
 * @since 4.0
 * @return Array
 */

if (!function_exists('getJeebAvailableCurrencies')) {
    function getJeebAvailableCurrencies()
    {

        $available_currencies = array(
            "BTC" => "BTC (Bitcoin)",
            "IRR" => "IRR (Iranian Rials)",
            "IRT" => "IRT (Iranian Toman)",
            "USD" => "USD (US Dollar)",
            "EUR" => "EUR (Euro)",
            "GBP" => "GBP (Pound)",
            "CAD" => "CAD (CA Dollar)",
            "AUD" => "AUD (AU Dollar)",
            "JPY" => "JPY (Yen)",
            "CNY" => "CNY (Yuan)",
            "AED" => "AED (Dirham)",
            "TRY" => "TRY (Lira)"
        );

        return $available_currencies;
    }
}

/**
 * Return an array containing all available coins in Jeeb Gateway
 *
 * @since 4.0
 * @return Array
 */

if (!function_exists('getJeebAvailableCoins')) {
    function getJeebAvailableCoins()
    {
        $available_coins = array(
            "BTC" => 'BTC (Bitcoin)',
            "ETH" => "ETH (Ethereum)",
            "DOGE" => "DOGE (Dogecoin)",
            "LTC" => "LTC (Litecoin)",
            "USDT" => "USDT (TetherUS)",
            "BNB" => "BNB (Binance Coin)",
            "USDC" => "USDC (USD Coin)",
            "LINK" => "LINK (Chainlink)",
            "ZRX" => "ZRX (0x)",
            "PAX" => "PAX (Paxos Standard)",
            "TBTC" => "TBTC (Bitcoin Testnet)",
            "TETH" => "TETH (Ethereum Testnet)"
        );

        return $available_coins;
    }
}

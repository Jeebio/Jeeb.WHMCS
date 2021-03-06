<?php

use WHMCS\Database\Capsule;

include 'jeeb/includes/currencies.php';


$JEEB_GATEWAY = getGatewayVariables(JEEB_MODULE_NAME);

/**
 * Make a string containing payable coins based on Module configurations
 *
 * @since 3.3
 * @return string
 */
if (!function_exists('getPayableCoins')) {

    function getPayableCoins()
    {
        global $JEEB_GATEWAY;

        $available_coins = getJeebAvailableCoins();

        $payable_coins = array();
        foreach ($available_coins as $coin) {
            if ($JEEB_GATEWAY[$coin] === 'yes') {
                $payable_coins[] = $coin;
            }
        }
        $payable_coins = implode('/', $payable_coins);

        return $payable_coins;
    }
}

/**
 * Prepare parameters for create_payment() located in create_payment.php
 */
if (!function_exists('prepareCreatePaymentParams')) {
    function prepareCreatePaymentParams()
    {
        global $JEEB_GATEWAY;

        // get invoice
        $invoice_id = (int) $_POST['invoiceId'];
        $invoice_data = Capsule::connection()->select("SELECT tblinvoices.total, tblinvoices.status, tblcurrencies.code FROM tblinvoices, tblclients, tblcurrencies where tblinvoices.userid = tblclients.id and tblclients.currency = tblcurrencies.id and tblinvoices.id=$invoice_id");
        $invoice_data = (array) $invoice_data[0];

        if (!$invoice_data) {
            error_log('[ERROR] In modules/gateways/jeeb/createinvoice.php: No invoice found for invoice id #' . $invoice_id);
            die('[ERROR] In modules/gateways/jeeb/createinvoice.php: Invalid invoice id #' . $invoice_id);
        }

        $api_key = $JEEB_GATEWAY['apiKey']; // Api key
        $hash_key = md5($api_key . $invoice_id);
        $notification = $_POST['systemURL'] . 'modules/gateways/callback/jeeb.php?hashKey=' . $hash_key; // Notification Url
        $callback = $_POST['systemURL']; // Redirect Url
        $amount = $invoice_data['total']; // Total payable amount
        $base_currency = $JEEB_GATEWAY['baseCur'];
        $lang = $JEEB_GATEWAY["language"];
        if ($lang == '') {
            $lang = null;
        }

        $expiration = $JEEB_GATEWAY['expiration'];

        if (!isset($expiration) ||
            !is_numeric($expiration) ||
            $expiration < 15 ||
            $expiration > 2880) {
            $expiration = 15;
        }

        return array(
            'orderNo' => $invoice_id,
            'client' => 'Internal',
            'type' => 'Restricted',
            'mode' => 'Standard',
            'payableCoins' => getPayableCoins(),
            'baseAmount' => $amount,
            'baseCurrencyId' => $base_currency,
            'webhookUrl' => $notification,
            'callbackUrl' => $callback,
            "allowTestNets" => $JEEB_GATEWAY['allowTestnets'] == "on" ? true : false,
            'allowReject' => $JEEB_GATEWAY['allowRefund'] == "on" ? true : false,
            "language" => $lang,
            "expiration" => $expiration,
        );
    }
}

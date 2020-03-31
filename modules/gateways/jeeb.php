<?php

/**
 * Returns configuration options array.
 *
 * @return array
 */
function jeeb_config()
{
    $configarray = array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => "Jeeb",
        ),
        'apiKey' => array(
            'FriendlyName' => 'Signature',
            'Type' => 'text',
            'Description' => 'The signature provided by Jeeb for you merchant.',
        ),

        'baseCur' => array(
            'FriendlyName' => 'Base Currency',
            'Type' => 'dropdown',
            'Options' => 'BTC,IRR,TOMAN,USD,EUR,GBP,CAD,AUD,AED,TRY,CNY,JPY',
            'Description' => 'The base currency of your website.',
        ),
        'BTC' => array(
            "FriendlyName" => "Bitcoin",
            'Options' => 'yes,no',
            "Size" => "25",
            'Description' => "Allow Bitcoin to be payable.",
        ),
        'DOGE' => array(
            'FriendlyName' => "Dogecoin",
            'Options' => 'yes,no',
            "Size" => "25",
            'Description' => "Allow Dogecoin to be payable.",
        ),
        'LTC' => array(
            'FriendlyName' => "Litecoin",
            'Options' => 'yes,no',
            "Size" => "25",
            'Description' => "Allow Litecoin to be payable.",
        ),
        'ETH' => array(
            'FriendlyName' => "Ethereum",
            'Options' => 'yes,no',
            "Size" => "25",
            'Description' => "Allow Ethereum to be payable.",
        ),
        'TEST-BTC' => array(
            'FriendlyName' => "Bitcoin Testnet",
            'Options' => 'yes,no',
            "Size" => "25",
            'Description' => "Allow Bitcoin testnet to be payable.",
        ),
        'TEST-LTC' => array(
            'FriendlyName' => "Litecoin Testnet",
            'Options' => 'yes,no',
            "Size" => "25",
            'Description' => "Allow Litecoin testnet to be payable.",
        ),

        'network' => array(
            'FriendlyName' => 'Allow Testnets',
            'Type' => 'dropdown',
            'Options' => 'yes,no',
            'Description' => 'Allows testnets such as TEST-BTC to get processed.',
        ),
        'allowRefund' => array(
            'FriendlyName' => 'Allow Refund',
            'Type' => 'dropdown',
            'Options' => 'yes,no',
            'Description' => 'Allows payments to be refunded.',
        ),
        'language' => array(
            'FriendlyName' => 'Language',
            'Type' => 'dropdown',
            'Options' => 'Auto-select,English,Persian',
            'Description' => 'The language of the payment area.',
        ),
        'expiration' => array(
            'FriendlyName' => 'Expiration Time',
            'Type' => 'text',
            'Description' => 'Expands default payments expiration time. It should be between 15 to 2880 (mins).',
        ),
    );

    return $configarray;
}

/**
 * Returns html form.
 *
 * @param  array  $params
 * @return string
 */
function jeeb_link($params)
{
    if (false === isset($params) || true === empty($params)) {
        die('[ERROR] In modules/gateways/jeeb.php::jeeb_link() function: Missing or invalid $params data.');
    }

    // Invoice Variables
    $invoiceid = $params['invoiceid'];

    // System Variables
    $systemurl = $params['systemurl'];

    $post = array(
        'invoiceId' => $invoiceid,
        'systemURL' => $systemurl,
    );

    $form = '<form action="' . $systemurl . 'modules/gateways/jeeb/createinvoice.php" method="POST">';

    foreach ($post as $key => $value) {
        $form .= '<input type="hidden" name="' . $key . '" value = "' . $value . '" />';
    }

    $form .= '<input type="submit" value="' . $params['langpaynow'] . '" />';
    $form .= '</form>';

    return $form;
}

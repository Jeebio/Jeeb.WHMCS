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
            "Value"=>"Jeeb"
        ),
        'apiKey' => array(
            'FriendlyName' => 'Signature',
            'Type'         => 'text',
            'description' => 'The signature provided by Jeeb for you merchant.'
        ),
        'network' => array(
          'FriendlyName' => 'Allow Testnets',
          'Type'         => 'dropdown',
          'Options'      => 'yes,no',
          'description'  => 'Allows testnets such as TEST-BTC to get processed.'
        ),
        'allowRefund' => array(
          'FriendlyName' => 'Allow Refund',
          'Type'         => 'dropdown',
          'Options'      => 'yes,no',
          'description'  => 'Allows payments to be refunded.'
        ),
        'language' => array(
          'FriendlyName' => 'Language',
          'Type'         => 'dropdown',
          'Options'      => 'Auto-select,English,Persian',
          'description'  => 'The language of the payment area.'
        ),
        'expiration' => array(
            'FriendlyName' => 'Expiration Time',
            'Type'         => 'text',
            'description'  => 'Expands default payments expiration time. It should be between 15 to 2880 (mins).',
        ),
        'baseCur' => array(
          'FriendlyName' => 'Base Currency',
          'Type'         => 'dropdown',
          'Options'      => 'BTC,EUR,IRR,TOMAN,USD',
          'description' => 'The base currency of your website.'
        ),
        'BTC' => array (
          "FriendlyName" => "Payable Currencies",
          "Type" => "yesno",
          "Size" => "25",
          "Description" => "BTC",
        ),
        'XRP' => array (
          "FriendlyName" => "The currencies which users can use for payments.(Multi-Select)",
          "Type" => "yesno",
          "Size" => "25",
          "Description" => "XRP",
        ),
        'XMR' => array (
          "Type" => "yesno",
          "Size" => "25",
          "Description" => "XMR",
        ),
        'LTC' => array (
          "Type" => "yesno",
          "Size" => "25",
          "Description" => "LTC",
        ),
        'BCH' => array (
          "Type" => "yesno",
          "Size" => "25",
          "Description" => "BCH",
        ),
        'ETH' => array (
          "Type" => "yesno",
          "Size" => "25",
          "Description" => "ETH",
        ),
        'TEST-BTC' => array (
          "Type" => "yesno",
          "Size" => "25",
          "Description" => "TESTBTC",
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
        'invoiceId'     => $invoiceid,
        'systemURL'     => $systemurl,
    );

    $form = '<form action="' . $systemurl . 'modules/gateways/jeeb/createinvoice.php" method="POST">';

    foreach ($post as $key => $value) {
        $form .= '<input type="hidden" name="' . $key . '" value = "' . $value . '" />';
    }

    $form .= '<input type="submit" value="' . $params['langpaynow'] . '" />';
    $form .= '</form>';

    return $form;
}

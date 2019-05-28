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
            'FriendlyName' => 'Signature for your jeeb.io merchant account',
            'Type'         => 'text'
        ),
        'network' => array(
          'FriendlyName' => 'Test or Live',
          'Type'         => 'dropdown',
          'Options'      => 'live,test',
        ),
        'language' => array(
          'FriendlyName' => 'Select the language of payment page',
          'Type'         => 'dropdown',
          'Options'      => 'Auto-select,English,Persian',
        ),
        'baseCur' => array(
          'FriendlyName' => 'Select base currency',
          'Type'         => 'dropdown',
          'Options'      => 'BTC,EUR,IRR,TOMAN,USD',
        ),
        'BTC' => array (
          "FriendlyName" => "Target Curency",
          "Type" => "yesno",
          "Size" => "25",
          "Description" => "BTC",
        ),
        'XRP' => array (
          "FriendlyName" => "(Multi-Select)",
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

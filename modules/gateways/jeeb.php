<?php

include 'jeeb/includes/consts.php';
include 'jeeb/includes/utils.php';

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
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Description' => 'The API key provided by Jeeb for you merchant.',
        ),
        'baseCur' => array(
            'FriendlyName' => 'Base Currency',
            'Type' => 'dropdown',
            'Options' => getJeebAvailableCurrencies(),
            'Description' => 'The base currency of your website.',
        ),
    );

    $currencies = getJeebAvailableCoins();

    foreach ($currencies as $currency => $title) {
        $configarray[$currency] = array(
            "FriendlyName" => $title,
			'Type' => 'dropdown',
            'Options' => 'yes,no',
            "Size" => "25",
            'Description' => "Allow " . $title . " to be payable.",
        );
    }
    
    $configarray = array_merge($configarray, array(
        'allowTestnets' => array(
            'FriendlyName' => 'Allow Testnets',
            'Type' => 'yesno',
            'Description' => 'Allows testnets such as TEST-BTC to get processed.',
        ),
        'allowRefund' => array(
            'FriendlyName' => 'Allow Refund',
            'Type' => 'yesno',
            'Description' => 'Allows payments to be refunded.',
        ),
        'hookLog' => array(
            'FriendlyName' => 'Webhook Log',
            'Type' => 'yesno',
            'Description' => 'Allows webhook activities to be logged in module directory.',
        ),
        'language' => array(
            'FriendlyName' => 'Language',
            'Type' => 'dropdown',
            'Options' => array(
                ''   => 'Auto',
                'en' => 'English',
                'fa' => 'Persian',
            ),
            'Description' => 'The language of the payment area.',
        ),
        'expiration' => array(
            'FriendlyName' => 'Expiration Time',
            'Type' => 'text',
            'Description' => 'Expands default payments expiration time. It should be between 15 to 2880 (mins).',
        ),
    ));

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

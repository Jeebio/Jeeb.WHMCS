<?php

include '../../../includes/functions.php';
include '../../../includes/gatewayfunctions.php';
include '../../../includes/invoicefunctions.php';

if (file_exists('../../../dbconnect.php')) {
    include '../../../dbconnect.php';
} else if (file_exists('../../../init.php')) {
    include '../../../init.php';
} else {
    error_log('[ERROR] In modules/gateways/jeeb/createinvoice.php: include error: Cannot find dbconnect.php or init.php');
    die('[ERROR] In modules/gateways/jeeb/createinvoice.php: include error: Cannot find dbconnect.php or init.php');
}

include './includes/consts.php';
include './includes/utils.php';

function create_payment()
{
    global $JEEB_GATEWAY;

    $params = prepareCreatePaymentParams();

    $post = json_encode($params);

    $ch = curl_init(BASE_URL . 'payments/issue/');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($post),
        'X-API-Key: ' . $JEEB_GATEWAY['apiKey'],
        'User-Agent:' . PLUGIN_NAME . '/' . PLUGIN_VERSION)
    );

    $result = curl_exec($ch);
    $data = json_decode($result, true);

    return $data['result']['token'];

}

function redirect_payment($token)
{
    // Using Auto-submit form to redirect user with the token
    echo "<form id='form' method='post' action='" . BASE_URL . "payments/invoice'>" .
        "<input type='hidden' autocomplete='off' name='token' value='" . $token . "'/>" .
        "</form>" .
        "<script type='text/javascript'>" .
        "document.getElementById('form').submit();" .
        "</script>";
}

$token = create_payment();

redirect_payment($token);

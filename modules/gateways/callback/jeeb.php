<?php

use WHMCS\Database\Capsule;

// Required File Includes
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

define("PLUGIN_NAME", 'WHMCS');
define("PLUGIN_VERSION", '3.2');
define("BASE_URL", 'https://core.jeeb.io/api/');

function confirm_payment($signature, $options = array())
{

    $post = json_encode($options);
    $ch = curl_init(BASE_URL . 'payments/' . $signature . '/confirm/');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'User-Agent:' . PLUGIN_NAME . '/' . PLUGIN_VERSION,
    ));
    $result = curl_exec($ch);
    $data = json_decode($result, true);
    // error_log('Response =>' . var_export($data, true));
    return (bool) $data['result']['isConfirmed'];

}

$gatewaymodule = 'jeeb';
$GATEWAY = getGatewayVariables($gatewaymodule);

if (!$GATEWAY['type']) {
    logTransaction($GATEWAY['name'], $_POST, 'Not activated');
    error_log('[ERROR] In modules/gateways/callback/jeeb.php: jeeb module not activated');
    die('[ERROR] In modules/gateways/callback/jeeb.php: Jeeb module not activated.');
}

$postdata = file_get_contents("php://input");
$json = json_decode($postdata, true);

// error_log("Entered Jeeb Notifications!");

if ($json['signature'] == $GATEWAY['apiKey']) {
    if ($json['orderNo']) {
        // Checks invoice ID is a valid invoice number or ends processing
        $invoiceid = checkCbInvoiceID($json['orderNo'], $GATEWAY['name']);

        $transid = $json['referenceNo'];

        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceid)->first();

        $userid = $invoice->userid;

        // Checks transaction number isn't already in the database and ends processing if it does
        checkCbTransID($transid);

        // Successful
        $fee = 0;

        // left blank, this will auto-fill as the full balance
        $amount = '';

        switch ($json['stateId']) {
            case '2':
                // New payment, not confirmed
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Pending transaction.');
                //error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
                break;
            case '3':
                // New payment, not confirmed
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Pending confirmation.');
                //error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
                break;
            case '4':
                // Apply Payment to Invoice
                // error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Confirmation occurred for transaction.');
                $data = array(
                    "token" => $json["token"],
                );

                $is_confirmed = confirm_payment($signature, $data);

                if ($data['result']['isConfirmed']) {
                    // error_log('Payment confirmed by jeeb');
                    logTransaction($GATEWAY['name'], $json, 'Jeeb: Merchant confirmation obtained. Payment is completed.');
                    Capsule::table('tblclients')->where('id', $userid)->update(array('defaultgateway' => $gatewaymodule));
                    addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule);
                    logTransaction($GATEWAY['name'], $json, 'The transaction is now complete.');
                } else {
                    //   error_log('Payment confirmation rejected by jeeb');
                    logTransaction($GATEWAY['name'], $json, 'Jeeb: Double spending avoided.');
                }
                break;
            case '5':
                // Invoice Expired
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Payment is expired or canceled.');
                // error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
                break;
            case '6':
                // Invoice Under Paid
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Partial-paid payment occurred, transaction was refunded automatically.');
                // error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
                break;
            case '7':
                // Invoice Over Paid
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Overpaid payment occurred, transaction was refunded automatically.');
                // error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
                break;
            default:
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Unknown state received. Please report this incident.');
                // error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
        }
    }
}

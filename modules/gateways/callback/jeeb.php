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

$gatewaymodule = 'jeeb';
$GATEWAY       = getGatewayVariables($gatewaymodule);

if (!$GATEWAY['type']) {
    logTransaction($GATEWAY['name'], $_POST, 'Not activated');
    error_log('[ERROR] In modules/gateways/callback/jeeb.php: jeeb module not activated');
    die('[ERROR] In modules/gateways/callback/jeeb.php: Jeeb module not activated.');
}


$postdata = file_get_contents("php://input");
$json = json_decode($postdata, true);

error_log("Entered Jeeb Notifications!");

if($json['signature']==$GATEWAY['apiKey']){
  if($json['orderNo']){
    error_log("hey".$json['orderNo']);

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
            logTransaction($GATEWAY['name'], $json, 'The Invoice was created Successfully.');
            error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
            break;
        case '3':
            // New payment, not confirmed
            logTransaction($GATEWAY['name'], $json, 'The payment has been received, but the transaction has not been confirmed on the bitcoin network. This will be updated when the transaction has been confirmed.');
            error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
            break;
        case '4':
            // Apply Payment to Invoice
            error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
            logTransaction($GATEWAY['name'], $json, 'The payment has been received, and the transaction has been confirmed on the bitcoin network. This will be updated when the transaction has been completed.');
            $data = array(
              "token" => $json["token"]
            );

            $data_string = json_encode($data);
            $api_key = $GATEWAY['apiKey'];
            $network_uri = "https://core.jeeb.io/api/" ;
            $url = $network_uri.'payments/'.$api_key.'/confirm';
            error_log("Signature:".$api_key." Base-Url:".$network_uri." Url:".$url);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
            );

            $result = curl_exec($ch);
            $data = json_decode( $result , true);
            error_log("data = ".var_export($data, TRUE));
            var_dump("Data".$data);


            if($data['result']['isConfirmed']){
            error_log('Payment confirmed by jeeb');
            logTransaction($GATEWAY['name'], $json, 'The payment has been received, and the transaction has been confirmed on the bitcoin network. This will be updated when the transaction has been completed.');
            Capsule::table('tblclients')->where('id', $userid)->update(array('defaultgateway' => $gatewaymodule));
            addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule);
            logTransaction($GATEWAY['name'], $json, 'The transaction is now complete.');
            }
            else{
              error_log('Payment confirmation rejected by jeeb');
              logTransaction($GATEWAY['name'], $json, 'The transaction was rejected By Jeeb(Please dont deliver the order).');
            }
            break;
        case '5':
            // Invoice Expired
            logTransaction($GATEWAY['name'], $json, 'The transaction failed(The invoice expired). Do not process this order!');
            error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
            break;
        case '6':
            // Invoice Over Paid
            logTransaction($GATEWAY['name'], $json, 'The transaction was incomplete(The invoice was over paid). Do not process this order!');
            error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
            break;
        case '7':
            // Invoice Under Paid
            logTransaction($GATEWAY['name'], $json, 'The transaction was incomplete(The invoice was under paid). Do not process this order!');
            error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
            break;
        default:
            logTransaction($GATEWAY['name'], $json, 'Unknown response received.');
            error_log('Order Id received = '.$json['orderNo'].' stateId = '.$json['stateId']);
    }
  }
}

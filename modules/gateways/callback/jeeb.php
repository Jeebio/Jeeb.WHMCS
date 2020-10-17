<?php

use WHMCS\Database\Capsule;

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

include '../jeeb/includes/consts.php';
include '../jeeb/includes/utils.php';


$remoteAddress = $_SERVER["REMOTE_ADDR"];

/**
 *
 * This array could be used to set some allowed IPs to be able to hook this file.
 *
 * @var array $allowedIps
 *
 */
$allowedIps = ['35.209.237.202', '52.56.239.177'];

jeebHookLog('Receiving Hook');

/**
 *
 * Uncomment the following if block to intercept the incoming hooks
 * and terminate those referrers not included in '$allowedIps' array.
 *
 */
//if (!in_array($remoteAddress,$allowedIps)){
//    http_response_code(401);
//    jeebHookLog('Unknown remote IP', true);
//    die();
//}

jeebHookLog('remote IP: ' . $remoteAddress, true);

if (!$JEEB_GATEWAY['type']) {
    logTransaction($JEEB_GATEWAY['name'], $_POST, 'Not activated');
    jeebHookLog('[ERROR] In modules/gateways/callback/jeeb.php: jeeb module not activated', true);
    error_log('[ERROR] In modules/gateways/callback/jeeb.php: jeeb module not activated');
    die('[ERROR] In modules/gateways/callback/jeeb.php: Jeeb module not activated.');
}

$postdata = file_get_contents("php://input");

$json = json_decode($postdata, true);

jeebHookLog(json_encode($JEEB_GATEWAY));
jeebHookLog(json_encode($json));

if ($_GET['hashKey'] === md5($JEEB_GATEWAY['apiKey'] . $json['orderNo'])) {
    jeebHookLog('HashKey IS VALID');

    // Checks invoice ID is a valid invoice number or ends processing
    $invoiceid = checkCbInvoiceID($json['orderNo'], $JEEB_GATEWAY['name']);

    jeebHookLog('Invoce ID: ' . json_encode($invoiceid));

    $transid = $json['referenceNo'];
    $invoice = Capsule::table('tblinvoices')->where('id', $invoiceid)->first();

    jeebHookLog('Invoce: ' . json_encode($invoice));

    $userid = $invoice->userid;

    // Checks transaction number isn't already in the database and ends processing if it does
    jeebHookLog('Checking "checkCbTransID" OK');
    checkCbTransID($transid);
    jeebHookLog('Check "checkCbTransID" OK');


    jeebHookLog('State ID: ' . $json['state']);
    switch ($json['state']) {
        case 'PendingTransaction':
            // New payment, not confirmed
            logTransaction($JEEB_GATEWAY['name'], $json, 'Jeeb: Pending transaction.');
            jeebHookLog('Order Id received = ' . $json['orderNo'] . ' state = ' . $json['state']);
            jeebUpdateInvoice("Payment Pending", $invoiceid);
            break;
        case 'PendingConfirmation':
            // New payment, not confirmed
            logTransaction($JEEB_GATEWAY['name'], $json, 'Jeeb: Pending confirmation.');
            jeebHookLog('Order Id received = ' . $json['orderNo'] . ' state = ' . $json['state']);
            jeebUpdateInvoice("Payment Pending", $invoiceid);
            break;
        case 'Completed':
            jeebHookLog('Order Id received = ' . $json['orderNo'] . ' state = ' . $json['state']);
            logTransaction($JEEB_GATEWAY['name'], $json, 'Jeeb: Confirmation occurred for transaction.');

            $token = $json['token'];
            $is_confirmed = confirm_payment($token);
            jeebHookLog('Confirmation result: ' . json_encode($is_confirmed));

            if ($is_confirmed) {
                jeebHookLog('Payment confirmed by jeeb');
                logTransaction($JEEB_GATEWAY['name'], $json, 'Jeeb: Merchant confirmation obtained. Payment is completed.');
                Capsule::table('tblclients')->where('id', $userid)->update(array('defaultgateway' => JEEB_MODULE_NAME));

                // Successful
                $fee = 0;
                // leave blank, this will auto-fill as the full balance
                $amount = '';
                addInvoicePayment($invoiceid, $transid, $amount, $fee, JEEB_MODULE_NAME);
                logTransaction($JEEB_GATEWAY['name'], $json, 'The transaction is now complete.');
                jeebUpdateInvoice("Paid", $invoiceid);
            } else {
                jeebHookLog('Payment confirmation rejected by jeeb');
                logTransaction($JEEB_GATEWAY['name'], $json, 'Jeeb: Double spending avoided.');
                jeebUpdateInvoice("Declined", $invoiceid);
            }
            break;
        case 'Expired':
            // Invoice Expired
            logTransaction($JEEB_GATEWAY['name'], $json, 'Jeeb: Payment is expired or canceled.');
            jeebHookLog('Order Id received = ' . $json['orderNo'] . ' state = ' . $json['state']);
            jeebUpdateInvoice("Cancelled", $invoiceid);
            break;
        default:
            logTransaction($JEEB_GATEWAY['name'], $json, 'Jeeb: Unknown state received. Please report this incident.');
            jeebHookLog('Unknown state received. Order Id received = ' . $json['orderNo'] . ' state = ' . $json['state']);
    }
    http_response_code(200);
    jeebHookLog('End of Hook', true);

} else {
    http_response_code(401);
    jeebHookLog('Invalid Request', true);
}

function confirm_payment($token)
{
    global $JEEB_GATEWAY;

    $post = json_encode(array('token' => $token));

    jeebHookLog('Confirmation post data: ' . json_encode($post));
    $ch = curl_init(BASE_URL . 'payments/seal');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'X-API-Key: ' . $JEEB_GATEWAY['apiKey'],
        'User-Agent: ' . PLUGIN_NAME . '/' . PLUGIN_VERSION,
    ));
    $result = curl_exec($ch);
    $data = json_decode($result, true);

    jeebHookLog('Confirmation response data: ' . json_encode($data));

    return (bool) $data['succeed'];
}

/**
 * This function is used to modify the invoice status.
 *
 * @param string $status The desired status to set on the invoice.
 * @param integer $invoiceId The ID of the desired invoice to modify
 */
function jeebUpdateInvoice($status, $invoiceId)
{
    Capsule::table('tblinvoices')->where('id', $invoiceId)->update(array('status' => $status));
}

/**
 * This function is used to log any developer defined message in a log file
 * located in /modules/gateways/jeeb/logs/ directory.
 *
 * @param string $message The line to print in log file.
 * @param bool $mustEnd if true, will print some empty new lines in the log file
 */
function jeebHookLog($message, $mustEnd = false)
{
    global $JEEB_GATEWAY;

    /**
     *
     * If the "hookLog" option is set to no nothing will be logged.
     *
     */
    if ($JEEB_GATEWAY['hookLog'] !== 'on') {
        return;
    }

    $date = date('Y-m-d');
    $directory = dirname(getcwd()) . "/jeeb/logs/";

    if (!is_dir($directory)) {
        mkdir($directory);
    }

    $path = $directory . "{$date}.log";

    if (!file_exists($path)) {
        touch($path);
    }

    $logFile = fopen($path, 'a');
    $time = date('H:i:s');
    $timeTag = "[ {$time} UTC ]: ";
    $backtrace = '(Line: ' . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0]['line'] . ') ';
    fwrite($logFile, $timeTag . $message . '. ' . $backtrace . PHP_EOL);
    if ($mustEnd) {
        fwrite($logFile, PHP_EOL . PHP_EOL);
    }
}

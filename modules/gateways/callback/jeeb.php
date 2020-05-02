<?php

use WHMCS\Database\Capsule;

// Required File Includes
include '../../../includes/functions.php';
include '../../../includes/gatewayfunctions.php';
include '../../../includes/invoicefunctions.php';

if (file_exists('../../../dbconnect.php'))
{
    include '../../../dbconnect.php';
}
else if (file_exists('../../../init.php'))
{
    include '../../../init.php';
}
else
{
    error_log('[ERROR] In modules/gateways/jeeb/createinvoice.php: include error: Cannot find dbconnect.php or init.php');
    die('[ERROR] In modules/gateways/jeeb/createinvoice.php: include error: Cannot find dbconnect.php or init.php');
}

define("PLUGIN_NAME", 'WHMCS');
define("PLUGIN_VERSION", '3.3');
define("BASE_URL", 'https://core.jeeb.io/api/');

function confirm_payment($signature, $options = array())
{

    $post = json_encode($options);
    jeebHookLog('Confirmation post data: ' . json_encode($post));
    $ch = curl_init(BASE_URL . 'payments/' . $signature . '/confirm/');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'User-Agent:' . PLUGIN_NAME . '/' . PLUGIN_VERSION,
    ));
    $result = curl_exec($ch);
    $data   = json_decode($result, true);
    jeebHookLog('Confirmation response data: ' . json_encode($data));
    // error_log('Response =>' . var_export($data, true));
    return (bool)$data['result']['isConfirmed'];

}

$gatewaymodule = 'jeeb';
$GATEWAY       = getGatewayVariables($gatewaymodule);

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

if (!$GATEWAY['type'])
{
    logTransaction($GATEWAY['name'], $_POST, 'Not activated');
    jeebHookLog('[ERROR] In modules/gateways/callback/jeeb.php: jeeb module not activated', true);
    error_log('[ERROR] In modules/gateways/callback/jeeb.php: jeeb module not activated');
    die('[ERROR] In modules/gateways/callback/jeeb.php: Jeeb module not activated.');
}

$postdata = file_get_contents("php://input");
$json     = json_decode($postdata, true);

jeebHookLog(json_encode($GATEWAY));
jeebHookLog(json_encode($json));

if ($json['signature'] == $GATEWAY['apiKey'])
{
    jeebHookLog('Signature Ok');
    if ($json['orderNo'])
    {
        jeebHookLog('Post data seems Ok');
        // Checks invoice ID is a valid invoice number or ends processing
        $invoiceid = checkCbInvoiceID($json['orderNo'], $GATEWAY['name']);

        jeebHookLog('Invoce ID: ' . json_encode($invoiceid));

        $transid = $json['referenceNo'];

        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceid)->first();

        jeebHookLog('Invoce: ' . json_encode($invoice));

        $userid = $invoice->userid;

        // Checks transaction number isn't already in the database and ends processing if it does
        jeebHookLog('Checking "checkCbTransID" OK');
        checkCbTransID($transid);
        jeebHookLog('Check "checkCbTransID" OK');

        // Successful
        $fee = 0;

        // leave blank, this will auto-fill as the full balance
        $amount = '';

        jeebHookLog('State ID: ' . $json['stateId']);
        switch ($json['stateId'])
        {
            case '2':
                // New payment, not confirmed
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Pending transaction.');
                jeebHookLog('Order Id received = ' . $json['orderNo'] . ' stateId = ' . $json['stateId']);
                jeebHookUpdateInvoice("Payment Pending", $invoice->id);
                break;
            case '3':
                // New payment, not confirmed
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Pending confirmation.');
                jeebHookLog('Order Id received = ' . $json['orderNo'] . ' stateId = ' . $json['stateId']);
                jeebHookUpdateInvoice("Payment Pending", $invoice->id);
                break;
            case '4':
                jeebHookLog('Order Id received = ' . $json['orderNo'] . ' stateId = ' . $json['stateId']);
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Confirmation occurred for transaction.');
                $data         = array(
                    "token" => $json["token"],
                );
                $signature    = $json['signature'];
                $is_confirmed = confirm_payment($signature, $data);
                jeebHookLog('Confirmation result: ' . json_encode($is_confirmed));

                if ($is_confirmed)
                {
                    jeebHookLog('Payment confirmed by jeeb');
                    logTransaction($GATEWAY['name'], $json, 'Jeeb: Merchant confirmation obtained. Payment is completed.');
                    Capsule::table('tblclients')->where('id', $userid)->update(array('defaultgateway' => $gatewaymodule));
                    addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule);
                    logTransaction($GATEWAY['name'], $json, 'The transaction is now complete.');
                    jeebHookUpdateInvoice("Paid", $invoice->id);
                }
                else
                {
                    jeebHookLog('Payment confirmation rejected by jeeb');
                    logTransaction($GATEWAY['name'], $json, 'Jeeb: Double spending avoided.');
                }
                break;
            case '5':
                // Invoice Expired
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Payment is expired or canceled.');
                jeebHookLog('Order Id received = ' . $json['orderNo'] . ' stateId = ' . $json['stateId']);
                jeebHookUpdateInvoice("Cancelled", $invoice->id);
                break;
            case '6':
                // Invoice Under Paid
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Partial-paid payment occurred, transaction was refunded automatically.');
                jeebHookLog('Order Id received = ' . $json['orderNo'] . ' stateId = ' . $json['stateId']);
                jeebHookUpdateInvoice("Refunded", $invoice->id);
                break;
            case '7':
                // Invoice Over Paid
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Overpaid payment occurred, transaction was refunded automatically.');
                jeebHookLog('Order Id received = ' . $json['orderNo'] . ' stateId = ' . $json['stateId']);
                jeebHookUpdateInvoice("Refunded", $invoice->id);
                break;
            default:
                logTransaction($GATEWAY['name'], $json, 'Jeeb: Unknown state received. Please report this incident.');
                jeebHookLog('Unknown state received. Order Id received = ' . $json['orderNo'] . ' stateId = ' . $json['stateId']);
        }
        http_response_code(200);
        jeebHookLog('End of Hook', true);
    }
    else
    {
        http_response_code(401);
        jeebHookLog('Bad post data: ' . $json['signature'], true);
    }
}
else
{
    http_response_code(401);
    jeebHookLog('Bad signature: ' . $json['signature'], true);
}

/**
 *
 * This function is used to log any developer defined message in a log file
 * located in /modules/gateways/jeeb/logs/ directory.
 *
 * @param string $message The line to print in log file.
 * @param bool $mustEnd if true, will print some empty new lines in the log file
 */
function jeebHookLog($message, $mustEnd = false)
{
    $gatewaymodule = 'jeeb';
    $GATEWAY       = getGatewayVariables($gatewaymodule);

    /**
     *
     * If the "hookLog" option is set to no nothing will be logged.
     *
     */
    if ($GATEWAY['hookLog'] !== 'yes') return;

    $date      = date('Y-m-d');
    $directory = dirname(getcwd()) . "/jeeb/logs/";
    if (!is_dir($directory)) mkdir($directory);

    $path = $directory . "{$date}.log";

    if (!file_exists($path)) touch($path);

    $logFile   = fopen($path, 'a');
    $time      = date('H:i:s');
    $timeTag   = "[ {$time} UTC ]: ";
    $backtrace = '(Line: ' . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0]['line'] . ') ';
    fwrite($logFile, $timeTag . $message . '. ' . $backtrace . PHP_EOL);
    if ($mustEnd)
    {
        fwrite($logFile, PHP_EOL.PHP_EOL);
    }
}

/**
 *
 * This function is used to modify the invoice status.
 *
 * @param string $status The desired status to set on the invoice.
 * @param integer $invoiceId The ID of the desired invoice to modify
 */
function jeebHookUpdateInvoice($status, $invoiceId)
{
    Capsule::table('tblinvoices')->where('id', $invoiceId)->update(array('status' => $status));
}
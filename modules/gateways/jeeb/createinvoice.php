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

function convertIrrToBtc($url, $amount, $signature, $baseCur) {

    // return Jeeb::convert_irr_to_btc($url, $amount, $signature);
    $ch = curl_init($url.'currency?'.$signature.'&value='.$amount.'&base='.$baseCur.'&target=btc');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json')
  );

  $result = curl_exec($ch);
  $data = json_decode( $result , true);
  error_log('Response =>'. var_export($data, TRUE));
  return (float) $data["result"];

  }


  function createInvoice($url, $amount, $options = array(), $signature) {

      $post = json_encode($options);

      $ch = curl_init($url.'payments/' . $signature . '/issue/');
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Content-Length: ' . strlen($post))
      );

      $result = curl_exec($ch);
      $data = json_decode( $result , true);
      error_log('Response =>'. var_export($data, TRUE));

      return $data['result']['token'];

  }

  function redirectPayment($url, $token) {
    error_log("Entered into auto submit-form");
    // Using Auto-submit form to redirect user with the token
    echo "<form id='form' method='post' action='".$url."payments/invoice'>".
            "<input type='hidden' autocomplete='off' name='token' value='".$token."'/>".
           "</form>".
           "<script type='text/javascript'>".
                "document.getElementById('form').submit();".
           "</script>";
  }

$gatewaymodule = 'jeeb';

$GATEWAY = getGatewayVariables($gatewaymodule);

// get invoice
$invoiceId = (int) $_POST['invoiceId'];
$price     = false;
$result    = Capsule::connection()->select("SELECT tblinvoices.total, tblinvoices.status, tblcurrencies.code FROM tblinvoices, tblclients, tblcurrencies where tblinvoices.userid = tblclients.id and tblclients.currency = tblcurrencies.id and tblinvoices.id=$invoiceId");
$data      = (array)$result[0];

if (!$data) {
    error_log('[ERROR] In modules/gateways/jeeb/createinvoice.php: No invoice found for invoice id #' . $invoiceId);
    die('[ERROR] In modules/gateways/jeeb/createinvoice.php: Invalid invoice id #' . $invoiceId);
}

$total    = $data['total'];

unset($options['invoiceId']);
unset($options['systemURL']);

$baseUri      = "https://core.jeeb.io/api/" ;
$signature    = $GATEWAY['apiKey']; // Signature
$notification = $_POST['systemURL'].'/modules/gateways/callback/jeeb.php';  // Notification Url
$callback     = $_POST['systemURL'];  // Redirect Url
$order_total  = $total;  // Total payable amount
$baseCur      = "";
$lang         = "";
$target_cur   = "";


switch ($GATEWAY["language"]) {
  case 'Auto-select':
    $lang=NULL;
    break;
  case 'English':
    $lang="en";
    break;
  case 'Persian':
    $lang="fa";
    break;

  default:
    $lang=NULL;
    break;
}

switch ($GATEWAY["baseCur"]) {
  case 'BTC':
    $baseCur="btc";
    break;
  case 'IRR':
    $baseCur="irr";
    break;
  case 'USD':
    $baseCur="usd";
    break;
  case 'EUR':
    $baseCur="eur";
    break;
  case 'TOMAN':
    $baseCur="toman";
    break;

  default:
    # code...
    break;
}

if($baseCur=='toman'){
  $baseCur='irr';
  $order_total *= 10;
}


$params = array(
                'BTC',
                'XRP',
                'XMR',
                'LTC',
                'BCH',
                'ETH',
                'TEST-BTC'
               );

foreach ($params as $p) {
  error_log($p." = ". $GATEWAY[$p]);
  $GATEWAY[$p] == "on" ? $target_cur .= $p . "/" : $target_cur .="" ;
}

error_log("Base Uri : ".$baseUri." Signature : ".$signature." CallbackUri : ".$callBack." NotificationUri : ".$notification." Invoice Id:".$invoiceId);
error_log("Cost = ". $total. " TargetUri = ". $target_cur);



$btc = convertIrrToBtc($baseUri, $order_total, $signature, $baseCur);
$orderNo = uniqid();
error_log("orderNo : ".$orderNo);

$params = array(
  'orderNo'          => $invoiceId,
  'value'            => (float) $btc,
  'webhookUrl'       => $notification,
  'callBackUrl'      => $callback,
  'allowReject'      => $GATEWAY['network'] == "test" ? false : true,
  "coins"            => $target_cur,
  "allowTestNet"     => $GATEWAY['network'] == "test" ? true  : false,
  "language"         => $lang
);

$token = createInvoice($baseUri, $btc, $params, $signature);

redirectPayment($baseUri, $token);

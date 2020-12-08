<?php
# 异步返回页面
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
use \Illuminate\Database\Capsule\Manager as Capsule;

if(!class_exists('MGate')) {
    include("./class.php");
}

logModuleCall('MGate', 'notify', '', http_build_query($_POST));
// log_result(http_build_query($_REQUEST));
$GATEWAY 					= getGatewayVariables('MGate');
$url						= $GATEWAY['systemurl'];
$companyname 				= $GATEWAY['companyname'];
$currency					= $GATEWAY['currency'];
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
$apiUrl                     = $GATEWAY['apiUrl'];
$appId						= $GATEWAY['appId'];
$appSecret					= $GATEWAY['appSecret'];
$mGate                    = new MGate($apiUrl, $appId, $appSecret);
$strToSign = $mGate->prepareSign($_POST);
$verify_result = $mGate->verify($strToSign, $_POST['sign']);
if(!$verify_result) { 
	logTransaction($GATEWAY["name"], $_POST, "Unsuccessful");
	die('FAIL');
} else {
    $invoiceId = $_POST['out_trade_no'];
    $transId = $_POST['trade_no'];
    $paymentAmount = $_POST['total_amount'] / 100;
    $feeAmount = 0;

    $invoice = \Illuminate\Database\Capsule\Manager::table('tblinvoices')->where('id', $invoiceId)->first();
    if ($invoice->status === 'Paid') {
    	die('SUCCESS');
    }

    if ($_POST['currency'] === 'usdt') {
        $paymentAmount = $invoice->total;
    }

    checkCbTransID($transId);
    addInvoicePayment($invoiceId, $transId, $paymentAmount, $feeAmount, 'MGate');
    logTransaction($GATEWAY["name"], $_POST, "Successful-A");
    die('SUCCESS');
}
die('FAIL');
?>
<?php
/**
 * WHMCS Sample Payment Callback File
 *
 * This sample file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
 
// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$ca = new WHMCS_ClientArea();
$ca->initPage();

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve data returned in payment gateway callback

$result = $_GET["res"];
$invoiceId = $_GET["orderID"];
$transactionId = $_GET["transactID"];
$paymentAmount = $_GET["amount"];
$paymentFee = 0.00;


/**
 * Validate Callback Invoice ID.
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 *
 * @param string $transactionId Unique Transaction ID
 */
 
checkCbTransID($transactionId);


if ($result == "SUCCESS") {
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        $paymentFee,
        $gatewayModuleName
    );
    logTransaction($gatewayModuleName["name"],$_POST,"Completed");
} else {
    $values["status"] = "Failed";
    logTransaction($gatewayModuleName["name"], $_POST, "Failed");
}

$ca->assign('invoiceId', $invoiceId);
$ca->assign('transactionId', $transactionId);
$ca->assign('status', $result);
$ca->setTemplate('nmb_callback'); 
$ca->output();

?>

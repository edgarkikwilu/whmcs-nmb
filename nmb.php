<?php
/**
 * WHMCS Payment Gateway Module
 *

 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function nmb_MetaData()
{
    return array(
        'DisplayName' => 'NMB PAYMENT',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 */
function nmb_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Credit/Debit Card(Visa, Mastercard & Union Pay)',
        ),
        // A text field for Merchant ID
        'merchantID' => array(
            'FriendlyName' => 'MERCHANT ID',
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'Enter your merchant ID here',
        ),
        // A text field for API Password
        'apiPassword' => array(
            'FriendlyName' => 'API PASSWORD',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Enter your merchant API Password'
        ),
        // A text field for Merchant Username
        'merchantUsername' => array(
            'FriendlyName' => 'MERCHANT USERNAME',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Enter merchant username here, format: merchant.merchantID',
        ),
        // Sand Box input Field
        'testMode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick this to use demo API(the merchant ID and API Password entered above must belong to a demo merchant',
        ),
    );
}

/**
 * Payment link.
 *
 */
function nmb_link($params)
{
    // Gateway Configuration Parameters
    $merchantID = $params['merchantID'];
    $apiPassword = $params['apiPassword'];
    $merchantUsername = $params['merchantUsername'];
    $testMode = $params['testMode'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];
    $referenceNo = rand(100000, 999999);
    
    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    if ($testMode == "on") {
        $Url = "https://test-nmbbank.mtf.gateway.mastercard.com/api/rest/version/60/merchant/$merchantID/session";
        $ssl = "false";
    } else {
        $Url = "https://nmbbank.gateway.mastercard.com/api/rest/version/60/merchant/$merchantID/session";
        $ssl = "true";
    }
    
    $data = array (
        "apiOperation"=>"CREATE_CHECKOUT_SESSION",
        "interaction" => array("operation"=>"PURCHASE"),
        "order" => array(
            "amount"     => $amount,
            "currency"   => $currencyCode,
            "description"=> $description,
            "reference"=> $referenceNo,
            "id"=> $invoiceId,
            )
    );
    $data = json_encode($data);
        
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    curl_setopt_array($ch, array(
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => array(
            'Authorization:Basic ' . base64_encode("$merchantUsername:$apiPassword"),
            'Content-Type: application/json'
        ),
    ));
    
    $result = curl_exec($ch);
    
    $results = json_decode($result);
    
    $sessionID = $results->session->id;
    $sessionVersion = $results->session->version;
    $successIndicator = $results->successIndicator;

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    if (curl_errno($ch)) {
        return 'Error:' . curl_error($ch);
    }

    curl_close($ch);
    
    if ($testMode == "on") {
        $jurl = "https://test-nmbbank.mtf.gateway.mastercard.com/checkout/version/60/checkout.js";
    } else {
        $jurl = "https://nmbbank.gateway.mastercard.com/checkout/version/60/checkout.js";
    }
    
    
    $htmlOutput = '<button id="page-with-session" class="btn btn-success py-2 px-4" onclick="Checkout.showPaymentPage();"><h5><strong>Pay Now</strong></h5></button>
    
    <script src="'.$jurl.'"
        data-error="errorCallback"
        data-cancel="cancelCallback"
        data-beforeRedirect="beforeRedirect"
        data-afterRedirect="afterRedirect"
        data-complete="completeCallback">
    </script>
    
    <script>
        var merchantId = "'.$merchantID.'";
        var sessionId = "'.$sessionID.'";
        var sessionVersion = "'.$sessionVersion.'";
        var successIndicator = "'.$successIndicator.'";
        var orderId = "'.$invoiceId.'";
        var reference_no = "'.$referenceNo.'";
        var amount = "'.$amount.'";
        var resultIndicator = null;

        function beforeRedirect() {
            return {
                successIndicator: successIndicator,
                orderId: orderId,
                sessionId: sessionId,
                sessionVersion: sessionVersion,
                merchantId: merchantId,
                amount:amount,
                reference_no:reference_no,
            };
        }


        function afterRedirect(data) {
            if (resultIndicator) {
                var result = (resultIndicator === data.successIndicator) ? "SUCCESS" : "ERROR";
                window.location.href = "/modules/gateways/callback/nmb.php" + "?orderID=" + data.orderId + "&amount=" + data.amount + "&transactID=" + data.reference_no + "&res=" + result;
            }
            else {
                successIndicator = data.successIndicator;
                orderId = data.orderId;
                sessionId = data.sessionId;
                sessionVersion = data.sessionVersion;
                merchantId = data.merchantId;
    
                window.location.href = "/hostedCheckout/" + data.orderId + "/" + data.successIndicator + "/" + data.sessionId;
            }
        }

        function errorCallback(error) {
            console.log(JSON.stringify(error));
        }
        function cancelCallback() {
            window.location.reload(true);
        }


        function completeCallback(response) {
            resultIndicator = response;
            var result = (resultIndicator === successIndicator) ? "SUCCESS" : "ERROR";
            window.location.href = "/hostedCheckout/" + orderId + "/" + result;
        }
        
        function randomId() {
            var chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", length = 10;
            var result = "";
            for (var i = length; i > 0; --i) result += chars[Math.round(Math.random() * (chars.length - 1))];
            return result;
        }


        Checkout.configure({
            merchant: merchantId,
            order: {
                amount: '.$amount.',
                currency: "'.$currencyCode.'",
                description: "'.$description.'",
                id: orderId,
                reference: reference_no,
            },
            session: {
                id: sessionId,
                version: sessionVersion
            },
            interaction: {
                merchant: {
                    name: "'.$firstname.' '.$lastname.'",
                    address: {
                        line1: "'.$address1.'",
                        line2: "'.$address2.'"
                    }
                }}
        });
    </script>
    
    ';
    
     return $htmlOutput;

}


<?php

/*
If you are using and old WHMCS version and this callback doesn't work, uncomment these includes and comment out the New WHMCS Versions block below. - CoinPayments
# Old WHMCS Versions
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
# /Old WHMCS Versions
*/

/*
# New WHMCS Versions
*/
require("../../../init.php");
$whmcs->load_function('gateway');
$whmcs->load_function('invoice');
/*
# /New WHMCS Versions
*/

$gatewaymodule = "payiota"; # Enter your gateway module name here replacing template
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback


if (isset($_POST["address"])) {
	
	$address = $_POST["address"];
	$invoiceid = $_POST["custom"];
	$verification = $_POST["verification"];
	$paid_iota = $_POST["paid_iota"];
	$paid_usd = file_get_contents("https://payiota.me/api.php?action=convert_to_usd&iota=".$paid_iota);
	$price_iota = $_POST["price_iota"];

	//try with cURL
	if (!is_numeric($paid_usd)) {

		if(is_callable('curl_init') == false){
			echo "ERROR: file_get_contents failed and cURL is not installed";
			die(1);
		}

		$curl = curl_init();
		curl_setopt_array($curl, array(
		    CURLOPT_RETURNTRANSFER => 1,
		    CURLOPT_URL => "https://payiota.me/api.php?action=convert_to_usd&iota=".$paid_iota,
		));

		$paid_usd = curl_exec($curl);
		curl_close($curl);

		if (!is_numeric($paid_usd)) {
			echo "ERROR: file_get_contents and cURL failed";
			die(1);
		}
	}

	//for more variables see documentation
	if ($verification !== $GATEWAY['payiota_verification_key']) {
		echo "ERROR: Verification key mismatch";
		die(1);
	}

	if( $paid_iota >= $price_iota){
	


		$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing

		checkCbTransID($address); # Checks transaction number isn't already in the database and ends processing if it does

		//set to compelte
		addInvoicePayment($invoiceid,$address,$paid_usd,0.00,$gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
		logTransaction($GATEWAY["name"],$_POST,"IOTA transaction successfully completed."); # Save to Gateway Log: name, data array, status	

		echo "Order set to Complete.";
		
	}else{
		echo "Paid Iota Amount is less than Price Amount Iota.";
	}
} else {
	echo "Incorrect POST";
	die(1);
}


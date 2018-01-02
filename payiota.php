<?php

function payiota_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"PayIOTA.me"),
     "payiota_api_key" => array("FriendlyName" => "API Key", "Type" => "text", "Size" => "32", ),
     "payiota_verification_key" => array("FriendlyName" => "Verification Key", "Type" => "text", "Size" => "64", ),
    );
	return $configarray;
}

function payiota_link($params) {
	$postdata = http_build_query(
	 array(
		"action" => "new",
		'api_key' => $params['payiota_api_key'],
		'custom' => $params['invoiceid'],
		'price' => $params['amount'],
		'currency' => $params['currency'],
		'ipn_url' => $params['systemurl'].'/modules/gateways/callback/payiota.php'
	)

	);

		$opts = array('http' =>
	    array(
	        'method'  => 'POST',
	        'header'  => 'Content-type: application/x-www-form-urlencoded',
	        'content' => $postdata
	    )
	);


		$context  = stream_context_create($opts);
		$response = file_get_contents('https://payiota.me/api.php', false, $context);
		
		//cURL fallback
		if (!$response) {
			
			if(is_callable('curl_init') == false){
				echo "ERROR: file_get_contents failed and cURL is not installed";
				die(1);
			}
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl,CURLOPT_POST, 1);
			curl_setopt($curl,CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($curl, CURLOPT_URL, 'https://payiota.me/api.php');
			$response = curl_exec($curl);
			
			if (!$response) {
				echo "ERROR: file_get_contents and cURL failed";
				die(1);
			}
		}
		$response = json_decode($response, true);
		$code = '<form id="cpsform" action="https://payiota.me/external.php" method="GET">
		<input type="hidden" name="address" value="'.$response[0].'">
		<input type="hidden" name="price" value="'.$response[1].'">
		<input type="hidden" name="invoiceid" value="'.$params['invoiceid'].'">
		<input type="hidden" name="success_url" value="'.$params['systemurl'].'/clientarea.php?action=invoices">
		<input type="hidden" name="cancel_url" value="'.$params['systemurl'].'">
		<input type="image" style="cursor: pointer;" src="https://payiota.me/resources/paynow.png" alt="Pay Now with IOTA">
		</form>';
		return $code;
	
}

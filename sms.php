<?php
/** We use php cURL for the samples **/
    $ch = curl_init();
    // base url
	$url = 'https://api.afromessage.com/api/send';
	$token = 'eyJhbGciOiJIUzI1NiJ9.eyJpZGVudGlmaWVyIjoiSExRMFZHN01oekRaRVNsWXFaZ0NpeWV2ZzFNOE4yT1QiLCJleHAiOjE4NjkyODc0NDUsImlhdCI6MTcxMTUyMTA0NSwianRpIjoiNzk1MTgzOWQtNzJhZi00NGZlLTllZWUtNWU4ODZkMTYyOGM0In0.ukPZInAYGg14ElRe0uFVHZMcNRTatgMlx45ae7JCS1o';
    $from = 'e80ad9d8-adf3-463f-80f4-7c4b39f7f164';
    $sender = 'WHALE';
	$to = '+251921650867';
	$message = 'Ola';
	$callback = 'https://webhook.site/d3690eb2-9921-46e5-92a6-10c679358395';
    // request body
	$body = array("from" => $from,"sender" => $sender,"to" => $to,"message" => $message,"callback"=>$callback);
	
    /** configure request **/
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

    /** request headers **/
	$headers = array();
	$headers[] = 'Authorization: Bearer '.$token;
	$headers[] = 'Content-Type: application/json';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // send request
	$result = curl_exec($ch);

    /** handle response **/
	if (curl_errno($ch)) {
        /** general http error **/
		echo 'Error:' . curl_error($ch);
    } else {	
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		switch ($http_code) {
	    case 200:
                /** Inpsect `acknowledge` node and see if response is error or success **/
				$data = json_decode($result,true);
				if ($data['acknowledge'] == 'success') {
					echo "Api success";
                }else{
					echo "Api failure";
                }
				break;
	    default:
          /** Other API error ... mostly authorization related. Inpsect response body for details. **/
	      echo 'Other HTTP Code: ', $http_code;
        }
    }
    /** finish **/
	curl_close ($ch);
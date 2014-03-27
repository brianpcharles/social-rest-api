<?php

/**
 * Wrapper for Outgoing "Get" and "Post" REST Calls. 
 * @author bcharles
 *
 */
trait REST {
	
	/**
	 * Send a "GET" Request and parse the response.
	 *   If response is json, decode it as an array
	 *   Otherwise, return the original response.
	 *   
	 * @param string $url
	 * @throws Exception
	 * @return string|object - JSON response when applicable, 
	 * 	 else the return response.
	 */
	public static function getRequest($url) {
		//send the response and decode it.
		try {
			$data = file_get_contents($url);
			$json = json_decode($data, true);
			//if the respons is valid json and is decoded
			//return it. 
			if(json_last_error() == JSON_ERROR_NONE) {
				return $json;
			}
		 	//otherwise, just return whatever the respons was.
			return $data;
		}
		catch(Exception $e) {
			throw new Exception ("error retrieving response from 
				{$url}: " . $e->getMessage());
		}
		return false;
	}
	
	/**
	 * Send a formatted curl request to given url.
	 * 
	 * @param string $url - where to send the request.
	 * @param mixed $params - the key value post data, can be a string or object, or array.
	 * @param array $headers - optional headers.
	 * @param $method - GET, POST, PUT (Default to POST)
	 * 
	 * @throws Exception
	 * @return string - json encoded output
	 * 	response - the actual given response form the request
	 * 	header - the headers returned from the request
	 * 	status - the http status, 200, 100, 404, etc.
	 */
	public static function sendCURL($url, $params, $headers=array(), $method='POST') {
		
		//set up the curl
		$opts = array(
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_FOLLOWLOCATION => 0,
					CURLOPT_FAILONERROR => false,
					CURLOPT_SSL_VERIFYPEER => true,
					CURLOPT_HEADER => true,
					CURLOPT_VERBOSE => true,
			);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt_array($ch, $opts);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		switch($method) {
			case 'GET':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
			case 'POST':
			default:
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				break;
		}
		
		$response = curl_exec($ch);
		
		if(curl_error($ch)) {
			throw new Exception(curl_error($ch), curl_errno($ch));
		}
		//is valid?
		$respHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		
		//parse the response into seperate header and body.
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		
		$data = json_decode($body);
		if(json_last_error() != JSON_ERROR_NONE) $data = $body;
			
		$output = (object)array('status'=>$respHttpCode, 'header'=>$header, 'response'=>$data);
		return $output;
	}
}
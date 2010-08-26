<?php

/**
 * PHP 5 class to assist with Authorize.net Customer Information Manager (CIM)
 *
 * Requires cURL and SimpleXML extensions in PHP 5
 *
 * Version 0.3 on 26 Aug 2010
 * By Chris Blay (chris@meosphere.com, chris.b.blay@gmail.com)
 * Copyright (c) 2010 Meosphere (http://meosphere.com, http://meolabs.com)
 *
 * License: http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public License (LGPL)
 * Website: http://github.com/chris-blay/PHP-5-AuthDotNetCIM-Class
 *
 * Please keep this header information here
 *
 */

class AuthDotNetCIM
{
	private $api_login_id;
	private $transaction_key;
	private $test_mode;
	public $debug_mode;
	public $direct_response_separator;
	
	// used to parse directResponse in transaction results
	private static $response_fields = array('responseCode', 'responseSubcode', 'responseReasonCode', 'responseReasonText', 'authorizationCode',
		'avsResponse', 'transactionId', 'invoiceNumber', 'description', 'amount', 'method', 'transactionType', 'customerId', 'firstName',
		'lastName', 'company', 'address', 'city', 'state', 'zipCode', 'country', 'phone', 'fax', 'emailAddress', 'shipToFirstName',
		'shipToLastName', 'shipToCompany', 'shipToAddress', 'shipToCity', 'shipToState', 'shipToZipCode', 'shipToCountry', 'tax', 'duty',
		'freight', 'taxExempt', 'purchaseOrderNumber', 'md5Hash', 'cardCodeResponse', 'cardholderAuthenticationVerificationResponse',
		'splitTenderId', 'requestedAmount', 'balanceOnCard', 'accountNumber', 'cardType');
	
	// just save whatever gets passed in
	public function __construct($api_login_id, $transaction_key, $test_mode = false, $debug_mode = false)
	{
		$this->api_login_id = $api_login_id;
		$this->transaction_key = $transaction_key;
		$this->test_mode = $test_mode;
		$this->debug_mode = $debug_mode;
		$this->direct_response_separator = '|';
	}
	
	// this gets called for every method
	public function __call($name, $arguments)
	{
		// suppress warnings about the namespace
		$xml = @new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><' . $name . ' xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"></' . $name . '>');
		
		// add merchant authentication
		$xml->merchantAuthentication->name = $this->api_login_id;
		$xml->merchantAuthentication->transactionKey = $this->transaction_key;
		
		// add parameters
		$this->addParams($xml, $arguments[0]);
		
		// return communication result
		return $this->communicate($xml);
	}
	
	// recursively add values from $array to $xml
	//   used to add the array values to the xml object
	private function addParams($xml, $array)
	{
		foreach ($array as $param => $value) {
			if (is_array($value)) {
				$xml->addChild($param);
				$this->addParams($xml->$param, $value);
			} else {
				$xml->$param = $value;
			}
		}
	}
	
	// send the xml object to authorize.net and return the resulting xml object
	private function communicate($xml)
	{
		// determine proper url
		if ($this->test_mode) {
			$url = 'https://apitest.authorize.net/xml/v1/request.api';
		} else {
			$url = 'https://api.authorize.net/xml/v1/request.api';
		}
		
		// get xml string from object
		$xml = $xml->asXML();
		
		// debug
		$this->debug('about to send "xml" to "url"', $xml, $url);
		
		// do request via curl
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		$response = curl_exec($ch);
		
		// check for curl error
		if ($response === false) {
			throw new Exception('curl had a problem: ' . curl_error($ch));
		}
		
		// close curl handle
		curl_close($ch);
		
		// suppress warnings about the namespace
		$xml = @new SimpleXMLElement($response);
		
		// check for simplexml error
		if ($xml === false) {
			throw new Exception('could not parse returned xml: ' . $response);
		}
		
		// make the result code easier to get to
		$xml->ok = ($xml->messages->resultCode == 'Ok');
		
		// look for a directResponse to parse
		if (isset($xml->directResponse)) {
			$this->parseDirectResponse($xml);
		}
		
		// debug
		$this->debug('"plain" response and "xml" response', $response, $xml);
		
		// return xml object
		return $xml;
	}
	
	// use the static response_fields array to make directResponse easier to get to
	private function parseDirectResponse($xml)
	{
		$input = explode($this->direct_response_separator, (string) $xml->directResponse);
		foreach (self::$response_fields as $key => $name) {
			$xml->response->$name = $input[$key];
		}
	}
	
	// vardump all the args passed in if in debug mode
	private function debug()
	{
		if ($this->debug_mode) {
			echo "\n\n[DEBUG]\n";
			var_dump(func_get_args());
			echo "[/DEBUG]\n\n";
		}
	}
}


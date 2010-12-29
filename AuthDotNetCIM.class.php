<?php

/**
 * PHP 5 class to assist with Authorize.net Customer Information Manager (CIM)
 *
 * Requires cURL and SimpleXML extensions in PHP 5
 *
 * Version 0.4 on 29 Dec 2010
 * By Chris Blay (chris@meosphere.com, chris.b.blay@gmail.com)
 * Copyright (c) 2010 Meosphere (http://meosphere.com, http://meolabs.com)
 *
 * License: http://www.gnu.org/licenses/lgpl-3.0.txt
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
	private $debug_mode;
	private $direct_response_separator;
	
	// the two urls for testing and non-testing
	private static $testing_url =
		'https://apitest.authorize.net/xml/v1/request.api';
	private static $normal_url =
		'https://api.authorize.net/xml/v1/request.api';
	
	// used to parse directResponse in transaction results
	private static $response_fields = array('responseCode', 'responseSubcode',
		'responseReasonCode', 'responseReasonText', 'authorizationCode',
		'avsResponse', 'transactionId', 'invoiceNumber', 'description',
		'amount', 'method', 'transactionType', 'customerId', 'firstName',
		'lastName', 'company', 'address', 'city', 'state', 'zipCode',
		'country', 'phone', 'fax', 'emailAddress', 'shipToFirstName',
		'shipToLastName', 'shipToCompany', 'shipToAddress', 'shipToCity',
		'shipToState', 'shipToZipCode', 'shipToCountry', 'tax', 'duty',
		'freight', 'taxExempt', 'purchaseOrderNumber', 'md5Hash',
		'cardCodeResponse', 'cardholderAuthenticationVerificationResponse',
		'splitTenderId', 'requestedAmount', 'balanceOnCard', 'accountNumber',
		'cardType');
	
	// the xml namespace used by the CIM api
	private static $xmlns = 'AnetApi/xml/v1/schema/AnetApiSchema.xsd';
	
	// save and validate whatever gets passed in
	public function __construct($login_id, $trans_key, array $opts = array())
	{
		// check for curl
		if (!function_exists('curl_init')) {
			throw new Exception('curl is not installed');
		}
		
		// check for simplexml
		if (!function_exists('simplexml_load_string')) {
			throw new Exception('simplexml is not installed');
		}
		
		// save data for this instance
		$this->api_login_id = (string) $login_id;
		$this->transaction_key = (string) $trans_key;
		$this->test_mode =
			isset($opts['test_mode']) ?
				(bool) $opts['test_mode']
			:
				false;
		$this->debug_mode =
			isset($opts['debug_mode']) ?
				(bool) $opts['debug_mode']
			:
				false;
		$this->direct_response_separator =
			isset($opts['direct_response_separator']) ?
				(string) $opts['direct_response_separator']
			:
				'|';
		
		// validate data for this instance
		if (strlen($this->api_login_id) === 0) {
			throw new Exception('api_login_id is empty');
		}
		if (strlen($this->transaction_key) === 0) {
			throw new Exception('transaction_key is empty');
		}
		if (strlen($this->direct_response_separator) !== 1) {
			throw new Exception('direct_response_separator not one char long');
		}
	}
	
	// this gets called for every method
	public function __call($name, $arguments)
	{
		// add 'Request' to end of name if it isn't there
		if (substr($name, -7) !== 'Request') {
			$name = $name . 'Request';
		}
		
		// suppress warnings about the namespace
		$xml = @new SimpleXMLElement(
			'<?xml version="1.0" encoding="utf-8"?>'.
			'<' . $name . ' xmlns="' . self::$xmlns . '">'.
			'</' . $name . '>');
		
		// add merchant authentication
		$xml->merchantAuthentication->name = $this->api_login_id;
		$xml->merchantAuthentication->transactionKey = $this->transaction_key;
		
		// add parameters
		$this->addParams($xml, $arguments[0]);
		
		// determine proper url
		if ($this->test_mode) {
			$url = self::$testing_url;
		} else {
			$url = self::$normal_url;
		}
		
		// get xml string from object
		$xml = $xml->asXML();
		
		$this->debug('about to send "xml" to "url"', $xml, $url);
		
		// do request via curl
		$response = $this->curlRequest($url, $xml);
		
		// suppress warnings about the namespace
		$xml = @new SimpleXMLElement($response);
		
		// check for simplexml error
		if ($xml === false) {
			throw new Exception('could not parse returned xml: ' . $response);
		}
		
		// make the result code easier to get to
		if ($xml->messages->resultCode == 'Ok') {
			$xml->ok = '';
		}
		
		// look for a directResponse to parse
		if (isset($xml->directResponse)) {
			$this->parseDirectResponse($xml);
		}
		
		$this->debug('"plain" response and "xml" response', $response, $xml);
		
		// return xml object
		return $xml;
	}
	
	// perform a curl request with given url and postfields
	// return the response
	private function curlRequest($url, $postfields)
	{
		// create curl handle and set options
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		$response = curl_exec($ch);
		
		// check for curl error
		if ($response === false) {
			throw new Exception('curl had a problem: ' . curl_error($ch));
		}
		
		// close curl handle
		curl_close($ch);
		
		// return response
		return $response;
	}
	
	// recursively add values from $array to $xml
	// used to add the array values to the xml object
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
	
	// use the static response_fields array to make directResponse easier to get to
	private function parseDirectResponse($xml)
	{
		$input = explode(
			$this->direct_response_separator,
			(string) $xml->directResponse);
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


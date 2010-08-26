<?php

/**
 * Example for PHP 5 class to assist with Authorize.net Customer Information Manager (CIM)
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


require_once('AuthDotNetCIM.class.php');


// instantiate the class in test mode

# TODO you'll need to change 'api_auth_id' and 'transaction_key'

$cim = new AuthDotNetCIM('api_auth_id', 'transaction_key', true);


// just call the method you want and pass in an array
//   with your parameters

// see http://www.authorize.net/support/CIM_XML_guide.pdf
//   for information about what methods are available and
//   what parameters are required/accepted

// a SimpleXMLElement object is returned that has shortcuts
//   for the result code and directResponse values along
//   with everything else sent back. it can be inspected
//   via debug mode

// if an error occured then a standard exception is thrown
//   with as seen below

// you can change debug_mode and direct_response_separator
//   via their respective public properties


try {
	
	// test creating customer profile
	echo "Attempting to create a customer profile...\n";
	
	$result = $cim->createCustomerProfileRequest(array(
		'profile' => array(
			'merchantCustomerId' => rand(1000000, 100000000),
			'paymentProfiles' => array(
				'billTo' => array(
					'firstName' => 'John',
					'lastName' => 'Doe',
					'address' => '1234 Street',
					'city' => 'Seattle',
					'state' => 'WA',
					'zip' => '98101',
				),
				'payment' => array(
					'creditCard' => array(
						'cardNumber' => '4111111111111111',
						'expirationDate' => '2025-01',
					),
				),
			),
		),
	));
	
	if ($result->ok) {
		echo "Created customer profile {$result->customerProfileId}\n\n";
	} else {
		echo "Error creating customer profile  :(\n";
		var_dump($result);
		die("\n");
	}
	
	$customerProfileId = (string) $result->customerProfileId;
	
	
	// test getting customer profile id
	echo "Attempting to get customer profile...\n";
	
	$result = $cim->getCustomerProfileRequest(array(
		'customerProfileId' => $customerProfileId,
	));
	
	if ($result->ok) {
		echo "Got customer profile $customerProfileId\n\n";
	} else {
		echo "Error getting customer profile $customerProfileId\n";
		var_dump($result);
		die("\n");
	}
	
	$customerPaymentProfileId = (string) $result->profile->paymentProfiles->customerPaymentProfileId;
	
	
	// test customer profile transaction
	//   notice that the class automatically parses the directResponse property
	//   into a more manageable 'response' property
	echo "Attempting to create transaction...\n";
	
	$result = $cim->createCustomerProfileTransactionRequest(array(
		'transaction' => array(
			'profileTransAuthOnly' => array(
				'amount' => '0.01',
				'customerProfileId' => $customerProfileId,
				'customerPaymentProfileId' => $customerPaymentProfileId,
			),
		),
	));
	
	if ($result->ok) {
		echo "Created customer profile transaction {$result->response->transactionId}\n\n";
	} else {
		echo "Error creating customer profile transaction\n";
		var_dump($result);
		die("\n");
	}
	
	
	// test deleting customer profile
	echo "Attempting to delete customer profile...\n";
	
	$result = $cim->deleteCustomerProfileRequest(array(
		'customerProfileId' => $customerProfileId,
	));
	
	if ($result->ok) {
		echo "Deleted customer profile $customerProfileId\n\n";
	} else {
		echo "Error deleting customer profile $customerProfileId\n";
		var_dump($result);
		die("\n");
	}
	
} catch (Exception $ex) {
	echo $ex->getMessage() . "\n\n";
}


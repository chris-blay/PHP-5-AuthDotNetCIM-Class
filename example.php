<?php

/**
 * Examples for PHP 5 class to assist with Authorize.net Customer Information Manager (CIM)
 *
 * Requires cURL and SimpleXML extensions in PHP 5
 *
 * Version 0.1 on 24 Aug 2010
 * By Chris Blay (chris@meosphere.com)
 * Copyright (c) 2010 Meosphere (http://meosphere.com, http://meolabs.com)
 *
 * License: http://www.gnu.org/licenses/lgpl-3.0.txt GNU Lesser General Public License (LGPL)
 *
 * Please keep this header information here
 *
 */


require_once('AuthDotNetCIM.class.php');


// instantiate the class in test and debug mode

# TODO you'll need to change 'api_auth_id' and 'transaction_key'

$cim = new AuthDotNetCIM('api_auth_id', 'transaction_key', true, true);


// just call the method you want and pass in an array
//   with your parameters

// see http://www.authorize.net/support/CIM_XML_guide.pdf
//   for information about what methods are available and
//   what parameters are required/accepted

// a SimpleXMLElement object is returned that has a
//   status message and all other information returned. it
//   can be inspected via debug mode or by other means

// if an error occured then the boolean value false will
//   be returned and more information can be found
//   in the public property $error

# TODO you'll need to fill in these values

$cim->createCustomerProfileRequest(array(
	'refId' => '',
	'profile' => array(
		'merchantCustomerId' => '',
		'description' => '',
		'email' => '',
		'paymentProfiles' => array(
			'customerType' => '',
			'billTo' => array(
				'firstName' => '',
				'lastName' => '',
				'company' => '',
				'address' => '',
				'city' => '',
				'state' => '',
				'zip' => '',
				'country' => '',
				'phoneNumber' => '',
				'faxNumber' => '',
			),
		),
	),
	# it's so easy!
));



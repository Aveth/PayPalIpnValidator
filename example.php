<?php

include('path/to/PayPalIpnValidator.php');  //or use an autoloader

$ipn = new PayPalIpnValidator(true);  //instantiate the object in sandbox mode

//set a callback...prior to PHP 5.3, you will need to use 'create_function()'
$ipn->set_callback('VERIFIED', function($post) {
	//do something here...
});

$ipn->set_callback('MISMATCH', function($post) {
	//do something here...
});

$ipn->set_callback('Completed', function($post) {
	//do something here...
});

$ipn->set_callback('INVALID', function($post) {
	//do something here...
});


//validation parameters...you can validate any item in the $_POST array by passing a matching key here
$params = array(
	'receiver_email' => 'mypaypalemail@example.com',  //to validate your email
	'mc_gross' => '100.00'  //to validate the amount
);

try {
	$ipn->validate($params);
} catch (Exception $e) {
	//some error handling code...
}

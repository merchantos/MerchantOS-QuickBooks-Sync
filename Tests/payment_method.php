<?php

echo "For testing only";
exit;

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

$ianywhere = new IntuitAnywhere($qb_sess_access);	
$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization

require_once("IntuitAnywhere/PaymentMethod.class.php");
$ia_payment_method = new IntuitAnywhere_PaymentMethod($ianywhere);

// Get a list of matching payment methods

$payment_method_name = "Test Method " . time();

$filters = array('Name'=>$payment_method_name);
	
$payment_methods = $ia_payment_method->listAll($filters);

if (count($payment_methods)>0)
{
	var_dump($payment_methods);
	var_dump("Should have been empty.");
}

$ia_payment_method->Name = $payment_method_name;
$ia_payment_method->save();

if (!isset($ia_payment_method->Id))
{
	var_dump($ia_payment_method);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}

$payment_methods = $ia_payment_method->listAll($filters);

if (count($payment_methods)!=1)
{
	var_dump($payment_methods);
	var_dump("Should have one result but has more or less.");
}

if ($payment_methods[0]->Id != $ia_payment_method->Id)
{
	var_dump($payment_methods[0]->Id);
	var_dump($ia_payment_method->Id);
	var_dump("Should have been equal.");
}

echo "Done.";

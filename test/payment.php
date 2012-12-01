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
require_once("IntuitAnywhere/Customer.class.php");
require_once("IntuitAnywhere/Payment.class.php");
$ia_payment_method = new IntuitAnywhere_PaymentMethod($ianywhere);
$ia_customer = new IntuitAnywhere_Customer($ianywhere);
$ia_payment = new IntuitAnywhere_Payment($ianywhere);

// Get a list of matching payment methods

$payment_method_name = "Test Method " . time();
$customer_name = "Test Customer " . time();

$ia_payment_method->Name = $payment_method_name;
$ia_payment_method->save();

$ia_customer->Name = $customer_name;
$ia_customer->save();

if (!isset($ia_payment_method->Id))
{
	var_dump($ia_payment_method);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}
if (!isset($ia_customer->Id))
{
	var_dump($ia_customer);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}

$ia_payment->HeaderTxnDate = new DateTime("2012-01-01");
$ia_payment->HeaderNote = "Test Payment";
$ia_payment->HeaderCustomerId = $ia_customer->Id;
$ia_payment->HeaderPaymentMethodId = $ia_payment_method->Id;
$ia_payment->HeaderTotalAmt = 1.00;
$ia_payment->save();

$filters = array('CustomerId'=>$ia_customer->Id);

$payments = $ia_payment->listAll($filters);

if (count($payments)!=1)
{
	var_dump($payments);
	var_dump("Should have one result but has more or less.");
}

if ($payments[0]->Id != $ia_payment->Id)
{
	var_dump($payments[0]->Id);
	var_dump($ia_payment->Id);
	var_dump("Should have been equal.");
}

echo "Done.";

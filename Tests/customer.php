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

require_once("IntuitAnywhere/Customer.class.php");
$ia_customer = new IntuitAnywhere_Customer($ianywhere);

// Get a list of matching customers

$customer_name = "Test Customer " . time();

$filters = array('Name'=>$customer_name);
	
$customers = $ia_customer->listAll($filters);

if (count($customers)>0)
{
	var_dump($customers);
	var_dump("Should have been empty.");
}

$ia_customer->Name = $customer_name;
$ia_customer->save();

if (!isset($ia_customer->Id))
{
	var_dump($ia_customer);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}

$customers = $ia_customer->listAll($filters);

if (count($customers)!=1)
{
	var_dump($customers);
	var_dump("Should have one result but has more or less.");
}

if ($customers[0]->Id != $ia_customer->Id)
{
	var_dump($customers[0]->Id);
	var_dump($ia_customer->Id);
	var_dump("Should have been equal.");
}

echo "Done.";

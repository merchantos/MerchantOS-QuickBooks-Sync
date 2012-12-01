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

require_once("IntuitAnywhere/Vendor.class.php");
$ia_vendor = new IntuitAnywhere_Vendor($ianywhere);

// Get a list of matching vendors

$vendor_name = "Test Vendor " . time();

$filters = array('Name'=>$vendor_name);
	
$vendors = $ia_vendor->listAll($filters);

if (count($vendors)>0)
{
	var_dump($vendors);
	var_dump("Should have been empty.");
}

$ia_vendor->Name = $vendor_name;
$ia_vendor->save();

if (!isset($ia_vendor->Id))
{
	var_dump($ia_vendor);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}

$vendors = $ia_vendor->listAll($filters);

if (count($vendors)!=1)
{
	var_dump($vendors);
	var_dump("Should have one result but has more or less.");
}

if ($vendors[0]->Id != $ia_vendor->Id)
{
	var_dump($vendors[0]->Id);
	var_dump($ia_vendor->Id);
	var_dump("Should have been equal.");
}

echo "Done.";

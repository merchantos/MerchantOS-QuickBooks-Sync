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

require_once("IntuitAnywhere/Bill.class.php");
require_once("IntuitAnywhere/Vendor.class.php");
require_once("IntuitAnywhere/Account.class.php");
require_once("IntuitAnywhere/Class.class.php");
$ia_bill = new IntuitAnywhere_Bill($ianywhere);
$ia_vendor = new IntuitAnywhere_Vendor($ianywhere);
$ia_account = new IntuitAnywhere_Vendor($ianywhere);
$ia_class = new IntuitAnywhere_Class($ianywhere);

// Get a list of matching payment methods

$bill_docnum = "mos" . time();
$vendor_name = "Test Vendor " . time();
$account_name = "Test Account " . time();
$class_name = "Test Class " . time();

$ia_vendor->Name = $vendor_name;
$ia_vendor->save();

$ia_account->Name = $account_name;
$ia_account->Subtype = "Inventory";
$ia_account->save();

$ia_class->Name = $class_name;
$ia_class->save();

if (!isset($ia_vendor->Id))
{
	var_dump($ia_vendor);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}
if (!isset($ia_account->Id))
{
	var_dump($ia_account);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}
if (!isset($ia_class->Id))
{
	var_dump($ia_class);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}

$ia_bill->HeaderTxnDate = new DateTime("2012-01-01");
$ia_bill->HeaderMsg = "Test Bill Msg";
$ia_bill->HeaderVendorId = $ia_vendor->Id;
$ia_bill->HeaderDocNumber = $bill_docnum;

$ia_bill_line = new IntuitAnywhere_BillLine($ianywhere);
$ia_bill_line->Desc = "Test Bill Line";
$ia_bill_line->Amount = 1.00;
$ia_bill_line->ClassId = $ia_class->Id;
$ia_bill_line->AccountId = $ia_account->Id;

$ia_bill->Lines = array($ia_bill_line);

$ia_bill->save();

if (!isset($ia_bill->Id))
{
	var_dump($ia_bill);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}

$filters = array('DocNumber'=>$bill_docnum);

$bills = $ia_bill->listAll($filters);

if (count($bills)!=1)
{
	var_dump($bills);
	var_dump("Should have one result but has more or less.");
}

if ($bills[0]->Id != $ia_bill->Id)
{
	var_dump($payments[0]->Id);
	var_dump($ia_bill->Id);
	var_dump("Should have been equal.");
}

if ($bills[0]->HeaderMsg != "Test Bill Msg" ||
	$bills[0]->HeaderVendorId != $ia_vendor->Id ||
	$bills[0]->HeaderDocNumber != $bill_docnum ||
	count($bills[0]->Lines) != 1 ||
	$bills[0]->Lines[0]->Desc != "Test Bill Line" ||
	$bills[0]->Lines[0]->Amount != 1.00 ||
	$bills[0]->Lines[0]->ClassId != $ia_class->Id ||
	$bills[0]->Lines[0]->AccountId != $ia_account->Id)
{
	var_dump($ia_bill);
	var_dump("Has unexpexted values.");
}

echo "Done.";

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

require_once("IntuitAnywhere/Account.class.php");
$ia_account = new IntuitAnywhere_Account($ianywhere);

// First get the Inventory Assets top level account to put accounts under
$inv_filters = array('Name'=>'Inventory Asset');
$accounts = $ia_account->listAll($inv_filters);
if (count($accounts)==0)
{
	var_dump($accounts);
	var_dump("Should not have been empty.");
}
$inv_account = $accounts[0];

// Get a list of matching accounts
$account_name = "Test Account " . time();

$filters = array('Name'=>$account_name,'AccountParentId'=>$inv_account->Id);
	
$accounts = $ia_account->listAll($filters);

if (count($accounts)>0)
{
	var_dump($accounts);
	var_dump("Should have been empty.");
}

// create an account

$ia_account->Name = $account_name;
$ia_account->Subtype = "Inventory";
$ia_account->AccountParentId = $inv_account->Id;
$ia_account->save();

if (!isset($ia_account->Id))
{
	var_dump($ia_account);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}

// find the account we created

$accounts = $ia_account->listAll($filters);

if (count($accounts)!=1)
{
	var_dump($accounts);
	var_dump("Should have one result but has more or less.");
}

if ($accounts[0]->Id != $ia_account->Id)
{
	var_dump($accounts[0]->Id);
	var_dump($ia_account->Id);
	var_dump("Should have been equal.");
}

// create a sub account
$subaccount_name = "Test Subaccount " . time();
$ia_subaccount = new IntuitAnywhere_Account($ianywhere);
$ia_subaccount->Name = $subaccount_name;
$ia_subaccount->Subtype = "Inventory";
$ia_subaccount->AccountParentId = $ia_account->Id;
$ia_subaccount->save();

if (!isset($ia_subaccount->Id))
{
	var_dump($ia_subaccount);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}

// find the sub account

$sub_filters = array(
	'Name'=>$subaccount_name,
	'AccountParentId'=>$ia_account->Id
);
$sub_accounts = $ia_account->listAll($sub_filters);

if (count($sub_accounts)!=1)
{
	var_dump($sub_accounts);
	var_dump("Should have one result but has more or less.");
}

if ($sub_accounts[0]->Id != $ia_subaccount->Id)
{
	var_dump($sub_accounts[0]->Id);
	var_dump($ia_subaccount->Id);
	var_dump("Should have been equal.");
}

// delete as fresh object to test loading
// first the sub account
$ia_to_delete = new IntuitAnywhere_Account($ianywhere);
$ia_to_delete->Id = $ia_subaccount->Id;
$ia_to_delete->delete();

// then the parent account
$ia_to_delete = new IntuitAnywhere_Account($ianywhere);
$ia_to_delete->Id = $ia_account->Id;
$ia_to_delete->delete();

// make sure they were deleted
$sub_accounts = $ia_account->listAll($sub_filters);
$accounts = $ia_account->listAll($filters);

if (count($sub_accounts)!=0)
{
	var_dump($sub_accounts);
	var_dump("Deleted account still existed.");
}
if (count($accounts)!=0)
{
	var_dump($accounts);
	var_dump("Deleted account still existed.");
}

echo "Done.";

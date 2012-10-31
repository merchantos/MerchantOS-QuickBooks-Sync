<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

header("Content-Type: application/json");

function returnOutput($output)
{
	if (isset($_GET['callback']))
	{
		return $_GET['callback'] . "(" . $output . ");";
	}
	return $output;
}

$accounts_json = false;

$accounts_json = $qb_sess_access->getCache("accounts_json",600);
if (!$accounts_json)
{
	$ianywhere = new IntuitAnywhere($qb_sess_access);	
	$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization
	
	// Get a list of accounts
	require_once("IntuitAnywhere/Account.class.php");
	$ia_account = new IntuitAnywhere_Account($ianywhere);
	
	$filters = array();
	if (isset($_GET['AccountParentId']))
	{
		$filters['AccountParentId'] =  $_GET['AccountParentId'];
	}
	
	$accounts = $ia_account->listAll($filters);
	
	$accounts_json = array();
	
	foreach ($accounts as $account)
	{
		$accounts_json[] = "{\"Id\":\"" . $account->Id . "\",\"Name\":\"" . $account->Name . "\",\"Subtype\":\"" . $account->Subtype . "\",\"AccountParentId\":\"" . $account->AccountParentId . "\"}";
	}
	
	$qb_sess_access->storeCache("accounts_json",$accounts_json);
}
echo returnOutput("[" . join(",",$accounts_json) . "]");

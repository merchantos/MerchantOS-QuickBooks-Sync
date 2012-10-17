<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("../lib/SessionAccess.class.php");

require_once("../oauth/library/OAuthStore.php");
require_once("../oauth/library/OAuthRequester.php");

require_once("../IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

header("Content-Type: application/json");

function returnOutput($output)
{
	if ($_GET['callback'])
	{
		return $_GET['callback'] . "(" . $output . ");";
	}
	return $output;
}

try
{
	$ianywhere = new IntuitAnywhere($qb_sess_access);
	$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization
	
	// Get a list of accounts
	require_once("../IntuitAnywhere/Account.class.php");
	$ia_account = new IntuitAnywhere_Account($ianywhere);
	
	$filters = array();
	if ($_GET['AccountParentId'])
	{
		$filters['AccountParentId'] =  $_GET['AccountParentId'];
	}
	
	$accounts = $ia_account->listAll($filters);
	
	$accounts_json = array();
	
	foreach ($accounts as $account)
	{
		$accounts_json[] = "{\"Id\":\"" . $account->Id . "\",\"Name\":\"" . $account->Name . "\",\"Subtype\":\"" . $account->Subtype . "\",\"AccountParentId\":\"" . $account->AccountParentId . "\"}";
	}
	echo returnOutput("[" . join(",",$accounts_json) . "]");
}
catch (Exception $e)
{
	echo returnOutput("{error:'" . $e->Message() . " (" . $e->getCode() . ")'}");
	exit;
}

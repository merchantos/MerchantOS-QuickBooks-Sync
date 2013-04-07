<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("Sync/DeleteQuickBooks.class.php");
require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$login_sess_access = new SessionAccess("login");
$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

$ianywhere = new IntuitAnywhere($qb_sess_access);	
$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization

header("Content-Type: application/json");

function returnOutput($output)
{
	if (isset($_GET['callback']))
	{
		return $_GET['callback'] . "(" . $output . ");";
	}
	return $output;
}

global $_sync_database;
if (!isset($_sync_database))
{
	require_once("Sync/Database.class.php");
	$_sync_database = new Sync_Database();
}

if (!isset($_GET['type']) || !isset($_GET['id']))
{
	throw new Exception("Can not delete object, 'type' or 'id' parameters not set.");
}

$sync_delete = new Sync_DeleteQuickBooks($ianywhere,$_sync_database);
$sync_delete->deleteObject($login_sess_access->account_id,$_GET['type'],$_GET['id']);

echo returnOutput("{\"success\":true}");

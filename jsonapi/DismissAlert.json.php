<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

$login_sess_access = new SessionAccess("login");

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

if (!isset($_GET['account_log_id']))
{
	throw new Exception("Can not dismiss alert, 'account_log_id' parameter not set.");
}

$_sync_database->dismissAlert($login_sess_access->account_id,$_GET['account_log_id']);

echo returnOutput("{\"success\":true}");

<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("Sync/Sync.class.php");
require_once("Sync/Database.class.php");

$login_sess_access = new SessionAccess("login");

try
{
	global $_sync_database;
	if (!isset($_sync_database))
	{
		require_once("Sync/Database.class.php");
		$_sync_database = new Sync_Database();
	}

	$sync_runner = new Sync_SyncRunner($_sync_database,$login_sess_access->account_id);
	$sync_runner->initFromSession();
	
	$date = (isset($_GET['date'])?$_GET['date']:null);
	$type = (isset($_GET['type'])?$_GET['type']:'all');
	
	$sync_runner->run($date,$type);
}
catch (Exception $e)
{
	echo returnOutput("{\"error\":\"" . $e->getMessage() . "\"}");
	exit;
}

echo returnOutput("{\"success\":true}");

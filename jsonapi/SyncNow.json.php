<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("sync/Sync.class.php");

$login_sess_access = new SessionAccess("login");

try
{
	$sync_runner = new Sync_SyncRunner($login_sess_access->account_id);
	$sync_runner->initFromSession();
	$sync_runner->run($_GET['date'],$_GET['type'],$_GET['resync_account_log_id']);
}
catch (Exception $e)
{
	echo returnOutput("{\"error\":\"" . $e->getMessage() . "\"}");
	exit;
}

echo returnOutput("{\"success\":true}");

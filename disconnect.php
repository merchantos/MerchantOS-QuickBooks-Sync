<?php

include_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");
$login_sess_access = new SessionAccess("login");
$setup_sess_access = new SessionAccess("setup");

$ianywhere = new IntuitAnywhere($qb_sess_access);	
$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization
$ianywhere->disconnect();

global $_sync_database;
if (!isset($_sync_database))
{
	require_once("Sync/Database.class.php");
	$_sync_database = new Sync_Database();
}

$_sync_database->deleteOAuth($login_sess_access->account_id);
$_sync_database->deleteSyncSetup($login_sess_access->account_id);

$oauth_sess_access->clear();
$qb_sess_access->clear();
$setup_sess_access->clear();

header("location: ./?disconnected=1");

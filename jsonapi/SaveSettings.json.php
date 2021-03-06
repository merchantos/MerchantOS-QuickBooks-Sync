<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

$login_sess_access = new SessionAccess("login");
$setup_sess_access = new SessionAccess("setup");

header("Content-Type: application/json");

function returnOutput($output)
{
	if (isset($_GET['callback']))
	{
		return $_GET['callback'] . "(" . $output . ");";
	}
	return $output;
}

$old_settings = $setup_sess_access->getArray();
foreach ($old_settings as $old_setting_name=>$old_setting_value)
{
	if (!isset($_POST[$old_setting_name]))
	{
		unset($setup_sess_access->{$old_setting_name});
	}
}
foreach ($_POST as $setting_name=>$setting_value)
{
	if ($setting_value == "0") {
		unset($setup_sess_access->{$setting_name});
		continue;
	}
	$setup_sess_access->{$setting_name} = $setting_value;
}

global $_sync_database;
if (!isset($_sync_database))
{
	require_once("Sync/Database.class.php");
	$_sync_database = new Sync_Database();
}
$_sync_database->writeSyncSetup($login_sess_access->account_id,$setup_sess_access->getArray());

echo returnOutput("{\"success\":true}");

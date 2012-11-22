<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("database.inc.php");

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

mosqb_database::dismissAlert($login_sess_access->account_id,$_GET['account_log_id']);

echo returnOutput("{\"success\":true}");
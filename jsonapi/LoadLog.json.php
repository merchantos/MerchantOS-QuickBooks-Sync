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

$page = 1;
if (isset($_GET['page']))
{
	$page = (integer)$_GET['page'];
}
$start_time = time() - (60*60*24*30*$page); // one month (60*60*24*30) per page
$end_time = time();

$log_msgs = mosqb_database::readAccountLog($login_sess_access->account_id,$start_time,$end_time);
if (!$log_msgs)
{
	$log_msgs = array();
}

$log_json = array();
foreach ($log_msgs as $insert_time=>$log_msg)
{
	$date_json = "\"".$log_msg['date']."\"";
	$msg_json = "\"".$log_msg['msg']."\"";
	$imported = "false";
	if ($log_msg['imported']) {
		$imported = "true";
	}
	$insert_time = $log_msg['insert_time'];
	$log_json[] = "{\"date\":$date_json,\"msg\":$msg_json,\"imported\":$imported,\"insert_time\":$insert_time}";
}

echo returnOutput("[" . join(",",$log_json) . "]");

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

$page = 1;
if (isset($_GET['page']))
{
	$page = (integer)$_GET['page'];
}
$limit = 16;
$offset = ($page-1)*$limit;

$alerts=false;
if (isset($_GET['alerts']) && $_GET['alerts']==1)
{
	$alerts = true;
}

$type = 'all';
if (isset($_GET['type']))
{
	$TYPE = $_GET['type'];
}

global $_sync_database;
if (!isset($_sync_database))
{
	require_once("Sync/Database.class.php");
	$_sync_database = new Sync_Database();
}

$log_msgs = $_sync_database->readAccountLog($type,$login_sess_access->account_id,$offset,$limit,$alerts);
if (!$log_msgs)
{
	$log_msgs = array();
}

$log_json = array();
foreach ($log_msgs as $insert_time=>$log_msg)
{
	$account_log_id_json = "\"".$log_msg['account_log_id']."\"";
	$date_json = "\"".$log_msg['data_date']."\"";
	$msg_json = "\"".$log_msg['msg']."\"";
	$type_json = "\"".$log_msg['type']."\"";
	$success = "false";
	if ($log_msg['success']) {
		$success = "true";
	}
	$insert_time = $log_msg['insert_time'];
	$log_json[] = "{\"date\":$date_json,\"msg\":$msg_json,\"success\":$success,\"insert_time\":$insert_time,\"account_log_id\":$account_log_id_json,\"type\":$type_json}";
}

$count = count($log_json);
if ($count==16)
{
	array_pop($log_json);
}

echo returnOutput("{\"page\":$page,\"count\":$count,\"log\":[" . join(",",$log_json) . "]}");

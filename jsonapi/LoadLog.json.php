<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.php");

$log_sess_access = new SessionAccess("log");

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
	$log_msgs = $log_sess_access->log;
	if (!$log_msgs)
	{
		$log_msgs = array();
	}
	
	$log_json = array();
	foreach ($log_msgs as $log_msg)
	{
		$date_json = "\"".$log_msg['date']."\"";
		$msg_json = "\"".$log_msg['msg']."\"";
		$log_json[] = "{\"date\":$date_json,\"msg\":$msg_json}";
	}
	
	echo returnOutput("[" . join(",",$log_json) . "]");
}
catch (Exception $e)
{
	echo returnOutput("{error:'" . $e->Message() . " (" . $e->getCode() . ")'}");
	exit;
}
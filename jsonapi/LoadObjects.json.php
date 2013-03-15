<?php

/**
 * Loads a list of QuickBooks Objects that have been created
 */

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("Sync/Database.class.php");

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

$type = 'all';
if (isset($_GET['type']))
{
	$type = $_GET['type'];
}

$db = new Sync_Database();
$qb_objects = $db->readQBObjects($type,$login_sess_access->account_id,$offset,$limit);
if (!$qb_objects)
{
	$qb_objects = array();
}

$qb_objects_json = array();
foreach ($qb_objects as $qb_obj)
{
	$id_json = "\"".$qb_obj['id']."\"";
	$type_json = "\"".$qb_obj['type']."\"";
	$insert_time = $qb_obj['insert_time'];
	$qb_objects_json[] = "{\"id\":$id_json,\"insert_time\":$insert_time,\"type\":$type_json}";
}

$count = count($qb_objects_json);
if ($count==16)
{
	array_pop($qb_objects_json);
}

echo returnOutput("{\"page\":$page,\"count\":$count,\"objects\":[" . join(",",$qb_objects_json) . "]}");

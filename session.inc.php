<?php
require_once("lib/Session.class.php");
try
{
	global $_sync_database;
	if (!isset($_sync_database))
	{
		require_once("Sync/Database.class.php");
		$_sync_database = new Sync_Database();
	}
	
	$session_starter = new lib_Session($_sync_database);
	$session_starter->init();
}
catch (Exception $e)
{
	echo $e->getMessage();
	exit;
}

<?php

echo "For testing only";
exit;

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("database.inc.php");

$api_key = "test" . time();

$account_id = mosqb_database::writeAccount($api_key);

$account_id2 = mosqb_database::readAccount($api_key);

if ($account_id !== $account_id2)
{
	var_dump("Account IDs from write and then read did not match.");
}

mosqb_database::writeOAuth($account_id,array("test"=>"result","test2"=>"result2"));

$oauth_test = mosqb_database::readOAuth($account_id);

if (!$oauth_test || count($oauth_test)===0 || $oauth_test['test'] !== "result" || $oauth_test['test2'] !== "result2")
{
	var_dump("OAuth write then read did not match.");
}

mosqb_database::writeSyncSetup($account_id,array("name1"=>"value1","name2"=>"value2"));

$setup_test = mosqb_database::readSyncSetup($account_id);

if (!$setup_test || count($setup_test)===0 || $setup_test['name1']!=="value1" || $setup_test["name2"]!=="value2")
{
	var_dump("SyncSetup write then read did not match.");
}

mosqb_database::writeSyncSetup($account_id,array("name1"=>"value3","name2"=>"value4"));

$setup_test = mosqb_database::readSyncSetup($account_id);	

if (!$setup_test || count($setup_test)===0 || $setup_test['name1']!=="value3" || $setup_test["name2"]!=="value4")
{
	var_dump("SyncSetup update then read did not match.");
}

$today = new DateTime();

mosqb_database::writeAccountLogEntries($account_id,array(array("date"=>$today->format("m/d/Y"),"success"=>1,"alert"=>1,"msg"=>"value1.3")));

mosqb_database::writeAccountLogEntries($account_id,array(array("date"=>$today->format("m/d/Y"),"success"=>0,"alert"=>1,"msg"=>"value2.3")));

$test_log_entries = mosqb_database::readAccountLog($account_id);

if (!$test_log_entries || count($test_log_entries)!==2)
{
	var_dump("LogEntry write then read did not match.");
}
$entry_test = array_pop($test_log_entries);
if ($entry_test['data_date']!==$today->format("m/d/Y") || $entry_test['success']!== "0" || $entry_test['alert']!== "1" || $entry_test['msg']!=="value2.3")
{
	var_dump("LogEntry write then read did not match.");
}

if (!mosqb_database::hasSyncSuccessDurring($account_id,$today,$today))
{
	var_dump("No successful sync seen from ".$today->format("m/d/Y")." to ".$today->format("m/d/Y").".");
}

$future = new DateTime("+1 day");
if (mosqb_database::hasSyncSuccessDurring($account_id,$future,$future))
{
	var_dump("Successful sync seen from ".$future->format("m/d/Y")." to ".$future->format("m/d/Y").".");
}

echo "done";
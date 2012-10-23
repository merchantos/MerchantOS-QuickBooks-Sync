<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.php");

$setup_sess_access = new SessionAccess("setup");
$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");
$merchantos_sess_access = new SessionAccess("merchantos");
$log_sess_access = new SessionAccess("log");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

require_once("MerchantOS/Option.class.php");
require_once("MerchantOS/Accounting.class.php");

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
	$ianywhere = new IntuitAnywhere($qb_sess_access);	
	$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization
	
	$mos_option = new MerchantOS_Option($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
	
	$options = $mos_option->listAll();
	$average_costing = true;
	if (isset($options['cost_method']) && $options['cost_method']!="average")
	{
		$average_costing = false;
	}
	
	$send_sales = $setup_sess_access->send_sales;
	if ($send_sales == "on" || $send_sales == "On" || $send_sales->send_sales)
	{
		$send_sales = true;
	}
	else
	{
		$send_sales = false;
	}
	
	$send_inventory = $setup_sess_access->send_inventory;
	if ($send_inventory == "on" || $send_inventory == "On" || $send_inventory->send_sales)
	{
		$send_inventory = true;
	}
	else
	{
		$send_inventory = false;
	}
	
	$send_orders = $setup_sess_access->send_orders;
	if ($send_orders == "on" || $send_orders == "On" || $send_orders->send_sales)
	{
		$send_orders = true;
	}
	else
	{
		$send_orders = false;
	}
	
	$start_date = new DateTime($setup_sess_access->start_date);
	
	// data delay is an offset from todays date that DateTime knows how to translate
	$end_date = new DateTime($setup_sess_access->data_delay);
	
	require_once("Sync/MerchantOStoQuickBooks.class.php");
	
	$mos_accounting = new MerchantOS_Accounting($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
	
	$mosqb_sync = new Sync_MerchantOStoQuickBooks($mos_accounting,$ianywhere);
	
	$mosqb_sync->setAccountMapping(array(
		"sales"=>$setup_sess_access->sales,
		"discounts"=>$setup_sess_access->discounts,
		"tax"=>$setup_sess_access->tax,
		"payments"=>$setup_sess_access->payments,
		"credit_accounts"=>$setup_sess_access->credit_accounts,
		"gift_cards"=>$setup_sess_access->gift_cards,
		"cogs"=>$setup_sess_access->cogs,
		"inventory"=>$setup_sess_access->inventory,
		"orders"=>$setup_sess_access->orders,
		"orders_shipping"=>$setup_sess_access->orders_shipping,
		"orders_other"=>$setup_sess_access->orders_other
	));
	
	if (!$average_costing)
	{
		$mosqb_sync->setFIFOCosting();
	}
	
	if (!$send_sales)
	{
		$mosqb_sync->setNoSales();
	}
	
	if (!$send_inventory)
	{
		$mosqb_sync->setNoCOGS();
	}
	
	if (!$send_orders)
	{
		$mosqb_sync->setNoOrders();
	}
	
	$log = $mosqb_sync->sync($start_date,$end_date);
	
	$log_json = array();
	if (isset($log_sess_access->log))
	{
		$all_logs = $log_sess_access->log;
	}
	else
	{
		$all_logs = array();
	}
	foreach ($log as $logentry)
	{
		$all_logs[] = $logentry;
	}
	$log_sess_access->log = $all_logs;
	
	echo returnOutput("{\"success\":true}");
}	
catch(Exception $e)
{
	echo "Exception: " . $e->getMessage();
	var_dump($e);
}

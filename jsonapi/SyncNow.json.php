<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("database.inc.php");

$setup_sess_access = new SessionAccess("setup");
$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");
$merchantos_sess_access = new SessionAccess("merchantos");
$login_sess_access = new SessionAccess("login");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

require_once("MerchantOS/Option.class.php");
require_once("MerchantOS/Accounting.class.php");
require_once("MerchantOS/Shop.class.php");

require_once("Sync/MerchantOStoQuickBooks.class.php");


function returnOutput($output)
{
	if (isset($_GET['callback']))
	{
		return $_GET['callback'] . "(" . $output . ");";
	}
	return $output;
}

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
if ($send_sales == "on" || $send_sales == "On")
{
	$send_sales = true;
}
else
{
	$send_sales = false;
}

$send_inventory = $setup_sess_access->send_inventory;
if ($send_inventory == "on" || $send_inventory == "On")
{
	$send_inventory = true;
}
else
{
	$send_inventory = false;
}

$send_orders = $setup_sess_access->send_orders;
if ($send_orders == "on" || $send_orders == "On")
{
	$send_orders = true;
}
else
{
	$send_orders = false;
}

$type = "all";
if (isset($_GET['type']))
{
	$type = $_GET['type'];
}

$mos_accounting = new MerchantOS_Accounting($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
$mos_shop = new MerchantOS_Shop($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);

$mosqb_sync = new Sync_MerchantOStoQuickBooks($mos_accounting,$mos_shop,$ianywhere);

// shops to sync
foreach ($setup_sess_access->setup_shops as $shopID=>$onoff)
{
	if ($onoff === true || $onoff === 'on' || $onoff === 'On')
	{
		$mosqb_sync->setSyncShop($shopID);
	}
}

foreach ($setup_sess_access->setup_tax as $taxName=>$AccountId)
{
	$mosqb_sync->addTaxAccount($taxName,$AccountId);
}

$mosqb_sync->setAccountMapping(array(
	"sales"=>$setup_sess_access->sales,
	"discounts"=>$setup_sess_access->discounts,
	//"tax"=>$setup_sess_access->tax,
	//"payments"=>$setup_sess_access->payments,
	"accounts_receivable"=>$setup_sess_access->accounts_receivable,
	"credit_accounts"=>$setup_sess_access->credit_accounts,
	"gift_cards"=>$setup_sess_access->gift_cards,
	"cogs"=>$setup_sess_access->cogs,
	"inventory"=>$setup_sess_access->inventory,
	//"orders"=>$setup_sess_access->inventory,
	"orders_shipping"=>$setup_sess_access->orders_shipping,
	"orders_other"=>$setup_sess_access->orders_other
));

if (!$average_costing)
{
	$mosqb_sync->setFIFOCosting();
}

if (!$send_sales || ($type!='all' && $type!='sales'))
{
	$send_sales = false;
	$mosqb_sync->setNoSales();
}

if (!$send_inventory || ($type!='all' && $type!='cogs'))
{
	$send_inventory = false;
	$mosqb_sync->setNoCOGS();
}

if (!$send_orders || ($type!='all' && $type!='orders'))
{
	$send_orders = false;
	$mosqb_sync->setNoOrders();
}

// check sync settings
if (!$mosqb_sync->checkSyncSettings())
{
	// we can't process a date this recent, it's against our data delay setting
	echo returnOutput("{\"error\":\"QuickBooks or MerchantOS settings have changed, you need to check your SyncSettings.\"}");
	exit;
}

// sync sales
if ($send_sales || $send_inventory)
{
	$log_test_type = "sales";
	if ($type == 'cogs')
	{
		$log_test_type = "cogs";
	}
	
	$sales_start_date = new DateTime($setup_sess_access->start_date);
	
	// we need to get the last date synced and if start_date is <= then set it to that plus 1 day
	$last_success_date = mosqb_database::getLastSuccessfulDataDate($log_test_type,$login_sess_access->account_id);
	if ($last_success_date)
	{
		$sales_start_date = new DateTime($last_success_date->format('c') . ' + 1 day');
	}
	
	// data delay is an offset from todays date that DateTime knows how to translate
	$sales_end_date = new DateTime($setup_sess_access->data_delay);
	
	if (isset($_GET['date'])) {
		$one_date = new DateTime($_GET['date']);
		if ($one_date > $sales_end_date)
		{
			// we can't process a date this recent, it's against our data delay setting
			echo returnOutput("{\"error\":\"Sync date is beyond your data delay setting.\"}");
			exit;
		}
		$sales_start_date = $one_date;
		$sales_end_date = $one_date;
	}
	
	if (mosqb_database::hasSyncSuccessDurring($log_test_type,$login_sess_access->account_id,$sales_start_date,$sales_end_date))
	{
		echo returnOutput("{\"error\":\"Date range has already been synced.\"}");
		exit;
	}
	
	try
	{
		$sales_log = $mosqb_sync->syncSales($sales_start_date,$sales_end_date);
	}
	catch (Exception $e)
	{
		$sales_log = array(array("msg"=>"Error: " . $e->getMessage() . " Line: " . $e->getLine() . " File: " . $e->getFile(),"success"=>false,"alert"=>true,"type"=>"msg"));
	}
	
	if (count($sales_log)>0)
	{
		if (isset($_GET['resync_account_log_id']))
		{
			// delete the log entries for the previous sync of this day if we are doing a resync
			mosqb_database::deleteAccountLogEntry($login_sess_access->account_id,$_GET['resync_account_log_id']);
		}
		mosqb_database::writeAccountLogEntries($login_sess_access->account_id,$sales_log);
	}
}


// sync orders
if ($send_orders)
{
	$orders_start_date = new DateTime($setup_sess_access->start_date);
	
	// we need to get the last date synced and if start_date is <= then set it to that plus 1 day
	$last_success_date = mosqb_database::getLastSuccessfulDataDate('orders',$login_sess_access->account_id);
	if ($last_success_date)
	{
		$orders_start_date = new DateTime($last_success_date->format('c') . ' + 1 day');
	}
	
	// data delay is an offset from todays date that DateTime knows how to translate
	$orders_end_date = new DateTime($setup_sess_access->data_delay);
	
	if (isset($_GET['date'])) {
		$one_date = new DateTime($_GET['date']);
		if ($one_date > $orders_end_date)
		{
			// we can't process a date this recent, it's against our data delay setting
			echo returnOutput("{\"error\":\"Sync date is beyond your data delay setting.\"}");
			exit;
		}
		$orders_start_date = $one_date;
		$orders_end_date = $one_date;
	}
	
	if (mosqb_database::hasSyncSuccessDurring('orders',$login_sess_access->account_id,$orders_start_date,$orders_end_date))
	{
		echo returnOutput("{\"error\":\"Date range has already been synced.\"}");
		exit;
	}
	
	try
	{
		$orders_log = $mosqb_sync->syncOrders($orders_start_date,$orders_end_date);
	}
	catch (Exception $e)
	{
		$orders_log = array(array("msg"=>"Error: " . $e->getMessage() . " Line: " . $e->getLine() . " File: " . $e->getFile(),"success"=>false,"alert"=>true,"type"=>"msg"));
	}
	
	if (count($orders_log)>0)
	{
		if (isset($_GET['resync_account_log_id']))
		{
			// delete the log entries for the previous sync of this day if we are doing a resync
			mosqb_database::deleteAccountLogEntry($login_sess_access->account_id,$_GET['resync_account_log_id']);
		}
		mosqb_database::writeAccountLogEntries($login_sess_access->account_id,$orders_log);
	}
}

// record all the objects that got created, useful because we might change how things are synced in future, also we might want to do do something like add a way to delete stuff that was created to clean up a bad sync etc
mosqb_database::writeQBObjects($login_sess_access->account_id,$mosqb_sync->getObjectsWritten());

echo returnOutput("{\"success\":true}");

<?php

require_once("session.inc.php");
require_once("database.inc.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

require_once("MerchantOS/Option.class.php");
require_once("MerchantOS/Accounting.class.php");
require_once("MerchantOS/Shop.class.php");

require_once("Sync/MerchantOStoQuickBooks.class.php");

class Sync_SyncRunner
{
	protected $_qb_setup;
	protected $_oauth_setup;
	protected $_account_setup;
	protected $_account_id;
	protected $_mos_apikey;
	
	public function __construct($account_id)
	{
		$this->_account_id = $account_id;
	}
	
	public function initFromSession()
	{
		$this->_account_setup = new SessionAccess("setup");
		$this->_qb_setup = new SessionAccess("qb");
		$this->_oauth_setup = new SessionAccess("oauth");
		
		$merchantos_setup = new SessionAccess("merchantos");
		$this->_mos_apikey = $merchantos_setup->api_key;
	}
	
	public function initFromDatabase()
	{
		$this->_account_setup = mosqb_database::readSyncSetup($this->_account_id);
		
		$oauth_qb_arrays = mosqb_database::readOAuth($this->_account_id);
		if (!isset($oauth_qb_arrays['qb']) || !isset($oauth_qb_arrays['oauth']))
		{
			throw new Exception("IntuitAnywhere OAuth account setup is incomplete.");
		}
		$this->_qb_setup = (object)$oauth_qb_arrays['qb'];
		$this->_oauth_setup = (object)$oauth_qb_arrays['oauth'];
		
		$this->_mos_apikey = mosqb_databasegetAPIKeyFromAccountID($this->_account_id);
	}
	
	protected function _getIntuitAnywhereAccess()
	{
		GLOBAL $_OAUTH_INTUIT_CONFIG;
		$ianywhere = new IntuitAnywhere($this->_qb_setup);	
		$ianywhere->initOAuth($this->_oauth_setup,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization
		if (!$ianywhere->isUserAuthorized())
		{
			throw new Exception("IntuitAnywhere OAuth access could not be established. User not authorized.");
		}
		return $ianywhere;
	}
	
	public function run($date=null,$type="all",$resync_account_log_id=null)
	{
		$ianywhere = $this->_getIntuitAnywhereAccess();
		
		$account_id = $login_sess_access->account_id;
		
		$mos_accounting = new MerchantOS_Accounting($this->_mos_apikey,$this->_account_id);
		$mos_shop = new MerchantOS_Shop($this->_mos_apikey,$this->_account_id);
		
		$mosqb_sync = new Sync_MerchantOStoQuickBooks($mos_accounting,$mos_shop,$ianywhere);
		
		$this->_setType($mosqb_sync,$type);
		
		$average_costing = $this->_getAverageCosting();
		
		// shops to sync
		foreach ($this->_account_setup->setup_shops as $shopID=>$onoff)
		{
			if ($onoff === true || $onoff === 'on' || $onoff === 'On')
			{
				$mosqb_sync->setSyncShop($shopID);
			}
		}
		
		foreach ($this->_account_setup->setup_tax as $taxName=>$AccountId)
		{
			$mosqb_sync->addTaxAccount($taxName,$AccountId);
		}
		
		$mosqb_sync->setAccountMapping(array(
			"sales"=>$this->_account_setup->sales,
			"discounts"=>$this->_account_setup->discounts,
			//"tax"=>$setup_sess_access->tax,
			//"payments"=>$setup_sess_access->payments,
			"accounts_receivable"=>$this->_account_setup->accounts_receivable,
			"credit_accounts"=>$this->_account_setup->credit_accounts,
			"gift_cards"=>$this->_account_setup->gift_cards,
			"cogs"=>$this->_account_setup->cogs,
			"inventory"=>$this->_account_setup->inventory,
			//"orders"=>$setup_sess_access->inventory,
			"orders_shipping"=>$this->_account_setup->orders_shipping,
			"orders_other"=>$this->_account_setup->orders_other
		));
		
		if (!$average_costing)
		{
			$mosqb_sync->setFIFOCosting();
		}
		
		// check sync settings
		if (!$mosqb_sync->checkSyncSettings())
		{
			// we can't process a date this recent, it's against our data delay setting
			throw new Exception("QuickBooks or MerchantOS settings have changed, you need to check your SyncSettings.");
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
			$last_success_date = mosqb_database::getLastSuccessfulDataDate($log_test_type,$account_id);
			if ($last_success_date)
			{
				$sales_start_date = new DateTime($last_success_date->format('c') . ' + 1 day');
			}
			
			// data delay is an offset from todays date that DateTime knows how to translate
			$sales_end_date = new DateTime($setup_sess_access->data_delay);
			
			if (isset($date)) {
				$one_date = new DateTime($date);
				if ($one_date > $sales_end_date)
				{
					// we can't process a date this recent, it's against our data delay setting
					throw new Exception("Sync date is beyond your data delay setting.");
				}
				$sales_start_date = $one_date;
				$sales_end_date = $one_date;
			}
			
			if (mosqb_database::hasSyncSuccessDurring($log_test_type,$account_id,$sales_start_date,$sales_end_date))
			{
				throw new Exception("Date range has already been synced.");
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
				if (isset($resync_account_log_id))
				{
					// delete the log entries for the previous sync of this day if we are doing a resync
					mosqb_database::deleteAccountLogEntry($account_id,$resync_account_log_id);
				}
				mosqb_database::writeAccountLogEntries($account_id,$sales_log);
			}
		}
		
		
		// sync orders
		if ($send_orders)
		{
			$orders_start_date = new DateTime($setup_sess_access->start_date);
			
			// we need to get the last date synced and if start_date is <= then set it to that plus 1 day
			$last_success_date = mosqb_database::getLastSuccessfulDataDate('orders',$account_id);
			if ($last_success_date)
			{
				$orders_start_date = new DateTime($last_success_date->format('c') . ' + 1 day');
			}
			
			// data delay is an offset from todays date that DateTime knows how to translate
			$orders_end_date = new DateTime($setup_sess_access->data_delay);
			
			if (isset($date)) {
				$one_date = new DateTime($date);
				if ($one_date > $orders_end_date)
				{
					// we can't process a date this recent, it's against our data delay setting
					throw new Exception("Sync date is beyond your data delay setting.");
				}
				$orders_start_date = $one_date;
				$orders_end_date = $one_date;
			}
			
			if (mosqb_database::hasSyncSuccessDurring('orders',$account_id,$orders_start_date,$orders_end_date))
			{
				throw new Exception("Date range has already been synced.");
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
				if (isset($resync_account_log_id))
				{
					// delete the log entries for the previous sync of this day if we are doing a resync
					mosqb_database::deleteAccountLogEntry($account_id,$resync_account_log_id);
				}
				mosqb_database::writeAccountLogEntries($account_id,$orders_log);
			}
		}
		
		// record all the objects that got created, useful because we might change how things are synced in future, also we might want to do do something like add a way to delete stuff that was created to clean up a bad sync etc
		mosqb_database::writeQBObjects($account_id,$mosqb_sync->getObjectsWritten());
	}
	
	protected function _setType($mosqb_sync,$type="all")
	{
		if (!isset($type))
		{
			$type = "all";
		}
		
		$send_sales = false;
		$send_inventory = false;
		$send_orders = false;
		
		$send_sales = $this->_account_setup->send_sales;
		if ($send_sales == "on" || $send_sales == "On")
		{
			$send_sales = true;
		}
		else
		{
			$send_sales = false;
		}
		
		$send_inventory = $this->_account_setup->send_inventory;
		if ($send_inventory == "on" || $send_inventory == "On")
		{
			$send_inventory = true;
		}
		else
		{
			$send_inventory = false;
		}
		
		$send_orders = $this->_account_setup->send_orders;
		if ($send_orders == "on" || $send_orders == "On")
		{
			$send_orders = true;
		}
		else
		{
			$send_orders = false;
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
	}
	
	protected function _getAverageCosting()
	{
		$mos_option = new MerchantOS_Option($this->_mos_apikey,$this->_account_id);
		$options = $mos_option->listAll();
		$average_costing = true;
		if (isset($options['cost_method']) && $options['cost_method']!="average")
		{
			$average_costing = false;
		}
		return $average_costing;
	}
}

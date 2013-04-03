<?php

require_once("lib/SessionAccess.class.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

require_once("MerchantOS/Option.class.php");
require_once("MerchantOS/Accounting.class.php");
require_once("MerchantOS/Shop.class.php");

require_once("Sync/MerchantOStoQuickBooks.class.php");

class Sync_SyncRunner
{
	protected $_account_setup;
	protected $_account_id;
	protected $_mos_apikey;
	protected $_sync;
	
	protected $_send_sales;
	protected $_send_inventory;
	protected $_send_orders;
	
	/**
	 * @var Sync_Database
	 */
	protected $_db;
	
	/**
	 * @param Sync_Database $db Access to our database.
	 * @param integer $account_id The account that we are going to sync.
	 */
	public function __construct($db,$account_id)
	{
		$this->_db = $db;
		$this->_account_id = $account_id;
	}
	
	public function initFromSession()
	{
		$this->_account_setup = $this->_getSessionAccess("setup");
		
		// setup IntuitAnywhere Access
		$qb_setup = $this->_getSessionAccess("qb");
		$oauth_setup = $this->_getSessionAccess("oauth");
		$ianywhere = $this->_getIntuitAnywhereAccess($oauth_setup,$qb_setup);
		
		// setup MOS API access
		$merchantos_setup = $this->_getSessionAccess("merchantos");
		$this->_mos_apikey = $merchantos_setup->api_key;
		$mos_accounting = new MerchantOS_Accounting($this->_mos_apikey,$this->_account_id);
		$mos_shop = new MerchantOS_Shop($this->_mos_apikey,$this->_account_id);
		
		// setup our sync object
		$this->_sync = $this->_getMOSQBSync($mos_accounting,$mos_shop,$ianywhere);
		
	}
	
	public function initFromDatabase()
	{
		$this->_account_setup = $this->_db->readSyncSetup($this->_account_id);
		
		// setup IntuitAnywhere Access
		$oauth_qb_arrays = $this->_db->readOAuth($this->_account_id);
		if (!isset($oauth_qb_arrays['qb']) || !isset($oauth_qb_arrays['oauth']))
		{
			throw new Exception("IntuitAnywhere OAuth account setup is incomplete.");
		}
		$qb_setup = (object)$oauth_qb_arrays['qb'];
		$oauth_setup = (object)$oauth_qb_arrays['oauth'];
		$ianywhere = $this->_getIntuitAnywhereAccess($oauth_setup,$qb_setup);
		
		// setup MOS API access
		$this->_mos_apikey = $this->_db->getAPIKeyFromAccountID($this->_account_id);
		$mos_accounting = new MerchantOS_Accounting($this->_mos_apikey,$this->_account_id);
		$mos_shop = new MerchantOS_Shop($this->_mos_apikey,$this->_account_id);
		
		// setup our sync object
		$this->_sync = $this->_getMOSQBSync($mos_accounting,$mos_shop,$ianywhere);
	}
	
	public function run($date=null,$type="all")
	{
		$this->_setType($type);
		
		$this->_setShopsToSync();
		
		$this->_setTaxAccounts();
		
		$this->_setAccountMapping();
		
		$this->_setCosting();
		
		// check sync settings
		if (!$this->_sync->checkSyncSettings())
		{
			// we can't process a date this recent, it's against our data delay setting
			throw new Exception("QuickBooks or MerchantOS settings have changed, you need to check your SyncSettings.");
		}
		
		// sync sales
		if ($this->_send_sales || $this->_send_inventory)
		{
			$sales_log = $this->_syncSalesInventory($date,$type);
			
			if (count($sales_log)>0)
			{
				$this->_db->writeAccountLogEntries($this->_account_id,$sales_log);
			}
		}
		
		
		// sync orders
		if ($this->_send_orders)
		{
			$orders_log = $this->_syncOrders($date,$type);
			
			if (count($orders_log)>0)
			{
				$this->_db->writeAccountLogEntries($this->_account_id,$orders_log);
			}
		}
		
		// record all the objects that got created, useful because we might change how things are synced in future, also we might want to do do something like add a way to delete stuff that was created to clean up a bad sync etc
		$this->_db->writeQBObjects($this->_account_id,$this->_sync->getObjectsWritten());
	}
	
	protected function _setType($type="all")
	{
		if (!isset($type))
		{
			$type = "all";
		}
		
		$this->_send_sales = false;
		$this->_send_inventory = false;
		$this->_send_orders = false;
		
		$send_sales = $this->_account_setup->send_sales;
		if ($send_sales == "on" || $send_sales == "On")
		{
			$this->_send_sales = true;
		}
		else
		{
			$this->_send_sales = false;
		}
		
		$send_inventory = $this->_account_setup->send_inventory;
		if ($send_inventory == "on" || $send_inventory == "On")
		{
			$this->_send_inventory = true;
		}
		else
		{
			$this->_send_inventory = false;
		}
		
		$send_orders = $this->_account_setup->send_orders;
		if ($send_orders == "on" || $send_orders == "On")
		{
			$this->_send_orders = true;
		}
		else
		{
			$this->_send_orders = false;
		}
		
		
		if (!$this->_send_sales || ($type!='all' && $type!='sales'))
		{
			$this->_send_sales = false;
			$this->_sync->setNoSales();
		}
		
		if (!$this->_send_inventory || ($type!='all' && $type!='cogs'))
		{
			$this->_send_inventory = false;
			$this->_sync->setNoCOGS();
		}
		
		if (!$this->_send_orders || ($type!='all' && $type!='orders'))
		{
			$this->_send_orders = false;
			$this->_sync->setNoOrders();
		}
	}
	
	protected function _setShopsToSync()
	{
		// shops to sync
		foreach ($this->_account_setup->setup_shops as $shopID=>$onoff)
		{
			if ($onoff === true || $onoff === 'on' || $onoff === 'On')
			{
				$this->_sync->setSyncShop($shopID);
			}
		}
	}
	
	protected function _setTaxAccounts()
	{
		foreach ($this->_account_setup->setup_tax as $taxName=>$AccountId)
		{
			$this->_sync->addTaxAccount($taxName,$AccountId);
		}
	}
	
	protected function _setCosting()
	{
		$options = $this->_getMOSOptions();
		$average_costing = true;
		if (isset($options['cost_method']) && $options['cost_method']!="average")
		{
			$this->_sync->setFIFOCosting();
		}
		// default is avg_cost so we're good
	}
	
	protected function _setAccountMapping()
	{
		$this->_sync->setAccountMapping(array(
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
	}
	
	protected function _syncSalesInventory($date,$type)
	{
		$log_test_type = "sales";
		if ($type == 'cogs')
		{
			$log_test_type = "cogs";
		}
		
		list($start_date,$end_date) = $this->_getStartEndDates($type,$date);
		
		if ($this->_db->hasSyncSuccessDurring($log_test_type,$this->_account_id,$start_date,$end_date))
		{
			throw new Exception("Date range has already been synced.");
		}
		
		try
		{
			return $this->_sync->syncSales($start_date,$end_date);
		}
		catch (Exception $e)
		{
			return array(array("msg"=>"Error: " . $e->getMessage() . " Line: " . $e->getLine() . " File: " . $e->getFile(),"success"=>false,"alert"=>true,"type"=>"msg"));
		}
	}
	
	protected function _syncOrders($date,$type)
	{
		list($start_date,$end_date) = $this->_getStartEndDates($type,$date);
		
		if ($this->_db->hasSyncSuccessDurring('orders',$this->_account_id,$start_date,$end_date))
		{
			throw new Exception("Date range has already been synced.");
		}
		
		try
		{
			return $this->_sync->syncOrders($start_date,$end_date);
		}
		catch (Exception $e)
		{
			return array(array("msg"=>"Error: " . $e->getMessage() . " Line: " . $e->getLine() . " File: " . $e->getFile(),"success"=>false,"alert"=>true,"type"=>"msg"));
		}
	}
	
	protected function _getStartEndDates($type,$date)
	{
		$start_date = new DateTime($this->_account_setup->start_date);
		
		// we need to get the last date synced and if start_date is <= then set it to that plus 1 day
		$last_success_date = $this->_db->getLastSuccessfulDataDate($type,$this->_account_id);
		if ($last_success_date)
		{
			$start_date = new DateTime($last_success_date->format('c') . ' + 1 day');
		}
		
		// data delay is an offset from todays date that DateTime knows how to translate
		$end_date = new DateTime($this->_account_setup->data_delay);
		
		if (isset($date)) {
			$one_date = new DateTime($date);
			if ($one_date > $end_date)
			{
				// we can't process a date this recent, it's against our data delay setting
				throw new Exception("Sync date is beyond your data delay setting.");
			}
			$start_date = $one_date;
			$end_date = $one_date;
		}
		
		return array($start_date,$end_date);
	}
	

	/**
	 * Initializes and returns an IntuitAnywhere object, use this function to return a mock in unit tests
	 * @return IntuitAnywhere
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereAccess($oauth_setup,$qb_setup)
	{
		GLOBAL $_OAUTH_INTUIT_CONFIG;
		$ianywhere = new IntuitAnywhere($qb_setup);	
		$ianywhere->initOAuth($oauth_setup,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization
		if (!$ianywhere->isUserAuthorized())
		{
			throw new Exception("IntuitAnywhere OAuth access could not be established. User not authorized.");
		}
		return $ianywhere;
	}
	
	/**
	 * Returns a Sync_MerchantOStoQuickBooks object, use this function to return a mock in unit tests
	 * @return Sync_MerchantOStoQuickBooks
	 * @codeCoverageIgnore
	 */
	protected function _getMOSQBSync($mos_accounting,$mos_shop,$ianywhere)
	{
		return new Sync_MerchantOStoQuickBooks($mos_accounting,$mos_shop,$ianywhere);
	}
	
	/**
	 * Returns a SessionAccess object, use this function to return a mock in unit tests
	 * @return SessionAccess
	 * @codeCoverageIgnore
	 */
	protected function _getSessionAccess($type)
	{
		return new SessionAccess($type);
	}
	
	/**
	 * Returns a MerchantOS_Option->listAll() result, use this function to return a mock in unit tests
	 * @return Array of from MerchantOS_Option->listAll()
	 * @codeCoverageIgnore
	 */
	protected function _getMOSOptions()
	{
		$mos_option = new MerchantOS_Option($this->_mos_apikey,$this->_account_id);
		return $mos_option->listAll();
	}
}

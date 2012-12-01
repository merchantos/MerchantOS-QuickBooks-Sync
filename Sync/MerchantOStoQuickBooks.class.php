<?php

class Sync_MerchantOStoQuickBooks {
	static protected $_MOS_CUSTOMER = "POS Customers (MerchantOS)";
	/**
	 * @var MerchantOS_Accounting
	 */
	protected $_mos_accounting;
	/**
	 * @var IntuitAnywhere
	 */
	protected $_i_anywhere;
	/**
	 * @var array Indexed by MerchantOS function to QB accountId
	 */
	protected $_accounts_map;
	/**
	 * @var boolean Are we using average costing? Default = true
	 */
	protected $_average_costing;
	/**
	 * @var boolean Should we send sales? Deafult = true
	 */
	protected $_send_sales;
	/**
	 * @var boolean Should we send COGS/Inventory? Default = true
	 */
	protected $_send_cogs;
	/**
	 * @var boolean Should we send Orders? Default = true
	 */
	protected $_send_orders;
	/**
	 * @var array Array of shops (indexed by shopID) to include in the sync. array(shopID=>true/false/null) if the shopID = true it will be included, otherwise it will not.
	 */
	protected $_shops;
	/**
	 * @var array Array of tax account mappings (indexed by tax name). array(taxName=>AccountId).
	 */
	protected $_tax_accounts;
	/**
	 * @var array The data we are compiling to sync
	 */
	protected $_days_buffer;
	
	/*
	 * @var array of cached IntuitAnywhere_Vendor objects that were found by querying. Indexed by Vendor->Name
	 */
	protected $_qb_vendors;
	/*
	 * @var array of cached IntuitAnywhere_Account objects that were found by querying. Indexed by Accout->Name + Account->ParentAccountId
	 */
	protected $_qb_accounts;
	/*
	 * @var array of cached IntuitAnywhere_Class objects that were found by querying. Indexed by Class->Name
	 */
	protected $_qb_classes;
	/*
	 * @var array of cached IntuitAnywhere_Customer objects that were found by querying. Indexed by Customer->Name
	 */
	protected $_qb_customers;
	/**
	 * @var array of cached IntuitAnywhere_PaymentMethod objects that were found by querying. Indexed by PaymentMethod->Name
	 */
	protected $_qb_payment_methods;
	
	public function __construct($mos_accounting,$i_anywhere)
	{
		$this->_mos_accounting = $mos_accounting;
		$this->_i_anywhere = $i_anywhere;
		$this->_average_costing = true;
		$this->_send_sales = true;
		$this->_send_cogs = true;
		$this->_send_orders = true;
		$this->_shops = array();
		$this->_tax_accounts = array();
	}
	
	public function setAccountMapping($map)
	{
		$this->_accounts_map = $map;
	}
	
	public function setFIFOCosting()
	{
		$this->_average_costing = false;
	}
	
	public function setNoSales()
	{
		$this->_send_sales = false;
	}
	
	public function setNoCOGS()
	{
		$this->_send_cogs = false;
	}
	
	public function setNoOrders()
	{
		$this->_send_orders = false;
	}
	
	public function setSyncShop($id)
	{
		$this->_shops[$id] = true;
	}
	
	public function addTaxAccount($name,$id)
	{
		$this->_tax_accounts[$name] = $id;
	}
	
	/**
	 * Sync MerchantOS to QuickBooks
	 * @param DateTime $start_date The first date in the range to sync for
	 * @param DateTime $end_date The last date in the range to sync for
	 * @return array Array of date=>,msg=> for each day that was synced
	 */
	public function sync($start_date,$end_date)
	{
		$this->_checkSyncSettings();
		$sales_cogs_logs = $this->_syncSalesCOGS($start_date,$end_date);
		$orders_logs = $this->_syncOrders($start_date,$end_date);
		return array_merge($sales_cogs_logs,$orders_logs);
	}
	
	protected function _checkSyncSettings()
	{
		/**
		 * @todo Check settings against QB and MerchantOS to make sure nothing has changed with accounts/shops/taxes etc
		 */
	}
	
	protected function _syncOrders($start_date,$end_date)
	{
		return array();
	}
	
	protected function _syncSalesCOGS($start_date,$end_date)
	{
		$this->_days_buffer = array();
		if ($this->_send_cogs || $this->_send_sales)
		{
			$this->_fillDaysWithSalesAndCOGS($start_date,$end_date);
		}
		if ($this->_send_sales)
		{
			$this->_fillDaysWithDiscounts($start_date,$end_date);
			$this->_fillDaysWithTax($start_date,$end_date);
			$this->_fillDaysWithPayments($start_date,$end_date);
		}
		
		if (count($this->_days_buffer)==0)
		{
			return array(array("date"=>date_format(new DateTime(),"m/d/Y")),"msg"=>"No records to sync.","success"=>true,"alert"=>false);
		}
		
		require_once("IntuitAnywhere/JournalEntry.class.php");
		require_once("IntuitAnywhere/Payment.class.php");
		
		$multi_shop = false;
		if (count($this->_days_buffer)>1)
		{
			$multi_shop = true;
		}
		
		$logs = array();
		$shopName = "";
		foreach ($this->_days_buffer as $shopID=>$sales_day_shop)
		{
			$shopName = $this->_getShopName($shopID);
			$shopName_msg = "";
			if ($multi_shop)
			{
				$shopName_msg = "$shopName: ";
			}
			
			foreach ($sales_day_shop as $date=>$sales_data)
			{
				$sales_total = round($this->_getSalesTotal($sales_data),2);
				$discounts_total = round($this->_getDiscountsTotal($sales_data),2);
				$tax_total = round($this->_getTaxTotal($sales_data),2);
				$payments_total = round($this->_getPaymentsTotal($sales_data),2);
				
				$balance = 0;
				$balance += $sales_total;
				$balance -= $discounts_total;
				$balance += $tax_total;
				$balance -= $payments_total;
				if (round($balance)!=0)
				{
					$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}Records contained unbalanced sales.","success"=>false,"alert"=>true);
					continue;
				}
				
				if ($this->_send_sales)
				{
					if ($this->_sendSales($date,$shopName,$sales_data))
					{
						$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}Sent \$$sales_total Sales, \$$discounts_total Discounts, \$$tax_total Tax, \$$payments_total Payments.","success"=>true,"alert"=>false);
					}
					else
					{
						$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}$0 in Sales.","success"=>true,"alert"=>false);
					}
				}
				
				if ($this->_send_cogs)
				{
					$cogs_total = round($this->_getCOGSTotal($sales_data),2);
					if ($this->_sendCOGS($date,$shopName,$sales_data))
					{
						$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}Sent \$$cogs_total in Cost of Goods Sold.","success"=>true,"alert"=>false);
					}
					else
					{
						$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}$0 in COGS.","success"=>true,"alert"=>false);
					}
				}
			}
		}
		return $logs;
	}
	
	protected function _sendSales($date,$shopName,$sales_data)
	{
		$sale_lines = $this->_getSaleLines($date,$sales_data);
		$discount_lines = $this->_getDiscountLines($date,$sales_data);
		$tax_lines = $this->_getTaxLines($date,$sales_data);
		$payment_lines = $this->_getPaymentJournalEntryLines($date,$sales_data);
		
		$lines = array_merge($sale_lines,$discount_lines,$tax_lines,$payment_lines);
		
		if (count($lines)>0)
		{
			// actual Payment objects
			$payments = $this->_getPayments($date,$sales_data);
			
			$journalentry = new IntuitAnywhere_JournalEntry($this->_i_anywhere);
			$journalentry->Lines = $lines;
			$journalentry->HeaderTxnDate = new DateTime($date);
			$journalentry->HeaderNote = "Retail sales from MerchantOS on $date from $shopName";
			$journalentry->save();
			
			foreach ($payments as $payment)
			{
				$payment->save();
			}
			
			return true;
		}
		return false;
	}
	
	protected function _sendCOGS($date,$shopName,$sales_data)
	{
		$cogs_inventory_lines = $this->_getCOGSInventoryLines($date,$sales_data);
		
		if (count($cogs_inventory_lines)>0)
		{
			$journalentry = new IntuitAnywhere_JournalEntry($this->_i_anywhere);
			$journalentry->Lines = $cogs_inventory_lines;
			$journalentry->HeaderTxnDate = new DateTime($date);
			$journalentry->HeaderNote = "COGS and inventory from MerchantOS on $date from $shopName";
			$journalentry->save();
			return true;
		}
		return false;
	}
	
	protected function _fillDaysWithSalesAndCOGS($start_date,$end_date)
	{
		$sales_by_tax_class = $this->_mos_accounting->getTaxClassSalesByDay($this->_startOfDay($start_date),$this->_endOfDay($end_date));
		
		foreach ($sales_by_tax_class as $sales_day_class)
		{
			$shopID = (string)$sales_day_class->shopID;
			$date = (string)$sales_day_class->date;
			$tax_class = (string)$sales_day_class->taxClassName;
			$this->_days_buffer[$shopID][$date]['sales'][$tax_class] = (string)$sales_day_class->subtotal;
			$this->_days_buffer[$shopID][$date]['fifo_cogs'][$tax_class] = (string)$sales_day_class->fifoCost;
			$this->_days_buffer[$shopID][$date]['avg_cogs'][$tax_class] = (string)$sales_day_class->avgCost;
		}
	}
	
	protected function _fillDaysWithDiscounts($start_date,$end_date)
	{
		$discounts = $this->_mos_accounting->getDiscountsByDay($this->_startOfDay($start_date),$this->_endOfDay($end_date));
		
		foreach ($discounts as $discount_day)
		{
			$shopID = (string)$discount_day->shopID;
			$date = (string)$discount_day->date;
			$this->_days_buffer[$shopID][$date]['discounts'] = (string)$discount_day->discount;
		}
	}
	
	protected function _fillDaysWithTax($start_date,$end_date)
	{
		$taxes_by_day = $this->_mos_accounting->getTaxesByDay($this->_startOfDay($start_date),$this->_endOfDay($end_date));
		
		foreach ($taxes_by_day as $tax_day)
		{
			$shopID = (string)$tax_day->shopID;
			$date = (string)$tax_day->date;
			$tax_vendor = (string)$tax_day->taxCategoryName;
			$this->_days_buffer[$shopID][$date]['taxes'][$tax_vendor] = (string)$tax_day->tax;
		}
	}
	
	protected function _fillDaysWithPayments($start_date,$end_date)
	{
		$payments = $this->_mos_accounting->getPaymentsByDay($this->_startOfDay($start_date),$this->_endOfDay($end_date));
		
		foreach ($payments as $payment_day_type)
		{
			$shopID = (string)$payment_day_type->shopID;
			$date = (string)$payment_day_type->date;
			$payment_type = (string)$payment_day_type->paymentTypeName;
			$this->_days_buffer[$shopID][$date]['payments'][$payment_type] = (string)$payment_day_type->amount;
		}
	}
	
	protected function _getSalesTotal($sales_data)
	{
		$balance = 0;
		if (isset($sales_data['sales']))
		{
			foreach ($sales_data['sales'] as $tax_class=>$sales_subtotal)
			{
				$balance += (float)$sales_subtotal;
			}
		}
		return $balance;
	}
	protected function _getTaxTotal($sales_data)
	{
		$balance = 0;
		if (isset($sales_data['taxes']))
		{
			foreach ($sales_data['taxes'] as $tax_vendor=>$tax_subtotal)
			{
				$balance += (float)$tax_subtotal;
			}
		}
		return $balance;
	}
	protected function _getDiscountsTotal($sales_data)
	{
		$balance = 0;
		if (isset($sales_data['discounts']))
		{
			$balance += (float)$sales_data['discounts'];
		}
		return $balance;
	}
	protected function _getPaymentsTotal($sales_data)
	{
		$balance = 0;
		if (isset($sales_data['payments']))
		{
			foreach ($sales_data['payments'] as $payment_type=>$payment_subtotal)
			{
				$balance += (float)$payment_subtotal;
			}
		}
		return $balance;
	}
	
	protected function _getSaleLines($date,$sales_data)
	{
		if (!isset($sales_data['sales']))
		{
			return array();
		}
		
		$lines = array();
		
		foreach ($sales_data['sales'] as $tax_class=>$sales_subtotal)
		{
			$line = new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
			$line->AccountId = $this->_accounts_map['sales'];
			$line->Desc = "MerchantOS $tax_class sales on $date";
			$line->Amount = (float)$sales_subtotal;
			
			if (!$this->_prepareLineAmount($line,"Credit","Debit"))
			{
				continue;
			}
			
			$line->ClassId = $this->_getQBClassNamed($tax_class);
			
			$lines[] = $line;
		}
		
		
		return $lines;
	}
	
	protected function _getDiscountLines($date,$sales_data)
	{
		if (!isset($sales_data['discounts']))
		{
			return array();
		}
		$line = new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
		$line->AccountId = $this->_accounts_map['discounts'];
		$line->Desc = "MerchantOS discounts $date";
		$line->Amount = $this->_getDiscountsTotal($sales_data);
		
		if (!$this->_prepareLineAmount($line,"Debit","Credit"))
		{
			return array();
		}
		
		return array($line);
	}
	
	protected function _getTaxLines($date,$sales_data)
	{
		if (!isset($sales_data['taxes']))
		{
			return array();
		}
		
		$lines = array();
		
		foreach ($sales_data['taxes'] as $tax_vendor=>$tax_subtotal)
		{
			if (!isset($this->_tax_accounts[$tax_vendor]))
			{
				throw new Exception($tax_vendor . " does not have a mapped sales tax account.");
			}
			
			$line = new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
			$line->AccountId = $this->_tax_accounts[$tax_vendor];
			$line->Desc = "MerchantOS $tax_vendor sales tax on $date";
			$line->Amount = (float)$tax_subtotal;
			
			if (!$this->_prepareLineAmount($line,"Credit","Debit"))
			{
				continue;
			}
			
			$lines[] = $line;
		}
		
		return $lines;
	}
	
	/**
	 * This gets a JournalEntryLine for putting payment needed into Accounts Receivable
	 * there is another function for sending the payment that credits accounts receivable and puts money in undeposited funds
	 */
	protected function _getPaymentJournalEntryLines($date,$sales_data)
	{
		if (!isset($sales_data['payments']))
		{
			return array();
		}
		
		$line = new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
		$line->AccountId = $this->_accounts_map['payments'];
		$line->Desc = "MerchantOS $payment_type payments on $date";
		$line->Amount = $this->_getPaymentsTotal($sales_data);
		
		if (!$this->_prepareLineAmount($line,"Debit","Credit"))
		{
			return array();
		}
		
		$line->EntityId = $this->_getQBCustomerNamed(self::$_MOS_CUSTOMER);
		$line->EntityType = 'CUSTOMER';
		
		return array($line);
	}
	
	protected function _getPayments($date,$sales_data)
	{
		/**
		 * @todo Create Payment for each payment_type in the sales_data attach to $_MOS_CUSTOMER and correct PaymentMethod, return an array of Payment objects
		 */
	}
	
	protected function _getCOGSTotal($sales_data)
	{
		$balance = 0;
		if ($this->_average_costing)
		{
			if (isset($sales_data['avg_cogs']))
			{
				foreach ($sales_data['avg_cogs'] as $tax_class=>$avg_cogs_subtotal)
				{
					$balance += (float)$avg_cogs_subtotal;
				}
			}
		}
		else
		{
			if (isset($sales_data['fifo_cogs']))
			{
				foreach ($sales_data['fifo_cogs'] as $tax_class=>$fifo_cogs_subtotal)
				{
					$balance += (float)$fifo_cogs_subtotal;
				}
			}
		}
		return $balance;
	}
	
	protected function _getCOGSInventoryLines($date,$sales_data)
	{
		if (!isset($sales_data['sales']))
		{
			return array();
		}
		
		$inventory_line = new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
		$inventory_line->AccountId = $this->_accounts_map['inventory'];
		$inventory_line->Desc = "MerchantOS Inventory Sold $date";
		$inventory_line->Amount = $this->_getCOGSTotal($sales_data);
		
		$cogs_line = new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
		$cogs_line->AccountId = $this->_accounts_map['cogs'];
		$cogs_line->Desc = "MerchantOS COGS $date";
		$cogs_line->Amount = $this->_getCOGSTotal($sales_data);
		
		if ($this->_prepareLineAmount($inventory_line,"Credit","Debit") &&
			$this->_prepareLineAmount($cogs_line,"Debit","Credit"))
		{
			return array($inventory_line,$cogs_line);
		}
		
		return array();
	}
	
	protected function _prepareLineAmount($line,$positivetype,$negativetype)
	{
		$amount = abs(round($line->Amount,2));
		if ($amount==0)
		{
			return false;
		}
		if ($amount<0)
		{
			$line->PostingType = $negativetype;
		}
		else
		{
			$line->PostingType = $positivetype;
		}
		$line->Amount = number_format($amount,2,".","");
		return true;
	}
	
	protected function _startOfDay($start_date)
	{
		return $start_date->format("Y-m-d\T00:00:00P");
	}
	protected function _endOfDay($end_date)
	{
		return $end_date->format("Y-m-d\T23:59:59P");
	}
	
	protected function _getQBPaymentMethodNamed($name)
	{
		if (isset($this->_qb_payment_methods[$name]))
		{
			return $this->_qb_payment_methods[$name]->Id;
		}
		/**
		 * @todo rest of this
		 */
	}
	
	protected function _getQBCustomerNamed($name)
	{
		if (isset($this->_qb_customers[$name]))
		{
			return $this->_qb_customers[$name]->Id;
		}
		/**
		 * @todo rest of this
		 */
	}
	
	protected function _getQBClassNamed($name)
	{
		if (isset($this->_qb_classes[$name]))
		{
			return $this->_qb_classes[$name]->Id;
		}
		/**
		 * @todo rest of this
		 */
	}
	
	protected function _getQBAccountNamed($name,$parent_account_id)
	{
		$cache_index = $name . $parent_account_id;
		if (isset($this->_qb_accounts[$cache_index]))
		{
			return $this->_qb_accounts[$cache_index]->Id;
		}
		/**
		 * @todo rest of this
		 */
	}
	
	protected function _getQBVendorNamed($name)
	{
		if (isset($this->_qb_vendors[$name]))
		{
			return $this->_qb_vendors[$name]->Id;
		}
		
		require_once("IntuitAnywhere/Vendor.class.php");
		$ia_vendor = new IntuitAnywhere_Vendor($ianywhere);
		
		// search for an existing vendor
		$filters = array('Name'=>$name);
		
		$vendors = $ia_vendor->listAll($filters,1);
		
		if (count($vendors)==1)
		{
			$this->_qb_vendors[$name] = $vendors[0];
			return $this->_qb_vendors[$name]->Id;
		}
		
		// couldn't find the vendor so we'll create one
		$ia_vendor->Name = $name;
		$ia_vendor->save();
		$this->_qb_vendors[$name] = $ia_vendor;
		return $this->_qb_vendors[$name]->Id;
	}
}

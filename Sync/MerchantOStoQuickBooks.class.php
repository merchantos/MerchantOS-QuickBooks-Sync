<?php

require_once("IntuitAnywhere/Account.class.php");
require_once("IntuitAnywhere/Bill.class.php");
require_once("IntuitAnywhere/Class.class.php");
require_once("IntuitAnywhere/Customer.class.php");
require_once("IntuitAnywhere/JournalEntry.class.php");
require_once("IntuitAnywhere/Payment.class.php");
require_once("IntuitAnywhere/PaymentMethod.class.php");
require_once("IntuitAnywhere/Vendor.class.php");

class Sync_MerchantOStoQuickBooks {
	static protected $_MOS_CUSTOMER = "POS Customers (MerchantOS)";
	static protected $_UNDEPOSITED_FUNDS = "Undeposited Funds";
	/**
	 * Functions used: getOrdersByTaxClass,getTaxClassSalesByDay,getDiscountsByDay,getTaxesByDay,getPaymentsByDay
	 * @var MerchantOS_Accounting
	 */
	protected $_mos_accounting;
	/**
	 * Functions used: listAll
	 * @var MerchantOS_Shop
	 */
	protected $_mos_shop;
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
	/**
	 * @var array Result of calling MerchantOS_Shop::listAll (list of all shops). Cache so we don't call it multiple times.
	 */
	protected $_mos_shops_list;
	
	/**
	 * @var array List of QB objects that have been created durring the sync(s)
	 */
	protected $_qb_objects;
	
	/**
	 * Initialize the sync object for syncing MerchantOS data to QuickBooks via IntuitAnywhere API
	 * @param MerchantOS_Accounting $mos_accounting Used to query accounting data from MerchantOS
	 * @param MerchantOS_Shop $mos_shop Used to query Shop records from MerchantOS
	 * @param IntuitAnywhere $i_anywhere Used to read and write data to QuickBooks via IntuitAnywhere API
	 */
	public function __construct($mos_accounting,$mos_shop,$i_anywhere)
	{
		$this->_mos_accounting = $mos_accounting;
		$this->_mos_shop = $mos_shop;
		$this->_i_anywhere = $i_anywhere;
		$this->_average_costing = true;
		$this->_send_sales = true;
		$this->_send_cogs = true;
		$this->_send_orders = true;
		$this->_shops = array();
		$this->_tax_accounts = array();
		$this->_qb_objects = array();
	}
	
	/**
	 * Get a list of objects written to QuickBooks via IntuitAnywhere API
	 * @return array Array of form [['type':'vendor','id':42]]
	 */
	public function getObjectsWritten()
	{
		return $this->_qb_objects;
	}
	
	/**
	 * Set the account mapping for the sync.
	 * @param array $map The map of accounts. Of form [['name':'orders_shipping','id':42]],
	 *   where 'name' is one of: sales, discounts, accounts_receivable, inventory, cogs, inventory, orders_shipping, orders_other
	 *   where 'id' is the id of the account within QuickBooks/IntuitAnywhere
	 */
	public function setAccountMapping($map)
	{
		$this->_accounts_map = $map;
	}
	
	/**
	 * Set the sync to use FIFO costing instead of the default average costing.
	 */
	public function setFIFOCosting()
	{
		$this->_average_costing = false;
	}
	
	/**
	 * Set the sync to not transfer Sales data.
	 */
	public function setNoSales()
	{
		$this->_send_sales = false;
	}
	
	/**
	 * Set the sync to not transfer COGS (cost of goods sold) data.
	 */
	public function setNoCOGS()
	{
		$this->_send_cogs = false;
	}
	
	/**
	 * Set the sync to not transfer Orders data.
	 */
	public function setNoOrders()
	{
		$this->_send_orders = false;
	}
	
	/**
	 * Set the sync to transfer data from a particular shop (id is from MerchantOS). You can call this multiple times for a multi shop account.
	 * @param integer $id ID of the shop record in MerchantOS.
	 */
	public function setSyncShop($id)
	{
		$this->_shops[$id] = true;
	}
	
	/**
	 * Map a tax name from MerchantOS to a vendor in QuickBOoks.
	 * @param string $name The name of the tax in MerchantOS.
	 * @param mixed $id The ID of the Vendor in QuickBooks.
	 */
	public function addTaxAccount($name,$id)
	{
		$this->_tax_accounts[$name] = $id;
	}
	
	/**
	 * Sync MerchantOS Sales and Inventory to QuickBooks
	 * @param DateTime $start_date The first date in the range to sync for
	 * @param DateTime $end_date The last date in the range to sync for
	 * @return array Array of date=>,msg=> for each day that was synced
	 */
	public function syncSales($start_date,$end_date)
	{
		return $this->_syncSalesCOGS($start_date,$end_date);
	}
	
	/**
	 * Sync MerchantOS Orders to QuickBooks
	 * @param DateTime $start_date The first date in the range to sync for
	 * @param DateTime $end_date The last date in the range to sync for
	 * @return array Array of date=>,msg=> for each day that was synced
	 */
	public function syncOrders($start_date,$end_date)
	{
		return $this->_syncOrders($start_date,$end_date);
	}
	
	/**
	 * Check to see if all the settings are still valid for syncing.
	 * @return boolean True if everything is ok.
	 */
	public function checkSyncSettings()
	{
		/**
		 * @todo Check settings against QB and MerchantOS to make sure nothing has changed with accounts/shops/taxes etc
		 */
		return true;
	}
	
	protected function _syncSalesCOGS($start_date,$end_date)
	{
		$this->_days_buffer = array();
		if ($this->_send_cogs || $this->_send_sales)
		{
			$this->_fillDaysWithSalesAndCOGS($start_date,$end_date);
		}
		else
		{
			return array();
		}
		if ($this->_send_sales)
		{
			$this->_fillDaysWithDiscounts($start_date,$end_date);
			$this->_fillDaysWithTax($start_date,$end_date);
			$this->_fillDaysWithPayments($start_date,$end_date);
		}
		
		if (count($this->_days_buffer)==0)
		{
			return array(array("date"=>date_format(new DateTime(),"m/d/Y"),"msg"=>"No sales/COGS to sync.","success"=>true,"alert"=>false,"type"=>"sales"));
		}
		
		$multi_shop = false;
		if (count($this->_shops)>1)
		{
			$multi_shop = true;
		}
		
		$logs = array();
		$shopName = "";
		foreach ($this->_days_buffer as $shopID=>$sales_day_shop)
		{
			if (!isset($this->_shops[$shopID]) || !$this->_shops[$shopID])
			{
				continue;
			}
			
			$shopName = $this->_getShopName($shopID);
			$shopName_msg = "";
			if ($multi_shop)
			{
				$shopName_msg = "$shopName: ";
			}
			
			foreach ($sales_day_shop as $date=>$sales_data)
			{
				if ($this->_send_sales)
				{
					$sales_total = round($this->_getSalesTotal($sales_data),2);
					$discounts_total = round($this->_getDiscountsTotal($sales_data),2);
					$tax_total = round($this->_getTaxTotal($sales_data),2);
					$payments_total = round($this->_getPaymentsTotal($sales_data),2);
					
					$balance = 0;
					$bsale_total = 0;
					$bsale_total += $sales_total;
					$bsale_total -= $discounts_total;
					$bsale_total += $tax_total;
					$balance = $bsale_total-$payments_total;
					if (round($balance)!=0)
					{
						$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}Records contained unbalanced sales (\$$bsale_total sales, \$$payments_total payments).","success"=>false,"alert"=>true,"type"=>"sales");
						continue;
					}
					
					if ($this->_sendSales($date,$shopName,$sales_data))
					{
						$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}Sent \$$sales_total Sales, \$$discounts_total Discounts, \$$tax_total Tax, \$$payments_total Payments.","success"=>true,"alert"=>false,"type"=>"sales");
					}
					else
					{
						$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}$0 in Sales.","success"=>true,"alert"=>false,"type"=>"sales");
					}
				}
				
				if ($this->_send_cogs)
				{
					$cogs_total = round($this->_getCOGSTotal($sales_data),2);
					if ($this->_sendCOGS($date,$shopName,$sales_data))
					{
						$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}Sent \$$cogs_total in Cost of Goods Sold.","success"=>true,"alert"=>false,"type"=>"cogs");
					}
					else
					{
						$logs[] = array("date"=>$date,"msg"=>"{$shopName_msg}$0 in COGS.","success"=>true,"alert"=>false,"type"=>"cogs");
					}
				}
			}
		}
		
		if (count($logs)==0)
		{
			return array(array("date"=>date_format(new DateTime(),"m/d/Y"),"msg"=>"No sales/COGS to sync.","success"=>true,"alert"=>false,"type"=>"sales"));
		}
		
		return $logs;
	}
	
	protected function _syncOrders($start_date,$end_date)
	{
		/*
		 * @todo need to move this into the main sync for sales so it only happens if the day has no unbalanced sales. So re-syncing days works correctly.
		 * Alternative: record syncing of orders separately from sales (maybe ver 2.0).
		 */
		if (!$this->_send_orders)
		{
			return array();
		}
		
		$orders_by_tax_class = $this->_mos_accounting->getOrdersByTaxClass($this->_startOfDay($start_date),$this->_endOfDay($end_date));
		
		if (!is_array($orders_by_tax_class) || count($orders_by_tax_class)==0)
		{
			return array(array("date"=>date_format(new DateTime(),"m/d/Y"),"msg"=>"No orders to sync.","success"=>true,"alert"=>false,"type"=>"orders"));
		}
		
		$orders = array();
		
		foreach ($orders_by_tax_class as $order_line)
		{
			$shop_id = (string)$order_line->shopID;
			$date = (string)$order_line->date;
			$tax_class_id = (string)$order_line->taxClassID;
			$tax_class = (string)$order_line->taxClassName;
			$vendor_id = (string)$order_line->vendorID;
			$vendor_name = (string)$order_line->vendorName;
			$line_cost = (string)$order_line->cost;
			$order_id = (string)$order_line->orderID;
			$ship_cost = (string)$order_line->totalShipCost;
			$other_cost = (string)$order_line->totalOtherCost;
			
			if (!isset($this->_shops[$shop_id]) || !$this->_shops[$shop_id])
			{
				// shop not included in sync
				continue;
			}
			
			// we must have a vendor
			if ($vendor_id<=0 || strlen(trim($vendor_name))<=0)
			{
				// no vendor
				continue;
			}
			
			if (!isset($orders[$shop_id]))
			{
				$orders[$shop_id] = array();
			}
			
			if (!isset($orders[$shop_id][$order_id]))
			{
				// order has not be started yet (first line we've seen of order) so create the bill
				$shopName = $this->_getShopName($shop_id);
				
				$ia_bill = $this->_getIntuitAnywhereBill();
				
				$ia_bill->HeaderTxnDate = new DateTime($date);
				$ia_bill->HeaderDocNumber = "mos" . $order_id;
				$ia_bill->HeaderMsg = "MerchantOS order #$order_id from $vendor_name at $shopName";
				$ia_bill->HeaderVendorId = $this->_getQBVendorNamed($vendor_name);
				$ia_bill->Lines = array();
				
				if ($ship_cost != 0.00)
				{
					$ia_bill_line = $this->_getIntuitAnywhereBillLine();
					$ia_bill_line->Desc = "Shipping";
					$ia_bill_line->Amount = $this->_formatSignedAmount($ship_cost);
					$ia_bill_line->AccountId = $this->_accounts_map['orders_shipping'];
					$ia_bill->Lines[] = $ia_bill_line;
				}
				
				if ($other_cost != 0.00)
				{
					$ia_bill_line = $this->_getIntuitAnywhereBillLine();
					$ia_bill_line->Desc = "Other Costs";
					$ia_bill_line->Amount = $this->_formatSignedAmount($other_cost);
					$ia_bill_line->AccountId = $this->_accounts_map['orders_other'];
					$ia_bill->Lines[] = $ia_bill_line;
				}
				
				$orders[$shop_id][$order_id] = $ia_bill;
			}
			
			// add the line to the bill
			$ia_bill_line = $this->_getIntuitAnywhereBillLine();
			$ia_bill_line->Desc = "$tax_class purchase order subtotal";
			$ia_bill_line->Amount = $this->_formatSignedAmount($line_cost);
			$ia_bill_line->ClassId = $this->_getQBClassNamed($tax_class);
			$ia_bill_line->AccountId = $this->_accounts_map['inventory'];
			
			$orders[$shop_id][$order_id]->Lines[] = $ia_bill_line;
		}
		
		$count = count($orders);
		if ($count==0)
		{
			return array(array("date"=>date_format(new DateTime(),"m/d/Y"),"msg"=>"No orders to sync.","success"=>true,"alert"=>false,"type"=>"orders"));
		}
		
		$multi_shop = false;
		if (count($this->_shops)>1)
		{
			$multi_shop = true;
		}
		
		foreach ($orders as $shop_id=>$shop_orders)
		{
			$shop_name = $this->_getShopName($shop_id);
			
			$shop_name_msg = "";
			if ($multi_shop)
			{
				$shop_name_msg = "$shop_name: ";
			}
			
			foreach ($shop_orders as $orderID=>$order)
			{
				$this->_writeObject($order);
				
				$logs[] = array("date"=>date_format($order->HeaderTxnDate,"m/d/Y"),"msg"=>"{$shop_name_msg}Order #{$orderID} synced.","success"=>true,"alert"=>false,"type"=>"orders");
			}
		}
		
		return $logs;
	}
	
	protected function _sendSales($date,$shopName,$sales_data)
	{
		$sale_lines = $this->_getSaleLines($date,$shopName,$sales_data);
		$discount_lines = $this->_getDiscountLines($date,$shopName,$sales_data);
		$tax_lines = $this->_getTaxLines($date,$shopName,$sales_data);
		$payment_lines = $this->_getPaymentJournalEntryLines($date,$shopName,$sales_data);
		
		$lines = array_merge($sale_lines,$discount_lines,$tax_lines,$payment_lines);
		
		if (count($lines)>0)
		{
			// actual Payment objects
			$payments = $this->_getPayments($date,$shopName,$sales_data);
			
			$journalentry = $this->_getIntuitAnywhereJournalEntry();
			$journalentry->Lines = $lines;
			$journalentry->HeaderTxnDate = new DateTime($date);
			$journalentry->HeaderNote = "Retail sales from MerchantOS on $date from $shopName";
			$this->_writeObject($journalentry);
			
			foreach ($payments as $payment)
			{
				$this->_writeObject($payment);
			}
			
			return true;
		}
		return false;
	}
	
	protected function _sendCOGS($date,$shopName,$sales_data)
	{
		$cogs_inventory_lines = $this->_getCOGSInventoryLines($date,$shopName,$sales_data);
		
		if (count($cogs_inventory_lines)>0)
		{
			$journalentry = $this->_getIntuitAnywhereJournalEntry();
			$journalentry->Lines = $cogs_inventory_lines;
			$journalentry->HeaderTxnDate = new DateTime($date);
			$journalentry->HeaderNote = "COGS and inventory from MerchantOS on $date from $shopName";
			$this->_writeObject($journalentry);
			return true;
		}
		return false;
	}
	
	protected function _fillDaysWithSalesAndCOGS($start_date,$end_date)
	{
		$sales_by_tax_class = $this->_mos_accounting->getTaxClassSalesByDay($this->_startOfDay($start_date),$this->_endOfDay($end_date));
		
		if (!is_array($sales_by_tax_class))
		{
			return;
		}
		
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
		
		if (!is_array($discounts))
		{
			return;
		}
		
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
		
		if (!is_array($taxes_by_day))
		{
			return;
		}
		
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
		
		if (!is_array($payments))
		{
			return;
		}
		
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
				$balance += $this->_convertToFloat($sales_subtotal);
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
				$balance += $this->_convertToFloat($tax_subtotal);
			}
		}
		return $balance;
	}
	protected function _getDiscountsTotal($sales_data)
	{
		$balance = 0;
		if (isset($sales_data['discounts']))
		{
			$balance += $this->_convertToFloat($sales_data['discounts']);
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
				$balance += $this->_convertToFloat($payment_subtotal);
			}
		}
		return $balance;
	}
	
	protected function _getSaleLines($date,$shopName,$sales_data)
	{
		if (!isset($sales_data['sales']))
		{
			return array();
		}
		
		$lines = array();
		
		foreach ($sales_data['sales'] as $tax_class=>$sales_subtotal)
		{
			$line = $this->_getIntuitAnywhereJournalEntryLine();
			$line->AccountId = $this->_accounts_map['sales'];
			$line->Desc = "MerchantOS $tax_class sales on $date from $shopName";
			$line->Amount = $sales_subtotal;
			
			if (!$this->_prepareLineAmount($line,"Credit","Debit"))
			{
				continue;
			}
			
			$line->ClassId = $this->_getQBClassNamed($tax_class);
			
			$lines[] = $line;
		}
		
		
		return $lines;
	}
	
	protected function _getDiscountLines($date,$shopName,$sales_data)
	{
		if (!isset($sales_data['discounts']))
		{
			return array();
		}
		$line = $this->_getIntuitAnywhereJournalEntryLine();
		$line->AccountId = $this->_accounts_map['discounts'];
		$line->Desc = "MerchantOS discounts on $date from $shopName";
		$line->Amount = $this->_getDiscountsTotal($sales_data);
		
		if (!$this->_prepareLineAmount($line,"Debit","Credit"))
		{
			return array();
		}
		
		return array($line);
	}
	
	protected function _getTaxLines($date,$shopName,$sales_data)
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
			
			$line = $this->_getIntuitAnywhereJournalEntryLine();
			$line->AccountId = $this->_tax_accounts[$tax_vendor];
			$line->Desc = "MerchantOS $tax_vendor sales tax on $date from $shopName";
			$line->Amount = $tax_subtotal;
			
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
	protected function _getPaymentJournalEntryLines($date,$shopName,$sales_data)
	{
		if (!isset($sales_data['payments']))
		{
			return array();
		}
		
		$lines = array();
		$non_refund_total=0;
		
		// add lines for refund totals, they will not get done as payments
		foreach ($sales_data['payments'] as $payment_type=>$subtotal)
		{
			if ($subtotal>=0)
			{
				$non_refund_total += $subtotal;
				continue;
			}
			$line = $this->_getIntuitAnywhereJournalEntryLine();
			$line->AccountId = $this->_getQBAccountNamed(self::$_UNDEPOSITED_FUNDS);
			$line->Desc = "MerchantOS $payment_type refund on $date from $shopName";
			$line->Amount = $this->_formatAbsoluteAmount($subtotal);
			$line->PostingType = "Credit";
			$line->EntityId = $this->_getQBCustomerNamed(self::$_MOS_CUSTOMER);
			$line->EntityType = 'CUSTOMER';
			
			$lines[] = $line;
		}
		
		if ($non_refund_total <= 0)
		{
			return $lines;
		}
		// total of payments (non-refund) to accounts receivable
		$line = $this->_getIntuitAnywhereJournalEntryLine();
		$line->AccountId = $this->_accounts_map['accounts_receivable'];
		$line->Desc = "MerchantOS $payment_type payments on $date from $shopName";
		$line->Amount = $this->_formatAbsoluteAmount($non_refund_total);
		$line->PostingType = "Debit";
		$line->EntityId = $this->_getQBCustomerNamed(self::$_MOS_CUSTOMER);
		$line->EntityType = 'CUSTOMER';
		
		$lines[] = $line;
		
		return $lines;
	}
	
	protected function _getPayments($date,$shopName,$sales_data)
	{
		if (!isset($sales_data['payments']))
		{
			return array();
		}
		
		$payments = array();
		
		foreach ($sales_data['payments'] as $payment_type=>$subtotal)
		{
			if ($subtotal<=0)
			{
				continue;
			}
			$payment = $this->_getIntuitAnywherePayment();
			$payment->HeaderTxnDate = new DateTime($date);
			$payment->HeaderNote = "MerchantOS $payment_type payments on $date from $shopName";
			$payment->HeaderCustomerId = $this->_getQBCustomerNamed(self::$_MOS_CUSTOMER);
			$payment->HeaderPaymentMethodId = $this->_getQBPaymentMethodNamed($payment_type);
			$payment->HeaderTotalAmt = $this->_formatAbsoluteAmount($subtotal);
			
			$payments[] = $payment;
		}
		
		return $payments;
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
					$balance += $this->_convertToFloat($avg_cogs_subtotal);
				}
			}
		}
		else
		{
			if (isset($sales_data['fifo_cogs']))
			{
				foreach ($sales_data['fifo_cogs'] as $tax_class=>$fifo_cogs_subtotal)
				{
					$balance += $this->_convertToFloat($fifo_cogs_subtotal);
				}
			}
		}
		return $balance;
	}
	
	protected function _getCOGSInventoryLines($date,$shopName,$sales_data)
	{
		if (!isset($sales_data['sales']))
		{
			return array();
		}
		
		$avg_fifo = 'fifo_cogs';
		if ($this->_average_costing)
		{
			$avg_fifo = 'avg_cogs';
		}
		
		$lines = array();
		
		foreach ($sales_data[$avg_fifo] as $tax_class=>$subtotal)
		{
			if ($subtotal==0)
			{
				continue;
			}
			
			$ClassId = $this->_getQBClassNamed($tax_class);
			
			$inventory_line = $this->_getIntuitAnywhereJournalEntryLine();
			$inventory_line->AccountId = $this->_accounts_map['inventory'];
			$inventory_line->Desc = "MerchantOS $tax_class inventory sold on $date from $shopName";
			$inventory_line->Amount = $subtotal;
			$inventory_line->ClassId = $ClassId;
			$this->_prepareLineAmount($inventory_line,"Credit","Debit");
			
			$lines[] = $inventory_line;
			
			$cogs_line = $this->_getIntuitAnywhereJournalEntryLine();
			$cogs_line->AccountId = $this->_accounts_map['cogs'];
			$cogs_line->Desc = "MerchantOS $tax_class COGS on $date from $shopName";
			$cogs_line->Amount = $subtotal;
			$cogs_line->ClassId = $ClassId;
			$this->_prepareLineAmount($cogs_line,"Debit","Credit");
			
			$lines[] = $cogs_line;
		}
		
		return $lines;
	}
	
	protected function _formatSignedAmount($amount)
	{
		if (is_string($amount))
		{
			$amount = $this->_convertToFloat($amount);
		}
		return number_format(round($amount,2),2,".","");
	}
	
	protected function _formatAbsoluteAmount($amount)
	{
		if (is_string($amount))
		{
			$amount = $this->_convertToFloat($amount);
		}
		return number_format(abs(round($amount,2)),2,".","");
	}
	
	protected function _prepareLineAmount($line,$positivetype,$negativetype)
	{
		if (is_string($line->Amount))
		{
			$line->Amount = $this->_convertToFloat($line->Amount);
		}
		if ($line->Amount==0)
		{
			return false;
		}
		if ($line->Amount<0)
		{
			$line->PostingType = $negativetype;
		}
		else
		{
			$line->PostingType = $positivetype;
		}
		$line->Amount = $this->_formatAbsoluteAmount($line->Amount);
		return true;
	}
	
	protected function _convertToFloat($amount)
	{
		if (is_string($amount))
		{
			$amount = str_replace(",",".",$amount);
		}
		return (float)$amount;
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
		
		$ia_payment_method = $this->_getIntuitAnywherePaymentMethod();
		
		// search for an existing PaymentMethod
		$filters = array('Name'=>$name);
		
		$payment_methods = $ia_payment_method->listAll($filters,1);
		
		if (count($payment_methods)==1)
		{
			$this->_qb_payment_methods[$name] = $payment_methods[0];
			return $this->_qb_payment_methods[$name]->Id;
		}
		
		// couldn't find the PaymentMethod so we'll create one
		$ia_payment_method->Name = $name;
		$this->_writeObject($ia_payment_method);
		$this->_qb_payment_methods[$name] = $ia_payment_method;
		return $this->_qb_payment_methods[$name]->Id;
	}
	
	protected function _getQBCustomerNamed($name)
	{
		if (isset($this->_qb_customers[$name]))
		{
			return $this->_qb_customers[$name]->Id;
		}
		
		$ia_customer = $this->_getIntuitAnywhereCustomer();
		
		// search for an existing customer
		$filters = array('Name'=>$name);
		
		$customers = $ia_customer->listAll($filters,1);
		
		if (count($customers)==1)
		{
			$this->_qb_customers[$name] = $customers[0];
			return $this->_qb_customers[$name]->Id;
		}
		
		// couldn't find the customer so we'll create one
		$ia_customer->Name = $name;
		$this->_writeObject($ia_customer);
		$this->_qb_customers[$name] = $ia_customer;
		return $this->_qb_customers[$name]->Id;
	}
	
	protected function _getQBClassNamed($name)
	{
		if (isset($this->_qb_classes[$name]))
		{
			return $this->_qb_classes[$name]->Id;
		}
		
		$ia_class = $this->_getIntuitAnywhereClass();
		
		// search for an existing class
		$filters = array('Name'=>$name);
		
		$classes = $ia_class->listAll($filters,1);
		
		if (count($classes)==1)
		{
			$this->_qb_classes[$name] = $classes[0];
			return $this->_qb_classes[$name]->Id;
		}
		
		// couldn't find the class so we'll create one
		$ia_class->Name = $name;
		$this->_writeObject($ia_class);
		$this->_qb_classes[$name] = $ia_class;
		return $this->_qb_classes[$name]->Id;
	}
	
	protected function _getQBAccountNamed($name,$parent_account_id=null)
	{
		$cache_index = $name . $parent_account_id;
		if (isset($this->_qb_accounts[$cache_index]))
		{
			return $this->_qb_accounts[$cache_index]->Id;
		}
		
		$ia_account = $this->_getIntuitAnywhereAccount();
		
		// search for an existing account
		$filters = array('Name'=>$name);
		if (isset($parent_account_id))
		{
			$filters['AccountParentId'] = $parent_account_id;
		}
		
		$accounts = $ia_account->listAll($filters,1);
		
		if (count($accounts)==1)
		{
			$this->_qb_accounts[$name] = $accounts[0];
			return $this->_qb_accounts[$name]->Id;
		}
		
		// couldn't find the account so we'll create one
		$ia_account->Name = $name;
		if (isset($parent_account_id))
		{
			$ia_account->AccountParentId = $parent_account_id;
		}
		$this->_writeObject($ia_account);
		$this->_qb_accounts[$name] = $ia_account;
		return $this->_qb_accounts[$name]->Id;
	}
	
	protected function _getQBVendorNamed($name)
	{
		if (isset($this->_qb_vendors[$name]))
		{
			return $this->_qb_vendors[$name]->Id;
		}
		
		$ia_vendor = $this->_getIntuitAnywhereVendor();
		
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
		$this->_writeObject($ia_vendor);
		$this->_qb_vendors[$name] = $ia_vendor;
		return $this->_qb_vendors[$name]->Id;
	}
	
	protected function _getShopName($shopID)
	{
		if (!isset($this->_mos_shops_list))
		{
			$this->_mos_shops_list = $this->_mos_shop->listAll();
		}
		foreach ($this->_mos_shops_list as $shop)
		{
			if ($shop['shopID']==$shopID)
			{
				return $shop['name'];
			}
		}
		throw new Exception("Invalid MerchantOS Shop ID.");
	}
	
	protected function _writeObject($obj)
	{
		$obj->save();
		$this->_qb_objects[] = array('type'=>$obj->getType(),'id'=>$obj->Id);
	}
	
	
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_Bill
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereBill()
	{
		return new IntuitAnywhere_Bill($this->_i_anywhere);
	}
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_BillLine
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereBillLine()
	{
		return new IntuitAnywhere_BillLine($this->_i_anywhere);
	}
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_JournalEntryLine
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereJournalEntryLine()
	{
		return new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
	}
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_JournalEntry
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereJournalEntry()
	{
		return new IntuitAnywhere_JournalEntry($this->_i_anywhere);
	}
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_PaymentMethod
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywherePaymentMethod()
	{
		return new IntuitAnywhere_PaymentMethod($this->_i_anywhere);
	}
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_Payment
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywherePayment()
	{
		return new IntuitAnywhere_Payment($this->_i_anywhere);
	}
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_Customer
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereCustomer()
	{
		return new IntuitAnywhere_Customer($this->_i_anywhere);
	}
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_Class
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereClass()
	{
		return new IntuitAnywhere_Class($this->_i_anywhere);
	}
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_Account
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereAccount()
	{
		return new IntuitAnywhere_Account($this->_i_anywhere);
	}
	/**
	 * Override this when mocking so we don't have to communicate with IntuitAnywhere API live to test.
	 * @return IntuitAnywhere_Vendor
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereVendor()
	{
		return new IntuitAnywhere_Vendor($this->_i_anywhere);
	}
}

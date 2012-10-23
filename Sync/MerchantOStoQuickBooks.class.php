<?php

class Sync_MerchantOStoQuickBooks {
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
	 * @var array The data we are compiling to sync
	 */
	protected $_days_buffer;
	
	public function __construct($mos_accounting,$i_anywhere)
	{
		$this->_mos_accounting = $mos_accounting;
		$this->_i_anywhere = $i_anywhere;
		$this->_average_costing = true;
		$this->_send_sales = true;
		$this->_send_cogs = true;
		$this->_send_orders = true;
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
	
	/**
	 * Sync MerchantOS to QuickBooks
	 * @param DateTime $start_date The first date in the range to sync for
	 * @param DateTime $end_date The last date in the range to sync for
	 * @return array Array of date=>,msg=> for each day that was synced
	 */
	public function sync($start_date,$end_date)
	{
		$sales_cogs_logs = $this->_syncSalesCOGS($start_date,$end_date);
		$orders_logs = $this->_syncOrders($start_date,$end_date);
		return array_merge($sales_cogs_logs,$orders_logs);
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
			return array(array("date"=>date_format(new DateTime(),"Y-m-d")),"msg"=>"No records to sync.");
		}
		
		require_once("IntuitAnywhere/JournalEntry.class.php");
		
		$logs = array();
		foreach ($this->_days_buffer as $shopID=>$sales_day_shop)
		{
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
					$logs[] = array("date"=>$date,"msg"=>"Records contained unbalanced sales.");
					continue;
				}
				
				if ($this->_send_sales)
				{
					if ($this->_sendSales($date,$sales_data))
					{
						$logs[] = array("date"=>$date,"msg"=>"Sent $sales_total Sales, $discounts_total Discounts, $tax_total Tax, $payments_total Payments.");
					}
					else
					{
						$logs[] = array("date"=>$date,"msg"=>"$0 in Sales.");
					}
				}
				
				if ($this->_send_cogs)
				{
					$cogs_total = round($this->_getCOGSTotal($sales_data),2);
					if ($this->_sendCOGS($date,$sales_data))
					{
						$logs[] = array("date"=>$date,"msg"=>"Sent $cogs_total in COGS.");
					}
					else
					{
						$logs[] = array("date"=>$date,"msg"=>"$0 in COGS.");
					}
				}
			}
		}
		return $logs;
	}
	
	protected function _sendSales($date,$sales_data)
	{
		$sale_lines = $this->_getSaleLines($date,$sales_data);
		$discount_lines = $this->_getDiscountLines($date,$sales_data);
		$tax_lines = $this->_getTaxLines($date,$sales_data);
		$payment_lines = $this->_getPaymentLines($date,$sales_data);
		
		$lines = array_merge($sale_lines,$discount_lines,$tax_lines,$payment_lines);
		
		if (count($lines)>0)
		{
			$journalentry = new IntuitAnywhere_JournalEntry($this->_i_anywhere);
			$journalentry->Lines = $lines;
			$journalentry->HeaderTxnDate = new DateTime($date);
			$journalentry->HeaderNote = "Retail Sales from MerchantOS $date";
			$journalentry->save();
			return true;
		}
		return false;
	}
	
	protected function _sendCOGS($date,$sales_data)
	{
		$cogs_inventory_lines = $this->_getCOGSInventoryLines($date,$sales_data);
		
		if (count($cogs_inventory_lines)>0)
		{
			$journalentry = new IntuitAnywhere_JournalEntry($this->_i_anywhere);
			$journalentry->Lines = $cogs_inventory_lines;
			$journalentry->HeaderTxnDate = new DateTime($date);
			$journalentry->HeaderNote = "COGS and Inventory from MerchantOS $date";
			$journalentry->save();
			return true;
		}
		return false;
	}
	
	protected function _fillDaysWithSalesAndCOGS($start_date,$end_date)
	{
		$sales_by_tax_class = $this->_mos_accounting->getTaxClassSalesByDay($start_date->format('c'),$end_date->format('c'));
		
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
		$discounts = $this->_mos_accounting->getDiscountsByDay($start_date->format('c'),$end_date->format('c'));
		
		foreach ($discounts as $discount_day)
		{
			$shopID = (string)$discount_day->shopID;
			$date = (string)$discount_day->date;
			$this->_days_buffer[$shopID][$date]['discounts'] = (string)$discount_day->discount;
		}
	}
	
	protected function _fillDaysWithTax($start_date,$end_date)
	{
		$taxes_by_day = $this->_mos_accounting->getTaxesByDay($start_date->format('c'),$end_date->format('c'));
		
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
		$payments = $this->_mos_accounting->getPaymentsByDay($start_date->format('c'),$end_date->format('c'));
		
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
		$line = new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
		$line->AccountId = $this->_accounts_map['sales'];
		$line->Desc = "MerchantOS sales $date";
		$line->Amount = $this->_getSalesTotal($sales_data);
		/*foreach ($sales_data['sales'] as $tax_class=>$sales_subtotal)
		{
			$line->Amount += (float)$sales_subtotal;
		}*/
		
		if (!$this->_prepareLineAmount($line,"Credit","Debit"))
		{
			return array();
		}
		
		return array($line);
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
		/**
		 * @todo Need let them pick a tax vendor for each sales tax
		 */
		$line = new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
		$line->AccountId = $this->_accounts_map['tax'];
		$line->Desc = "MerchantOS sales tax $date";
		$line->Amount = $this->_getTaxTotal($sales_data);
		/*
		foreach ($sales_data['taxes'] as $tax_vendor=>$tax_subtotal)
		{
			$line->Amount += (float)$tax_subtotal;
		}
		*/
		if (!$this->_prepareLineAmount($line,"Credit","Debit"))
		{
			return array();
		}
		
		return array($line);
	}
	
	protected function _getPaymentLines($date,$sales_data)
	{
		if (!isset($sales_data['payments']))
		{
			return array();
		}
		
		$line = new IntuitAnywhere_JournalEntryLine($this->_i_anywhere);
		$line->AccountId = $this->_accounts_map['payments'];
		$line->Desc = "MerchantOS payments $date";
		$line->Amount = $this->_getPaymentsTotal($sales_data);
		/*
		foreach ($sales_data['payments'] as $payment_type=>$payment_subtotal)
		{
			$payment_line->Amount += (float)$payment_subtotal;
		}
		*/
		if (!$this->_prepareLineAmount($line,"Debit","Credit"))
		{
			return array();
		}
		
		return array($line);
	}
	
	protected function _getCOGSTotal($sales_data)
	{
		$balance = 0;
		if ($this->_average_costing)
		{
			foreach ($sales_data['avg_cogs'] as $tax_class=>$avg_cogs_subtotal)
			{
				$balance += (float)$avg_cogs_subtotal;
			}
		}
		else
		{
			foreach ($sales_data['fifo_cogs'] as $tax_class=>$fifo_cogs_subtotal)
			{
				$balance += (float)$fifo_cogs_subtotal;
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
}

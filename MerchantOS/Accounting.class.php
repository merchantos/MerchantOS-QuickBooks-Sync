<?php

require_once("MerchantOS/MOSAPICall.class.php");

class MerchantOS_Accounting
{
	/**
	 * @var MOSAPICall
	 */
	private $_mosapi;
	
	public function __construct($apikey)
	{
		$this->_mosapi = new MOSAPICall($apikey);
	}
	
	public function getTaxClassSalesByDay($start,$end)
	{
		return $mosapi->makeAPICall("Account.Reports.Accounting.TaxClassSalesByDay","Read",null,null,"xml","startDate=$start&endDate=$end");
	}
	
	public function getDiscountsByDay($start,$end)
	{
		return $mosapi->makeAPICall("Account.Reports.Accounting.DiscountsByDay","Read",null,null,"xml","startDate=$start&endDate=$end");
	}
	
	public function getTaxesByDay($start,$end)
	{
		return $mosapi->makeAPICall("Account.Reports.Accounting.TaxesByDay","Read",null,null,"xml","startDate=$start&endDate=$end");
	}
	
	public function getPaymentsByDay($start,$end)
	{
		return $mosapi->makeAPICall("Account.Reports.Accounting.PaymentsByDay","Read",null,null,"xml","startDate=$start&endDate=$end");
	}
	
	public function getOrdersByTaxClass($start,$end)
	{
		return $mosapi->makeAPICall("Account.Reports.Accounting.OrdersByTaxClass","Read",null,null,"xml","startDate=$start_date&endDate=$end_date");
	}
}

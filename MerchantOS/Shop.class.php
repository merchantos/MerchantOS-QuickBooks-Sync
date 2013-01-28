<?php

require_once("MerchantOS/MOSAPICall.class.php");

class MerchantOS_Shop
{
	/*
	 * @var MOSAPICall
	 */
	private $_mosapi;
	
	function __construct($apikey,$account_num=null)
	{
		$this->_mosapi = new MOSAPICall($apikey,$account_num);
	}
	
	function listAll()
	{
		$shops = array();
		
		$xml = $this->_mosapi->makeAPICall("Account.Shop","Get");
		
		foreach ($xml as $shopxml)
		{
			$shops[] = array(
				"shopID"=>(integer)$shopxml->shopID,
				"name"=>(string)$shopxml->name
			);
		}
		return $shops;
	}
	
	function updateName($shopID,$name)
	{
	    $xml = "<Shop><name>".htmlentities($name)."</name></Shop>";
		$shop = $this->_mosapi->makeAPICall("Account.Shop","Update",$xml);
		return $shop;
	}
}

<?php

require_once("MerchantOS/MOSAPICall.class.php");

class MerchantOS_Shop
{
	/*
	 * @var MOSAPICall
	 */
	private $_mosapi;
	
	function __construct($apikey)
	{
		$this->_mosapi = new MOSAPICall($apikey);
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
}

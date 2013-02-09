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
	
	function update($shopID,$fields)
	{
	    $xml = new SimpleXMLElement("<Shop></Shop>");
		foreach ($fields as $key=>$value)
		{
			if (is_array($value))
			{
				if ($key=="Address")
				{
					$contact = $xml->addChild("Contact");
					$addresses = $contact->addChild("Addresses");
					$contact_address = $addresses->addChild("ContactAddress");
					$contact_address->addChild("address1",$value['line1']);
					$contact_address->addChild("address2",$value['line2']);
					$contact_address->addChild("city",$value['city']);
					$contact_address->addChild("state",$value['state']);
					$contact_address->addChild("zip",$value['zip']);
				}
				continue;
			}
			$xml->addChild($key,htmlentities($value));
		}
		$shop = $this->_mosapi->makeAPICall("Account.Shop","Update",$xml->asXML());
		return $shop;
	}
}

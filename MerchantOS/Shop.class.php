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
				"name"=>(string)$shopxml->name,
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
				if ($key=="address")
				{
					$contact = $xml->addChild("Contact");
					$addresses = $contact->addChild("Addresses");
					$contact_address = $addresses->addChild("ContactAddress");
					$contact_address->addChild("address1",htmlentities($value['line1']));
					$contact_address->addChild("address2",htmlentities($value['line2']));
					$contact_address->addChild("city",htmlentities($value['city']));
					$contact_address->addChild("state",htmlentities($value['state']));
					$contact_address->addChild("zip",htmlentities($value['zip']));
				}
				continue;
			}
			$xml->addChild($key,htmlentities($value));
		}
		$shop = $this->_mosapi->makeAPICall("Account.Shop","Update",$shopID,$xml->asXML());
		return $shop;
	}
}

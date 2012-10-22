<?php

require_once("MerchantOS/MOSAPICall.class.php");

class MerchantOS_Option
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
		$options = array();
		
		$xml = $this->_mosapi->makeAPICall("Account.Option","Get");
		
		foreach ($xml as $optionxml)
		{
			$options[(string)$optionxml->name] = (string)$optionxml->value;
		}
		return $options;
	}
}

<?php

require_once("MerchantOS/MOSAPICall.class.php");

class MerchantOS_TaxCategory
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
		$tax_cats = array();
		
		$xml = $this->_mosapi->makeAPICall("Account.TaxCategory","Get");
		
		foreach ($xml as $taxcatxml)
		{
			$name = (string)$taxcatxml->tax1Name;
			if (isset($taxcatxml->tax2Name) && strlen(trim((string)$taxcatxml->tax2Name))>0)
			{
				$name .= "/" . (string)$taxcatxml->tax2Name;
			}
			$tax_cats[] = array(
				"taxCategoryID"=>(integer)$taxcatxml->taxCategoryID,
				"name"=>$name
			);
		}
		return $tax_cats;
	}
}

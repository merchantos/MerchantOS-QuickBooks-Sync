<?php

require_once("MerchantOS/MOSAPICall.class.php");

class MerchantOS_SystemAPIKeyAccess
{
	/*
	 * @var MOSAPICall
	 */
	private $_mosapi;
	
	function __construct($apikey,$account_num=null)
	{
		$this->_mosapi = new MOSAPICall($apikey,$account_num);
	}
	
	function create($systemAPIKeyID)
	{
	    $xml = "<SystemAPIKeyAccess>
    <controlName>employee:all</controlName>
    <canCreate>true</canCreate>
    <canRead>true</canRead>
    <canUpdate>true</canUpdate>
    <canDelete>true</canDelete>
    <systemAPIKeyID></systemAPIKeyID>
  </SystemAPIKeyAccess>";
		$simple_xml = new SimpleXMLElement($xml);
		$simple_xml->systemAPIKeyID = (integer)$systemAPIKeyID;
	    
		$response = $this->_mosapi->makeAPICall("SystemAPIKeyAccess","Create",null,$simple_xml->asXML());
		
		return $response;
	}
}

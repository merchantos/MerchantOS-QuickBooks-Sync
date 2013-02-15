<?php

require_once("MerchantOS/MOSAPICall.class.php");

class MerchantOS_SystemAPIKey
{
	/*
	 * @var MOSAPICall
	 */
	private $_mosapi;
	
	function __construct($apikey,$account_num=null)
	{
		$this->_mosapi = new MOSAPICall($apikey,$account_num);
	}
	
	function create($systemCustomerID,$systemUserID,$systemAPIClientID)
	{
	    $xml = "<SystemAPIKey>
    <systemCustomerID></systemCustomerID>
    <systemUserID></systemUserID>
    <systemAPIClientID></systemAPIClientID>
  </SystemAPIKey>";
		$simple_xml = new SimpleXMLElement($xml);
		$simple_xml->systemCustomerID = (integer)$systemCustomerID;
		$simple_xml->systemUserID = (integer)$systemUserID;
		$simple_xml->systemAPIClientID = (integer)$systemAPIClientID;
	    
		$response = $this->_mosapi->makeAPICall("SystemAPIKey","Create",null,$simple_xml->asXML());
		
		return $response;
	}
}

<?php

require_once("MerchantOS/MOSAPICall.class.php");

class MerchantOS_SystemOpenID
{
	/*
	 * @var MOSAPICall
	 */
	private $_mosapi;
	
	function __construct($apikey,$account_num=null)
	{
		$this->_mosapi = new MOSAPICall($apikey,$account_num);
	}
	
	function create($systemUserID,$openid)
	{
	    $xml = "<SystemOpenID>
    <systemUserID></systemUserID>
    <openid></openid>
  </SystemOpenID>";
		$simple_xml = new SimpleXMLElement($xml);
		$simple_xml->systemUserID = (integer)$systemUserID;
		$simple_xml->systemAPIClientID = (integer)$openid;
	    
		$response = $this->_mosapi->makeAPICall("SystemOpenID","Create",null,$simple_xml->asXML());
		
		return $response;
	}
}

<?php

require_once('MerchantOS/MOScURL.class.php');

class MOSAPICall
{
	protected $_mos_api_url = MOS_API_URL;
	
	protected $_api_key;
	protected $_account_num;
	
	public function __construct($api_key,$account_num=null)
	{
		$this->_api_key = $api_key;
		$this->_account_num = $account_num;
	}
	
	protected function _queryAccountNum()
	{
		$xml = $this->makeAPICall("Session","Get");
		$this->_account_num = (integer)$xml->systemCustomerID;
	}
	
	public function makeAPICall($controlname,$action,$unique_id=null,$xml=null,$emitter="xml",$query_str=false)
	{
		$custom_request = "GET";
		switch ($action)
		{
			case "Create":
				$custom_request = "POST";
				break;
			case "Read":
				$custom_request = "GET";
				break;
			case "Update":
				$custom_request = "PUT";
				break;
			case "Delete":
				$custom_request = "DELETE";
				break;
		}
		$curl = new MOScURL();
		$curl->setBasicAuth($this->_api_key,'apikey');
		$curl->setVerifyPeer(false);
		$curl->setVerifyHost(0);
		$curl->setCustomRequest($custom_request);
		
		if (stripos($controlname,"Account.")!==false)
		{
			if (!isset($this->_account_num))
			{
				throw new Exception("MerchantOS Account ID needs to be specified to call account controls.");
			}
			$controlname = str_replace("Account.","Account." . $this->_account_num . ".",$controlname);
		}
		
		$control_url = $this->_mos_api_url . str_replace(".","/",$controlname);
		if (isset($unique_id))
		{
			$control_url .= "/" . $unique_id;
		}
		
		if ($query_str)
		{
			$control_url .= "." . $emitter . "?" . $query_str;
		}
		else
		{
			$control_url .= "." . $emitter;
		}
		
		if (is_object($xml))
		{
			$xml = $xml->asXML();
		}
		
		return self::_makeCall($curl,$control_url,$xml);
	}
	
	protected static function _makeCall($curl,$url,$xml)
	{
		$result = $curl->call($url,$xml);
		
		try
		{
			$result_simplexml = new SimpleXMLElement($result);
		}
		catch (Exception $e)
		{
			throw new Exception("MerchantOS API Call Error: " . $e->getMessage() . ", Response: " . $result);
		}
		
		if (!is_object($result_simplexml))
		{
			throw new Exception("MerchantOS API Call Error: Could not parse XML, Response: " . $result);
		}
		
		return $result_simplexml;
	}
}

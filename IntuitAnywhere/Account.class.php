<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_Account extends IntuitAnywhere_DataModel
{
	public $Name;
	public $Desc;
	public $Subtype;
	public $AcctNum;
	public $CurrentBalance;
	public $AccountParentId;
	
	protected function _getQBOObjectName() { return "account"; }
	protected function _getQBOObjectNamePlural() { return "accounts"; }
	protected function _getQBDObjectName() { return "account"; }
	protected function _getQBDObjectNamePlural() { return "accounts"; }
	
	protected function _loadFromQBOXML($xml)
	{
		$this->Id = (integer)$xml->Id;
		$this->CreateTime = new DateTime((string)$xml->MetaData->CreateTime);
		$this->LastUpdatedTime = new DateTime((string)$xml->MetaData->LastUpdatedTime);
		
		$this->Name = (string)$xml->Name;
		$this->Desc = (string)$xml->Desc;
		$this->Subtype = (string)$xml->Subtype;
		$this->AcctNum = (string)$xml->AcctNum;
		$this->CurrentBalance = (string)$xml->CurrentBalance;
		$this->AccountParentId = (integer)$xml->AccountParentId;
	}
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("Account::load from QBD XML, not implemented.");
	}
	
	protected function _getXMLForQBO()
	{
		throw new Exception("Account::get QBO XML, not implemented.");
	}
	protected function _getXMLForQBD()
	{
		throw new Exception("Account::get QBD XML, not implemented.");
	}
}

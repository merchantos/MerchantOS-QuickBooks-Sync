<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_Account extends IntuitAnywhere_DataModel
{
	public $Name;
	public $Desc;
	public $Subtype;
	public $AcctNum;
	public $CurrentBalance;
	
	protected function _getQBOObjectName() { return "account"; }
	protected function _getQBOObjectNamePlural() { return "accounts"; }
	protected function _getQBDObjectName() { return "account"; }
	protected function _getQBDObjectNamePlural() { return "accounts"; }
	
	protected function _loadFromQBOXML($xml)
	{
		$account = new IntuitAnywhere_Account($this->ia);
		$account->Id = (integer)$xml->Id;
		$account->CreateTime = new DateTime((string)$xml->MetaData->CreateTime);
		$account->LastUpdatedTime = new DateTime((string)$xml->MetaData->LastUpdatedTime);
		
		$account->Name = (string)$xml->Name;
		$account->Desc = (string)$xml->Desc;
		$account->AcctNum = (string)$xml->AcctNum;
		$account->CurrentBalance = (string)$xml->CurrentBalance;
	}
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("Account::load from QBD XML, not implemented.");
	}
}

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
	
	protected function _getXMLForQBDDelete()
	{
		throw new Exception("Account::get XML for QBD delete, not implemented.");
	}
	
	protected function _getXMLForQBODelete()
	{
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<Account xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">';
		$xml .= "<Id>" . $this->Id . "</Id>";
		$xml .= "<SyncToken>" . $this->SyncToken . "</SyncToken>";
		$xml .= '</Account>';
		return $xml;
	}
	
	protected function _getXMLForQBO()
	{
		if ($this->Id>0)
		{
			throw new Exception("Account::update for QBO not implemented.");
		}
		
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<Account xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">';
		$xml .= "<Name>" . htmlentities($this->Name) . "</Name>";
		$xml .= "<Subtype>" . $this->Subtype . "</Subtype>";
		if (isset($this->Desc))
		{
			$xml .= "<Desc>" . htmlentities($this->Desc) . "</Desc>";
		}
		if (isset($this->AcctNum))
		{
			$xml .= "<AcctNum>" . $this->AcctNum . "</AcctNum>";
		}
		if (isset($this->AccountParentId))
		{
			$xml .= "<AccountParentId>" . $this->AccountParentId . "</AccountParentId>";
		}
		if (isset($this->OpeningBalance))
		{
			$xml .= "<OpeningBalance>" . $this->OpeningBalance . "</OpeningBalance>";
		}
		if (isset($this->OpeningBalanceDate))
		{
			$OpeningBalanceDate = $this->OpeningBalanceDate->format('Y-m-d-H:i:s');
			$xml .= "<OpeningBalanceDate>" . $OpeningBalanceDate . "</OpeningBalanceDate>";
		}
		$xml .= "</Account>";
		return $xml;
	}
	protected function _getXMLForQBD()
	{
		throw new Exception("Account::get QBD XML, not implemented.");
	}
}

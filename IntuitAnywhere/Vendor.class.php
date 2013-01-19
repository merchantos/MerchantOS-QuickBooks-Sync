<?php

require_once("IntuitAnywhere/EntityBase.class.php");

class IntuitAnywhere_Vendor extends IntuitAnywhere_EntityBase
{
	public $AcctNum;
	public $Vendor1099;
	
	protected function _getQBOObjectName() { return "vendor"; }
	protected function _getQBOObjectNamePlural() { return "vendors"; }
	protected function _getQBDObjectName() { return "vendor"; }
	protected function _getQBDObjectNamePlural() { return "vendors"; }
	
	protected function _loadFromQBOXML($xml)
	{
		parent::_loadFromQBOXML($xml);
		
		$this->AcctNum = (string)$xml->AcctNum;
		$this->Vendor1099 = (string)$xml->Vendor1099;
	}
	
	protected function _getXMLForQBDDelete()
	{
		throw new Exception("Vendor::get XML for QBD delete, not implemented.");
	}
	
	protected function _getXMLForQBODelete()
	{
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<Vendor xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">';
		$xml .= "<Id>" . $this->Id . "</Id>";
		$xml .= "<SyncToken>" . $this->SyncToken . "</SyncToken>";
		$xml .= '</Vendor>';
		return $xml;
	}
	
	protected function _getXMLForQBO()
	{
		if ($this->Id>0)
		{
			throw new Exception("Vendor::update for QBO not implemented.");
		}
		
		$base_xml = parent::_getXMLForQBO();
		
		$my_xml = "";
		if (isset($this->AcctNum))
		{
			$my_xml .= "<AcctNum>" . $this->AcctNum . "</AcctNum>";
		}
		if (isset($this->Vendor1099))
		{
			$my_xml .= "<Vendor1099>" . $this->Vendor1099 . "</Vendor1099>";
		}
		
		return '<?xml version="1.0" encoding="utf-8"?>' .
					'<Vendor xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">' .
					$base_xml .
					$my_xml .
					'</Vendor>';
	}
}

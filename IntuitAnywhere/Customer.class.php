<?php

require_once("IntuitAnywhere/EntityBase.class.php");

class IntuitAnywhere_Customer extends IntuitAnywhere_EntityBase
{
	protected function _getQBOObjectName() { return "customer"; }
	protected function _getQBOObjectNamePlural() { return "customers"; }
	protected function _getQBDObjectName() { return "customer"; }
	protected function _getQBDObjectNamePlural() { return "customers"; }
	
	protected function _loadFromQBOXML($xml)
	{
		parent::_loadFromQBOXML($xml);
	}
	
	protected function _getXMLForQBDDelete()
	{
		throw new Exception("Customer::get XML for QBD delete, not implemented.");
	}
	
	protected function _getXMLForQBODelete()
	{
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<Customer xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">';
		$xml .= "<Id>" . $this->Id . "</Id>";
		$xml .= "<SyncToken>" . $this->SyncToken . "</SyncToken>";
		$xml .= '</Customer>';
		return $xml;
	}
	
	protected function _getXMLForQBO()
	{
		$base_xml = parent::_getXMLForQBO();
		
		return '<?xml version="1.0" encoding="utf-8"?>' .
					'<Customer xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">' .
					$base_xml .
					'</Customer>';
	}
}

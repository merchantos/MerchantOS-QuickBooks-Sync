<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_PaymentMethod extends IntuitAnywhere_DataModel
{
	public $Name;
	public $Type='NON_CREDIT_CARD';
	
	protected function _getQBOObjectName() { return "payment-method"; }
	protected function _getQBOObjectNamePlural() { return "payment-methods"; }
	protected function _getQBDObjectName() { return "payment-method"; }
	protected function _getQBDObjectNamePlural() { return "payment-methods"; }
	
	protected function _loadFromQBOXML($xml)
	{
		/*
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<PaymentMethod xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:ns2="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:ns3="http://www.intuit.com/sb/cdm/qbo">
<Id>8</Id>
<SyncToken>0</SyncToken>
<Name>Wire Transfer</Name>
<Type>NON_CREDIT_CARD</Type>
</PaymentMethod>
	*/
		$this->Name = (string)$xml->Name;
		$this->Type = (string)$xml->Type;
		
		return;
	}
	
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("PaymentMethod::load from QBD XML, not implemented.");
	}
	
	protected function _getXMLForQBO()
	{
		/*
<?xml version="1.0" encoding="UTF 8" standalone="yes" ?>
<PaymentMethod xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:ns2="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:ns3="http://www.intuit.com/sb/cdm/qbo">
<Id>8</Id>
<SyncToken>0</SyncToken>
<Name>Wire Transfer</Name>
<Type>NON_CREDIT_CARD</Type>
</PaymentMethod>
*/
		if ($this->Id>0)
		{
			throw new Exception("PaymentMethod::update for QBO not implemented.");
		}
		
		$xml = "";
		
		// required
		$xml .= "<Name>" . $this->Name . "</Name>";
		$xml .= "<Type>" . $this->Type . "</Type>";
		
		return <<<PAYMENTQBOCREATE
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<PaymentMethod xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
	$xml
</PaymentMethod>
PAYMENTQBOCREATE;
	}
	
	protected function _getXMLForQBD()
	{
		throw new Exception("PaymentMethod::get QBD XML, not implemented.");
	}
}

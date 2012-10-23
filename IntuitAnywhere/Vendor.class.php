<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_Bill extends IntuitAnywhere_DataModel
{
	public $Name;
	public $Desc;
	public $Subtype;
	public $AcctNum;
	public $CurrentBalance;
	public $AccountParentId;
	
	protected function _getQBOObjectName() { return "vendor"; }
	protected function _getQBOObjectNamePlural() { return "vendors"; }
	protected function _getQBDObjectName() { return "vendor"; }
	protected function _getQBDObjectNamePlural() { return "vendors"; }
	
	protected function _loadFromQBOXML($xml)
	{
		/*
		 *<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<SearchResults xmlns="http://www.intuit.com/sb/cdm/qbo" xmlns:ns2="http://www.intuit.com/sb/cdm/v2" xmlns:ns3="http://www.intuit.com/sb/cdm/qbopayroll/v1">
<CdmCollections xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ns2:Vendors">
<ns2:Vendor>
<ns2:Id>6</ns2:Id>
<ns2:SyncToken>1</ns2:SyncToken>
<ns2:MetaData>
<ns2:CreateTime>2010-09-13T01:52:36-07:00</ns2:CreateTime>
<ns2:LastUpdatedTime>2010-09-13T01:56:13-07:00</ns2:LastUpdatedTime>
</ns2:MetaData>
<ns2:Name>Digital</ns2:Name>
<ns2:Address>
<ns2:Line1>Park Avenue</ns2:Line1>
<ns2:Line2></ns2:Line2>
<ns2:City>San Francisco</ns2:City>
<ns2:CountrySubDivisionCode>CA</ns2:CountrySubDivisionCode>
<ns2:PostalCode>91367</ns2:PostalCode>
</ns2:Address>
<ns2:Phone>
<ns2:FreeFormNumber>(818) 436-8225</ns2:FreeFormNumber>
</ns2:Phone>
<ns2:WebSite>
<ns2:URI>http://www.digitalinsight.mint.com/</ns2:URI>
</ns2:WebSite>
<ns2:Email>
<ns2:Address>john_doe@digitalinsight.mint.com</ns2:Address>
</ns2:Email>
<ns2:GivenName>John</ns2:GivenName>
<ns2:FamilyName>Doe</ns2:FamilyName>
<ns2:DBAName>Digital</ns2:DBAName>
<ns2:AcctNum>9001</ns2:AcctNum>
<ns2:Vendor1099>true</ns2:Vendor1099>
</ns2:Vendor>
</CdmCollections>
<Count>1</Count>
<CurrentPage>1</CurrentPage>
</SearchResults>
		*/
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
		throw new Exception("Vendor::load from QBD XML, not implemented.");
	}
	
	protected function _getXMLForQBO()
	{
		/*
		 *<?xml version="1.0" encoding="utf-8"?>
<Vendor xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">
<TypeOf>Person</TypeOf>
<Name>Digital</Name>
<Address>
<Line1>Park Avenue</Line1>
<City>Woodland Hills</City>
<CountrySubDivisionCode>CA</CountrySubDivisionCode>
<PostalCode>91367</PostalCode>
</Address>
<Phone>
<FreeFormNumber>(818) 436-8225</FreeFormNumber>
</Phone>
<WebSite>
<URI>http://www.digitalinsight.mint.com/</URI>
</WebSite>
<Email>
<Address>john_doe@digitalinsight.mint.com</Address>
</Email>
<GivenName>John</GivenName>
<FamilyName>Doe</FamilyName>
<DBAName>Digital</DBAName>
<TaxIdentifier>12-1234567</TaxIdentifier>
<AcctNum>9001</AcctNum>
<Vendor1099>true</Vendor1099>
</Vendor>
*/
		throw new Exception("Vendor::get QBO XML, not implemented.");
	}
	protected function _getXMLForQBD()
	{
		throw new Exception("Vendor::get QBD XML, not implemented.");
	}
}

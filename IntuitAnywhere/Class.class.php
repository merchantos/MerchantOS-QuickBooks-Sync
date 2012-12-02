<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_Class extends IntuitAnywhere_DataModel
{
	public $Name;
	public $ClassParentId;
	
	protected function _getQBOObjectName() { return "class"; }
	protected function _getQBOObjectNamePlural() { return "classes"; }
	protected function _getQBDObjectName() { return "class"; }
	protected function _getQBDObjectNamePlural() { return "classes"; }
	
	protected function _loadFromQBOXML($xml)
	{
		/*
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<Class xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
  <Id idDomain="QBO">103</Id>
  <SyncToken>0</SyncToken>
  <MetaData>
    <CreateTime>2011-08-12T10:21:20-07:00</CreateTime>
    <LastUpdatedTime>2011-08-12T10:21:20-07:00</LastUpdatedTime>
  </MetaData>
  <Name>dqYhyVws37</Name>
  <ClassParentId idDomain="QBO">102</ClassParentId>
</Class>
	*/
		$this->Name = (string)$xml->Name;
		$this->ClassParentId = (integer)$xml->ClassParentId;
		
		return;
	}
	
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("Class::load from QBD XML, not implemented.");
	}
	
	protected function _getXMLForQBO()
	{
		/*
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<Class xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
  <Name>dqYhyVws37X</Name>
  <ClassParentId idDomain="QBO">102</ClassParentId>
</Class>
*/
		if ($this->Id>0)
		{
			throw new Exception("Class::update for QBO not implemented.");
		}
		
		$xml = "";
		
		// required
		$xml .= "<Name>" . $this->Name . "</Name>";
		$xml .= "<ClassParentId>" . $this->ClassParentId . "</ClassParentId>";
		
		return <<<CLASSQBOCREATE
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Class xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
	$xml
</Class>
CLASSQBOCREATE;
	}
	
	protected function _getXMLForQBD()
	{
		throw new Exception("Class::get QBD XML, not implemented.");
	}
}

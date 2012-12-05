<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_BillLine extends IntuitAnywhere_DataModel
{
	public $Desc;
	public $Amount;
	public $ClassId;
	public $AccountId;
	
	protected function _getQBOObjectName() { new Exception("BillLine can not be queried individually."); }
	protected function _getQBOObjectNamePlural() { new Exception("BillLine can not be queried individually."); }
	protected function _getQBDObjectName() { new Exception("BillLine can not be queried individually."); }
	protected function _getQBDObjectNamePlural() { new Exception("BillLine can not be queried individually."); }
	
	public function loadFromQBOXML($xml)
	{
		return $this->_loadFromQBOXML($xml);
	}
	protected function _loadFromQBOXML($xml)
	{
		$this->Desc = (string)$xml->Desc;
		$this->Amount = (float)$xml->Amount;
		$this->ClassId = (integer)$xml->ClassId;
		$this->AccountId = (integer)$xml->AccountId;
	}
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("BillLine::load from QBD XML, not implemented.");
	}
	
	public function getXMLForQBO()
	{
		return $this->_getXMLForQBO();
	}
	protected function _getXMLForQBO()
	{
		$desc = $this->Desc;
		$amount = number_format(round($this->Amount,2),2);
		$classid = $this->ClassId;
		$accountid = $this->AccountId;
		return <<<BILLLINEQBO
<Line>
	<Desc>$desc</Desc>
	<Amount>$amount</Amount>
	<ClassId>$classid</ClassId>
	<AccountId>$accountid</AccountId>
</Line>
BILLLINEQBO;
	}
	
	public function getXMLForQBD()
	{
		return $this->_getXMLForQBD();
	}
	protected function _getXMLForQBD()
	{
		throw new Exception("BillLine::get QBD XML, not implemented.");
	}
}

class IntuitAnywhere_Bill extends IntuitAnywhere_DataModel
{
	public $HeaderTxnDate;
	public $HeaderDocNumber;
	public $HeaderMsg;
	public $HeaderVendorId;
	/**
	 * @var array Array of IntuitAnywhere_BillLine
	 */
	public $Lines;
	
	protected function _getQBOObjectName() { return "bill"; }
	protected function _getQBOObjectNamePlural() { return "bills"; }
	protected function _getQBDObjectName() { return "bill"; }
	protected function _getQBDObjectNamePlural() { return "bills"; }
	
	protected function _loadFromQBOXML($xml)
	{
		/*
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Bill xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:ns2="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:ns3="http://www.intuit.com/sb/cdm/qbo">
<Id>56</Id>
<SyncToken>0</SyncToken>
<MetaData>
<CreateTime>2010-09-13T21:40:41-07:00</CreateTime>
<LastUpdatedTime>2010-09-13T21:40:41-07:00</LastUpdatedTime>
</MetaData>
<Header>
<DocNumber>2004</DocNumber>
<TxnDate>2010-08-06-07:00</TxnDate>
<VendorId>7</VendorId>
<TotalAmt>25.00</TotalAmt>
<SalesTermId>5</SalesTermId>
<DueDate>2010-08-11-07:00</DueDate>
<Balance>25.00</Balance>
</Header>
<Line>
<Id>1</Id>
<Desc>Pens</Desc>
<Amount>25.00</Amount>
<ItemId>2</ItemId>
<UnitPrice>5</UnitPrice>
<Qty>5</Qty>
<AccountId>30</AccountId>
</Line>
</Bill>
	*/
		$this->HeaderTxnDate = new DateTime((string)$xml->Header->TxnDate);
		$this->HeaderDocNumber = (string)$xml->Header->DocNumber;
		$this->HeaderMsg = (string)$xml->Header->Msg;
		$this->HeaderVendorId = (integer)$xml->Header->VendorId;
		
		$this->Lines = array();
		foreach ($xml->Line as $linexml)
		{
			$line = new IntuitAnywhere_BillLine($this->ia);
			$line->loadFromQBOXML($linexml);
			$this->Lines[] = $line;
		}
		
		return;
	}
	
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("Bill::load from QBD XML, not implemented.");
	}
	
	protected function _getXMLForQBO()
	{
		/*
<?xml version="1.0" encoding="utf-8"?>
<Bill xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">
<Header>
<DocNumber>2004</DocNumber>
<TxnDate>2010-08-06</TxnDate>
<Msg>4 Pens</Msg>
<VendorId>7</VendorId>
<VendorName>Digital</VendorName>
<TotalAmt>50</TotalAmt>
<SalesTermId>5</SalesTermId>
<SalesTermName>Due before receipt</SalesTermName>
<DueDate>2010-08-11</DueDate>
</Header>
<Line>
<Desc>Pens</Desc>
<Amount>25</Amount>
<BillableStatus>NotBillable</BillableStatus>
<Qty>5</Qty>
<UnitPrice>5</UnitPrice>
<ItemId>2</ItemId>
</Line>
</Bill>
*/
		if ($this->Id>0)
		{
			throw new Exception("Bill::update for QBO not implemented.");
		}
		$lines = "";
		foreach ($this->Lines as $billline)
		{
			$lines .= $billline->getXMLForQBO();
		}
		$txndate = $this->HeaderTxnDate->format('Y-m-d-H:i:s');
		$msg = $this->HeaderMsg;
		$vendorid = $this->HeaderVendorId;
		$docnum = "";
		if (isset($this->HeaderDocNumber))
		{
			$docnum = "<DocNumber>" . $this->HeaderDocNumber . "</DocNumber>";
		}
		return <<<BILLQBOCREATE
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Bill xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
	<Header>
		<TxnDate>$txndate</TxnDate>
		<Msg>$msg</Msg>
		<VendorId>$vendorid</VendorId>
		$docnum
	</Header>
	$lines
</Bill>
BILLQBOCREATE;
	}
	
	protected function _getXMLForQBD()
	{
		throw new Exception("Bill::get QBD XML, not implemented.");
	}
}

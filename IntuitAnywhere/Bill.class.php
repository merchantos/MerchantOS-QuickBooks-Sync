<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_BillLine extends IntuitAnywhere_DataModel
{
	public $Desc;
	public $Amount;
	public $PostingType;
	public $AccountId;
	public $EntityId;
	
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
		$this->PostingType = (string)$xml->PostingType;
		$this->AccountId = (integer)$xml->AccountId;
		$this->EntityId = (integer)$xml->EntityId;
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
		$postingtype = $this->PostingType;
		$accountid = $this->AccountId;
		$entity = "";
		if (isset($this->EntityId))
		{
			$entity = '<EntityId idDomain="QBO">' . $this->EntityId . '</EntityId>';
		}
		return <<<BILLLINEQBO
<Line>
	<Desc>$desc</Desc>
	<Amount>$amount</Amount>
	<PostingType>$postingtype</PostingType>
	<AccountId idDomain="QBO">$accountid</AccountId>
	$entity
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
	public $HeaderNote;
	public $HeaderAdjustment = "false";
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
		 *<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<SearchResults xmlns="http://www.intuit.com/sb/cdm/qbo" xmlns:ns2="http://www.intuit.com/sb/cdm/v2" xmlns:ns3="http://www.intuit.com/sb/cdm/qbopayroll/v1">
<CdmCollections xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ns2:Bills">
<ns2:Bill>
<ns2:Id>56</ns2:Id>
<ns2:SyncToken>1</ns2:SyncToken>
<ns2:MetaData>
<ns2:CreateTime>2010-09-13T21:40:41-07:00</ns2:CreateTime>
<ns2:LastUpdatedTime>2010-09-13T21:44:43-07:00</ns2:LastUpdatedTime>
</ns2:MetaData>
<ns2:Header>
<ns2:DocNumber>2004</ns2:DocNumber>
<ns2:TxnDate>2010-08-06-07:00</ns2:TxnDate>
<ns2:VendorId>7</ns2:VendorId>
<ns2:TotalAmt>25.00</ns2:TotalAmt>
<ns2:SalesTermId>5</ns2:SalesTermId>
<ns2:DueDate>2010-08-11-07:00</ns2:DueDate>
<ns2:Balance>25.00</ns2:Balance>
</ns2:Header>
<ns2:Line>
<ns2:Id>1</ns2:Id>
<ns2:Desc>Pens</ns2:Desc>
<ns2:Amount>25.00</ns2:Amount>
<ns2:ItemId>2</ns2:ItemId>
<ns2:UnitPrice>5</ns2:UnitPrice>
<ns2:Qty>7</ns2:Qty>
<ns2:AccountId>30</ns2:AccountId>
</ns2:Line>
</ns2:Bill>
</CdmCollections>
<Count>1</Count>
<CurrentPage>1</CurrentPage>
</SearchResults>
	*/
		$this->Adjustment = (string)$xml->Header->Adjustment;
		$this->Note = (string)$xml->Header->Note;
		$this->HeaderTxnDate = new DateTime((string)$xml->Header->TxnDate);
		$this->Lines = array();
		foreach ($xml->Line as $linexml)
		{
			$line = new IntuitAnywhere_BillLine($this->ia);
			$line->loadFromQBOXML($xml);
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
		 *<?xml version="1.0" encoding="utf-8"?>
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
		foreach ($this->Lines as $jeline)
		{
			$lines .= $jeline->getXMLForQBO();
		}
		$txndate = $this->HeaderTxnDate->format('Y-m-d-H:i:s');
		$note = $this->HeaderNote;
		$adjustment = $this->HeaderAdjustment;
		return <<<BILLQBOCREATE
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Bill xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
	<Header>
		<TxnDate>$txndate</TxnDate>
		<Note>$note</Note>
		<Adjustment>$adjustment</Adjustment>
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

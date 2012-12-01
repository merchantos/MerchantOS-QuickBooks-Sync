<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_Payment extends IntuitAnywhere_DataModel
{
	public $HeaderDocNumber;
	public $HeaderTxnDate;
	public $HeaderNote;
	public $HeaderCustomerId;
	public $HeaderDepositToAccountId;
	public $HeaderPaymentMethodId;
	public $HeaderTotalAmt;
	// readonly
	public $HeaderPaymentMethodName;
	// not used right now
	public $HeaderProcessPayment = 'false';
	/**
	 * @var array Array of IntuitAnywhere_BillLine
	 */
	public $Lines;
	
	protected function _getQBOObjectName() { return "payment"; }
	protected function _getQBOObjectNamePlural() { return "payments"; }
	protected function _getQBDObjectName() { return "payment"; }
	protected function _getQBDObjectNamePlural() { return "payments"; }
	
	protected function _loadFromQBOXML($xml)
	{
		/*
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Payment xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:ns2="http://www.intuit.com/sb/cdm/qbopayroll/v1"
xmlns:ns3="http://www.intuit.com/sb/cdm/qbo">
<Id>47</Id>
<SyncToken>0</SyncToken>
<MetaData>
<CreateTime>2010-09-14T05:33:24-07:00</CreateTime>
<LastUpdatedTime>2010-09-14T05:33:24-07:00</LastUpdatedTime>
</MetaData>
<Header>
<DocNumber>54</DocNumber>
<TxnDate>2010-08-09-07:00</TxnDate>
<Note>Payment against Invoice</Note>
<CustomerId>5</CustomerId>
<DepositToAccountId>41</DepositToAccountId>
<PaymentMethodId>1</PaymentMethodId>
<TotalAmt>20.00</TotalAmt>
<ProcessPayment>false</ProcessPayment>
</Header>
<Line>
<Amount>20.00</Amount>
<TxnId>8</TxnId>
</Line>
</Payment>
	*/
		$this->HeaderDocNumber = (string)$xml->Header->DocNumber;
		$this->HeaderTxnDate = new DateTime((string)$xml->Header->TxnDate);
		$this->HeaderNote = (string)$xml->Header->Note;
		$this->HeaderCustomerId = (integer)$xml->Header->CustomerId;
		$this->HeaderDepositToAccountId = (integer)$xml->Header->DepositToAccountId;
		$this->HeaderPaymentMethodId = (integer)$xml->Header->PaymentMethodId;
		$this->HeaderTotalAmt = (float)$xml->Header->TotalAmt;
		$this->HeaderPaymentMethodName = (string)$xml->Header->PaymentMethodName;
		
		//public $HeaderProcessPayment = 'false';
		
		// can have lines but we aren't using them for now
		
		return;
	}
	
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("Payment::load from QBD XML, not implemented.");
	}
	
	protected function _getXMLForQBO()
	{
		/*
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Payment xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:ns2="http://www.intuit.com/sb/cdm/qbopayroll/v1"
xmlns:ns3="http://www.intuit.com/sb/cdm/qbo">
<Header>
<DocNumber>54</DocNumber>
<TxnDate>2010-08-09-07:00</TxnDate>
<Note>Payment against Invoice</Note>
<CustomerId>5</CustomerId>
<DepositToAccountId>41</DepositToAccountId>
<PaymentMethodId>1</PaymentMethodId>
<TotalAmt>20.00</TotalAmt>
<ProcessPayment>false</ProcessPayment>
</Header>
<Line>
<Amount>20.00</Amount>
<TxnId>8</TxnId>
</Line>
</Payment>
*/
		if ($this->Id>0)
		{
			throw new Exception("Payment::update for QBO not implemented.");
		}
		
		$header_xml = "<Header>";
		
		// required
		$header_xml .= "<CustomerId>" . $this->HeaderCustomerId . "</CustomerId>";
		
		// optional
		if (isset($this->HeaderDocNumber))
		{
			$header_xml .= "<DocNumber>" . $this->HeaderDocNumber . "</DocNumber>";
		}
		if (isset($this->HeaderTxnDate))
		{
			$txndate = $this->HeaderTxnDate->format('Y-m-d-H:i:s');
			$header_xml .= "<TxnDate>" . $txndate . "</TxnDate>";
		}
		if (isset($this->HeaderNote))
		{
			$header_xml .= "<Note>" . $this->HeaderNote . "</Note>";
		}
		if (isset($this->HeaderDepositToAccountId))
		{
			$header_xml .= "<DepositToAccountId>" . $this->HeaderDepositToAccountId . "</DepositToAccountId>";
		}
		if (isset($this->HeaderPaymentMethodId))
		{
			$header_xml .= "<PaymentMethodId>" . $this->HeaderPaymentMethodId . "</PaymentMethodId>";
		}
		if (isset($this->HeaderTotalAmt))
		{
			$header_xml .= "<TotalAmt>" . $this->HeaderTotalAmt . "</TotalAmt>";
		}
		
		$header_xml .= "</Header>";
		
		return <<<PAYMENTQBOCREATE
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Payment xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
	$header_xml
</Payment>
PAYMENTQBOCREATE;
	}
	
	protected function _getXMLForQBD()
	{
		throw new Exception("Payment::get QBD XML, not implemented.");
	}
}

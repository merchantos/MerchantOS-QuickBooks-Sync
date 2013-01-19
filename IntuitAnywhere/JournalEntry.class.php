<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_JournalEntryLine extends IntuitAnywhere_DataModel
{
	public $Desc;
	public $Amount;
	public $PostingType;
	public $AccountId;
	public $EntityId;
	public $EntityType;
	public $ClassId;
	
	protected function _getQBOObjectName() { new Exception("JournalEntryLine can not be queried individually."); }
	protected function _getQBOObjectNamePlural() { new Exception("JournalEntryLine can not be queried individually."); }
	protected function _getQBDObjectName() { new Exception("JournalEntryLine can not be queried individually."); }
	protected function _getQBDObjectNamePlural() { new Exception("JournalEntryLine can not be queried individually."); }
	
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
		$this->ClassId = (integer)$xml->ClassId;
	}
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("JournalEntryLine::load from QBD XML, not implemented.");
	}
	
	protected function _getXMLForQBDDelete()
	{
		throw new Exception("JournalEntryLine::Can not be deleted individually.");
	}
	
	protected function _getXMLForQBODelete()
	{
		throw new Exception("JournalEntryLine::Can not be deleted individually.");
	}
	
	public function getXMLForQBO()
	{
		return $this->_getXMLForQBO();
	}
	protected function _getXMLForQBO()
	{
		$desc = htmlentities($this->Desc);
		$amount = $this->Amount;
		$postingtype = $this->PostingType;
		$accountid = $this->AccountId;
		$entity = "";
		if (isset($this->EntityId))
		{
			$entity = '<EntityId idDomain="QBO">' . $this->EntityId . '</EntityId><EntityType>' . $this->EntityType . '</EntityType>';
		}
		$class = "";
		if (isset($this->ClassId))
		{
			$class = '<ClassId idDomain="QBO">' . $this->ClassId . '</ClassId>';
		}
		return <<<JOURNALENTRYLINEQBO
<Line>
	<Desc>$desc</Desc>
	<Amount>$amount</Amount>
	<PostingType>$postingtype</PostingType>
	<AccountId idDomain="QBO">$accountid</AccountId>
	$entity
	$class
</Line>
JOURNALENTRYLINEQBO;
	}
	
	public function getXMLForQBD()
	{
		return $this->_getXMLForQBD();
	}
	protected function _getXMLForQBD()
	{
		throw new Exception("JournalEntryLine::get QBD XML, not implemented.");
	}
}

class IntuitAnywhere_JournalEntry extends IntuitAnywhere_DataModel
{
	public $HeaderTxnDate;
	public $HeaderNote;
	public $HeaderAdjustment = "false";
	/**
	 * @var array Array of IntuitAnywhere_JournalEntryLine
	 */
	public $Lines;
	
	protected function _getQBOObjectName() { return "journal-entry"; }
	protected function _getQBOObjectNamePlural() { return "journal-entries"; }
	protected function _getQBDObjectName() { return "journal-entry"; }
	protected function _getQBDObjectNamePlural() { return "journal-entries"; }
	
	protected function _loadFromQBOXML($xml)
	{
		$this->Adjustment = (string)$xml->Header->Adjustment;
		$this->Note = (string)$xml->Header->Note;
		$this->HeaderTxnDate = new DateTime((string)$xml->Header->TxnDate);
		$this->Lines = array();
		foreach ($xml->Line as $linexml)
		{
			$line = new IntuitAnywhere_JournalEntryLine($this->ia);
			$line->loadFromQBOXML($linexml);
			$this->Lines[] = $line;
		}
		
		return;
	}
	
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("JournalEntry::load from QBD XML, not implemented.");
	}
	
	protected function _getXMLForQBDDelete()
	{
		throw new Exception("Customer::get XML for QBD delete, not implemented.");
	}
	
	protected function _getXMLForQBODelete()
	{
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<JournalEntry xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">';
		$xml .= "<Id>" . $this->Id . "</Id>";
		$xml .= "<SyncToken>" . $this->SyncToken . "</SyncToken>";
		$xml .= '</JournalEntry>';
		return $xml;
	}
	
	protected function _getXMLForQBO()
	{
		if ($this->Id>0)
		{
			throw new Exception("JournalEntry::update for QBO not implemented.");
		}
		$lines = "";
		foreach ($this->Lines as $jeline)
		{
			$lines .= $jeline->getXMLForQBO();
		}
		$txndate = $this->HeaderTxnDate->format('Y-m-d-H:i:s');
		$note = htmlentities($this->HeaderNote);
		$adjustment = $this->HeaderAdjustment;
		return <<<JOURNALENTRYQBOCREATE
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<JournalEntry xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
	<Header>
		<TxnDate>$txndate</TxnDate>
		<Note>$note</Note>
		<Adjustment>$adjustment</Adjustment>
	</Header>
	$lines
</JournalEntry>
JOURNALENTRYQBOCREATE;
	}
	
	protected function _getXMLForQBD()
	{
		throw new Exception("JournalEntry::get QBD XML, not implemented.");
	}
}

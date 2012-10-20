<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_JournalEntryLine extends IntuitAnywhere_DataModel
{
	public $Id;
	public $Desc;
	public $Amount;
	public $PostingType;
	public $AccountId;
	public $EntityId;
	
	protected function _getQBOObjectName() { new Exception("JournalEntryLine can not be queried individually."); }
	protected function _getQBOObjectNamePlural() { new Exception("JournalEntryLine can not be queried individually."); }
	protected function _getQBDObjectName() { new Exception("JournalEntryLine can not be queried individually."); }
	protected function _getQBDObjectNamePlural() { new Exception("JournalEntryLine can not be queried individually."); }
	
	protected function _loadFromQBOXML($xml)
	{
		throw new Exception("JournalEntryLine::load from QBO XML, not implemented.");
	}
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("JournalEntryLine::load from QBD XML, not implemented.");
	}
	
	public function getXMLForQBO()
	{
		return $this->_getXMLForQBO();
	}
	protected function _getXMLForQBO()
	{
		$desc = $this->Desc;
		$amount = $this->Amount;
		$postingtype = $this->PostingType;
		$accountid = $this->AccountId;
		$entity = "";
		if (isset($this->EntityId))
		{
			$entity = '<EntityId idDomain="QBO">' . $this->EntityId . '</EntityId>';
		}
		return <<<JOURNALENTRYLINEQBO
<Line>
	<Desc>$desc</Desc>
	<Amount>$amount</Amount>
	<PostingType>$postingtype</PostingType>
	<AccountId idDomain="QBO">$accountid</AccountId>
	$entity
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
		throw new Exception("JournalEntry::load from QBO XML, not implemented.");
	}
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception("JournalEntry::load from QBD XML, not implemented.");
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
			$lines[] = $jeline->getXMLForQBO();
		}
		$txndate = $this->HeaderTxnDate->format('Y-m-d-H:i:s');
		$note = $this->HeaderNote;
		return <<<JOURNALENTRYQBOCREATE
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<JournalEntry xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
	<Header>
		<TxnDate>$txndate</TxnDate>
		<Note>$note</Note>
		<Adjustment>false</Adjustment>
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

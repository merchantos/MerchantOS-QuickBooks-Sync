<?php

require_once("IntuitAnywhere/DataModel.class.php");

class IntuitAnywhere_EntityComponent extends IntuitAnywhere_DataModel
{
	protected function _getQBOObjectName() { new Exception(get_class($this) . " can not be queried individually."); }
	protected function _getQBOObjectNamePlural() { new Exception(get_class($this) . " can not be queried individually."); }
	protected function _getQBDObjectName() { new Exception(get_class($this) . " can not be queried individually."); }
	protected function _getQBDObjectNamePlural() { new Exception(get_class($this) . " can not be queried individually."); }
	
	public function loadFromQBOXML($xml)
	{
		return $this->_loadFromQBOXML($xml);
	}
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception(get_class($this) . "::load from QBD XML, not implemented.");
	}
	protected function _loadFromQBOXML($xml)
	{
		throw new Exception(get_class($this) . "::load from QBO XML, not implemented.");
	}
	protected function _getXMLForQBDDelete()
	{
		throw new Exception("EntityComponent::Can not be deleted individually.");
	}
	protected function _getXMLForQBODelete()
	{
		throw new Exception("EntityComponent::Can not be deleted individually.");
	}
	public function getXMLForQBO()
	{
		return $this->_getXMLForQBO();
	}
	public function getXMLForQBD()
	{
		return $this->_getXMLForQBD();
	}
	protected function _getXMLForQBD()
	{
		throw new Exception(get_class($this) . "::get QBD XML, not implemented.");
	}
	protected function _getXMLForQBO()
	{
		throw new Exception(get_class($this) . "::get QBO XML, not implemented.");
	}
}

class IntuitAnywhere_EntityPhone extends IntuitAnywhere_EntityComponent
{
	public $FreeFormNumber;
	public $DeviceType;
	
	protected function _loadFromQBOXML($xml)
	{
		$this->FreeFormNumber = (string)$xml->FreeFormNumber;
		$this->DeviceType = (string)$xml->DeviceType;
	}
	
	protected function _getXMLForQBO()
	{
		$xml = "<Phone><FreeFormNumber>" . $this->FreeFormNumber . "</FreeFormNumber>";
		if (isset($this->DeviceType))
		{
			$xml .= '<DeviceType>' . $this->DeviceType . '</DeviceType>';
		}
		$xml .= "</Phone>";
		return $xml;
	}
}

class IntuitAnywhere_EntityAddress extends IntuitAnywhere_EntityComponent
{
	public $Line1;
	public $Line2;
	public $Line3;
	public $Line4;
	public $Line5;
	public $City;
	public $CountrySubDivisionCode;
	public $PostalCode;
	public $Tag;
	
	protected function _loadFromQBOXML($xml)
	{
		$this->Line1 = (string)$xml->Line1;
		$this->Line2 = (string)$xml->Line2;
		$this->Line3 = (string)$xml->Line3;
		$this->Line4 = (string)$xml->Line4;
		$this->Line5 = (string)$xml->Line5;
		$this->City = (string)$xml->City;
		$this->CountrySubDivisionCode = (string)$xml->CountrySubDivisionCode;
		$this->PostalCode = (string)$xml->PostalCode;
		$this->Tag = (string)$xml->Tag;
	}
	
	protected function _getXMLForQBO()
	{
		$xml = "<Address>";
		if (isset($this->Line1))
		{
			$xml .= '<Line1>' . htmlentities($this->Line1) . '</Line1>';
		}
		if (isset($this->Line2))
		{
			$xml .= '<Line2>' . htmlentities($this->Line2) . '</Line2>';
		}
		if (isset($this->Line3))
		{
			$xml .= '<Line3>' . htmlentities($this->Line3) . '</Line3>';
		}
		if (isset($this->Line4))
		{
			$xml .= '<Line4>' . htmlentities($this->Line4) . '</Line4>';
		}
		if (isset($this->Line5))
		{
			$xml .= '<Line5>' . htmlentities($this->Line5) . '</Line5>';
		}
		if (isset($this->City))
		{
			$xml .= '<City>' . htmlentities($this->City) . '</City>';
		}
		if (isset($this->CountrySubDivisionCode))
		{
			$xml .= '<CountrySubDivisionCode>' . $this->CountrySubDivisionCode . '</CountrySubDivisionCode>';
		}
		if (isset($this->PostalCode))
		{
			$xml .= '<PostalCode>' . $this->PostalCode . '</PostalCode>';
		}
		if (isset($this->Tag))
		{
			$xml .= '<Tag>' . htmlentities($this->Tag) . '</Tag>';
		}
		$xml .= "</Address>";
		return $xml;
	}
}

class IntuitAnywhere_EntityWebSite extends IntuitAnywhere_EntityComponent
{
	public $URI;
	
	protected function _loadFromQBOXML($xml)
	{
		$this->URI = (string)$xml->URI;
	}
	
	protected function _getXMLForQBO()
	{
		return "<WebSite><URI>" . htmlentities($this->URI) . "</URI></WebSite>";
	}
}

class IntuitAnywhere_EntityEmail extends IntuitAnywhere_EntityComponent
{
	public $Address;
	
	protected function _loadFromQBOXML($xml)
	{
		$this->Address = (string)$xml->Address;
	}
	
	protected function _getXMLForQBO()
	{
		return "<Email><Address>" . htmlentities($this->Address) . "</Address></Email>";
	}
}

abstract class IntuitAnywhere_EntityBase extends IntuitAnywhere_DataModel
{
	public $Name;
	public $GivenName;
	public $MiddleName;
	public $FamilyName;
	public $Suffix;
	public $Gender;
	public $BirthDate;
	public $DBAName;
	public $TaxIdentifier;
	public $ShowAs;
	public $SalesTermId;
	public $OpenBalanceAmount;
	/**
	 * @var array Array of IntuitAnywhere_EntityPhone
	 */
	public $Phones;
	/**
	 * @var array Array of IntuitAnywhere_EntityAddress
	 */
	public $Addresses;
	/**
	 * @var array Array of IntuitAnywhere_EntityWebSite
	 */
	public $WebSites;
	/**
	 * @var array Array of IntuitAnywhere_EntityEmail
	 */
	public $Emails;
	
	/**
	 * need to implement these in the class that extends EntityBase
	 *
	protected function _getQBOObjectName() { return "journal-entry"; }
	protected function _getQBOObjectNamePlural() { return "journal-entries"; }
	protected function _getQBDObjectName() { return "journal-entry"; }
	protected function _getQBDObjectNamePlural() { return "journal-entries"; }
	 */
	
	protected function _loadFromQBOXML($xml)
	{
		$this->Name = (string)$xml->Name;
		$this->GiveName = (string)$xml->GiveName;
		$this->MiddleName = (string)$xml->MiddleName;
		$this->FamilyName = (string)$xml->FamilyName;
		$this->Suffix = (string)$xml->Suffix;
		if (isset($xml->BirthDate))
		{
			$this->BirthDate = new DateTime((string)$xml->BirthDate);
		}
		$this->DBAName = (string)$xml->DBAName;
		
		$this->TaxIdentifier = (string)$xml->TaxIdentifier;
		$this->ShowAs = (string)$xml->ShowAs;
		$this->SalesTermId = (integer)$xml->SalesTermId;
		if (isset($xml->OpenBalance))
		{
			$this->OpenBalanceAmount = (float)$xml->OpenBalance->Amount;
		}
		
		if (isset($xml->Phone))
		{
			$this->Phones = array();
			foreach ($xml->Phone as $phonexml)
			{
				$line = new IntuitAnywhere_EntityPhone($this->ia);
				$line->loadFromQBOXML($phonexml);
				$this->Phones[] = $line;
			}
		}
		if (isset($xml->Address))
		{
			$this->Addresses = array();
			foreach ($xml->Address as $addressxml)
			{
				$line = new IntuitAnywhere_EntityAddress($this->ia);
				$line->loadFromQBOXML($addressxml);
				$this->Addresses[] = $line;
			}
		}
		if (isset($xml->WebSite))
		{
			$this->WebSites = array();
			foreach ($xml->WebSite as $websitexml)
			{
				$line = new IntuitAnywhere_EntityWebSite($this->ia);
				$line->loadFromQBOXML($websitexml);
				$this->WebSites[] = $line;
			}
		}
		if (isset($xml->Email))
		{
			$this->Email = array();
			foreach ($xml->Email as $emailxml)
			{
				$line = new IntuitAnywhere_EntityWebSite($this->ia);
				$line->loadFromQBOXML($emailxml);
				$this->Email[] = $line;
			}
		}
		
		return;
	}
	
	
	protected function _loadFromQBDXML($xml)
	{
		throw new Exception(get_class($this) . "::load from QBD XML, not implemented.");
	}
	
	protected function _getXMLForQBDDelete()
	{
		throw new Exception("EntityBase::Can not delete because subclass (" . get_class($this) . ") does not define the delete method.");
	}
	protected function _getXMLForQBODelete()
	{
		throw new Exception("EntityBase::Can not delete because subclass (" . get_class($this) . ") does not define the delete method.");
	}
	/**
	 * When you extend this you need to add the XMl wrapper information:
	 * 
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<... xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
</...>
	 */
	protected function _getXMLForQBO()
	{
		if ($this->Id>0)
		{
			throw new Exception(get_class($this) . "::update for QBO not implemented.");
		}
		$extras = "";
		if (isset($this->Phones))
		{
			foreach ($this->Phones as $phone)
			{
				$extras .= $phone->getXMLForQBO();
			}
		}
		if (isset($this->Addresses))
		{
			foreach ($this->Addresses as $address)
			{
				$extras .= $address->getXMLForQBO();
			}
		}
		if (isset($this->WebSites))
		{
			foreach ($this->WebSites as $website)
			{
				$extras .= $website->getXMLForQBO();
			}
		}
		if (isset($this->Emails))
		{
			foreach ($this->Emails as $email)
			{
				$extras .= $email->getXMLForQBO();
			}
		}
		
		$xml = "";
		if (isset($this->BirthDate))
		{
			$birthdate = $this->BirthDate->format('Y-m-d-H:i:s');
			$xml .= "<BirthDate>$birthdate</BirthDate>";
		}
		if (isset($this->Name))
		{
			$xml .= "<Name>" . htmlentities($this->Name) . "</Name>";
		}
		if (isset($this->GiveName))
		{
			$xml .= "<GiveName>" . htmlentities($this->GiveName) . "</GiveName>";
		}
		if (isset($this->MiddleName))
		{
			$xml .= "<MiddleName>" . htmlentities($this->MiddleName) . "</MiddleName>";
		}
		if (isset($this->FamilyName))
		{
			$xml .= "<FamilyName>" . htmlentities($this->FamilyName) . "</FamilyName>";
		}
		if (isset($this->Suffix))
		{
			$xml .= "<Suffix>" . htmlentities($this->Suffix) . "</Suffix>";
		}
		if (isset($this->TaxIdentifier))
		{
			$xml .= "<TaxIdentifier>" . $this->TaxIdentifier . "</TaxIdentifier>";
		}
		if (isset($this->ShowAs))
		{
			$xml .= "<ShowAs>" . htmlentities($this->ShowAs) . "</ShowAs>";
		}
		
		if (isset($this->SalesTermId))
		{
			$xml .= "<SalesTermId>" . $this->SalesTermId . "</SalesTermId>";
		}
		//OpenBalanceAmount can't be written (I'm think!)
		
		return $xml . $extras;
	}
	
	protected function _getXMLForQBD()
	{
		throw new Exception(get_class($this) . "::get QBD XML, not implemented.");
	}
}

<?php

require_once("IntuitAnywhere/EntityBase.class.php");

class IntuitAnywhere_CompanyMetaData extends IntuitAnywhere_EntityBase
{
	public $CompanyName;
	
	/**
	 * Get company phone number. Extra access function to make getting the phone number for the company easier
	 * @return string The phone number for the company, so we can use it in account creation etc.
	 */
	public function getCompanyPhone()
	{
		if (isset($this->Phones) && isset($this->Phones[0]))
		{
			return $this->Phones[0]->FreeFormNumber;
		}
		return null;
	}
	/**
	 * Get company email. Extra access function to make getting the email for the company easier
	 * @return string The email for the company, so we can use it in account creation etc.
	 */
	public function getCompanyEmail()
	{
		if (!isset($this->Emails) || count($this->Emails)<=0)
		{
			return null;
		}
		foreach ($this->Emails as $email)
		{
			if ($email->Tag == "COMPANY_EMAIL")
			{
				return $email->Address;
			}
		}
		if (isset($this->Emails[0]))
		{
			return $this->Emails[0]->Address;
		}
		return null;
	}
	/**
	 * Get company address. Extra access function to make getting the address for the company easier
	 * @return array The address for the company in form array("line1"=>value,"line2"=>value,"city"=>value,"state"=>value,"zip"=>value), so we can use it in account creation etc.
	 */
	public function getCompanyAddress()
	{
		if (!isset($this->Addresses) || count($this->Addresses)<=0)
		{
			return null;
		}
		foreach ($this->Addresses as $address)
		{
			if ($address->Tag == "COMPANY_ADDRESS")
			{
				return self::_convertAddressToArray($address);
			}
		}
		if (isset($this->Addresses[0]))
		{
			return self::_convertAddressToArray($this->Addresses[0]);
		}
		return null;
	}
	protected static function _convertAddressToArray($address)
	{
		return array(
			"line1"=>$address->Line1,
			"line2"=>$address->Line2,
			"city"=>$address->City,
			"state"=>$address->CountrySubDivisionCode,
			"zip"=>$address->PostalCode
		);
	}
	
	protected function _getQBOObjectName() { return "companymetadata"; }
	protected function _getQBOObjectNamePlural() { return "companymetadata"; }
	protected function _getQBDObjectName() { return "companymetadata"; }
	protected function _getQBDObjectNamePlural() { return "companymetadata"; }
	
	/**
	 * We have to override this because querying for multiple objects just returns the single CompanyMetaData object
	 */
	protected function _listAllQBO($filters,$limit=null)
	{
		$result = $this->ia->query($this->_getQBOObjectName(),null,"GET");
		
		$xml = new SimpleXMLElement($result);
		
		$object = new IntuitAnywhere_CompanyMetaData($this->ia);
		$object->_loadFromQBOXML($xml);
		return array($object);
	}
	
	protected function _loadQBO()
	{
		throw new Exception("Can not load CompanyMetaData by Id.");
	}
	
	protected function _loadFromQBOXML($xml)
	{
		$this->CompanyName = (string)$xml->QBNRegisteredCompanyName;
		parent::_loadFromQBOXML($xml);
	}
	
	protected function _getXMLForQBDDelete()
	{
		throw new Exception("Customer::get XML for QBD delete, not implemented.");
	}
	
	protected function _getXMLForQBODelete()
	{
		throw new Exception("CompanyMetaData can not be deleted.");
	}
	
	protected function _getXMLForQBO()
	{
		throw new Exception("CompanyMetadata can not be created or updated.");
		/*
		$base_xml = parent::_getXMLForQBO();
		
		return '<?xml version="1.0" encoding="utf-8"?>' .
					'<CompanyMetaData xmlns:ns2="http://www.intuit.com/sb/cdm/qbo" xmlns="http://www.intuit.com/sb/cdm/v2">' .
					$base_xml .
					'</CompanyMetaData>';
		*/
	}
}

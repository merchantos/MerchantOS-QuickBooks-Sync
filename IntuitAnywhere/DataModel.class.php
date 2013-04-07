<?php

abstract class IntuitAnywhere_DataModel
{
	public $Id;
	public $SyncToken;
	public $CreateTime;
	public $LastUpdatedTime;
	/**
	 * @var IntuitAnywhere
	 */
	protected $ia;
	
	abstract protected function _getQBOObjectName();
	abstract protected function _getQBOObjectNamePlural();
	abstract protected function _getQBDObjectName();
	abstract protected function _getQBDObjectNamePlural();
	
	abstract protected function _loadFromQBOXML($xml);
	abstract protected function _loadFromQBDXML($xml);
	
	abstract protected function _getXMLForQBODelete();
	abstract protected function _getXMLForQBDDelete();
	abstract protected function _getXMLForQBO();
	abstract protected function _getXMLForQBD();
	
	function __construct($intuit_anywhere_obj)
	{
		$this->ia = $intuit_anywhere_obj;
	}
	
	function getType()
	{
		return $this->_getQBDObjectName();
	}
	
	function load()
	{
		if ($this->ia->isQBO())
		{
			return $this->_loadQBO();
		}
		return $this->_loadQBD();
	}
	function listAll($filters=null,$limit=null)
	{
		if ($this->ia->isQBO())
		{
			return $this->_listAllQBO($filters,$limit);
		}
		return $this->_listAllDBD($filters,$limit);
	}
	function save()
	{
		if ($this->ia->isQBO())
		{
			return $this->_saveQBO();
		}
		return $this->_saveQBD();
	}
	function delete()
	{
		if ($this->ia->isQBO())
		{
			return $this->_deleteQBO();
		}
		return $this->_deleteDBD();
	}
	
	protected function _listAllDBD($filters,$limit=null)
	{
		throw new Exception("_listAllDBD: not implemented.");
	}
	
	protected function _saveQBD()
	{
		throw new Exception("_saveQBD: not implemented.");
	}
	
	protected function _loadQBD()
	{
		throw new Exception("_loadQBD: not implemented.");
	}
	
	protected function _deleteQBD($id)
	{
		throw new Exception("_deleteQBD: not implemented.");
	}
	
	protected function _saveQBO()
	{
		$xml = $this->_getXMLForQBO();
		
		$id = null;
		if (isset($this->Id) && $this->Id > 0)
		{
			$id = $this->Id;
		}
		$result = $this->ia->query($this->_getQBOObjectName(),$id,"POST",null,$xml);
		
		$result_xml = new SimpleXMLElement($result);
		
		if (isset($result_xml->ErrorCode) && (integer)$result_xml->ErrorCode>0)
		{
			throw new Exception((string)$xml->ErrorMessage,(integer)$xml->ErrorCode);
		}
		
		$this->_loadStandardQBOXML($result_xml);
		$this->_loadFromQBOXML($result_xml);
	}
	
	protected function _loadQBO()
	{
		if (!isset($this->Id) || $this->Id <= 0)
		{
			throw new Exception("Can not load an object without an Id.");
		}
		$result = $this->ia->query($this->_getQBOObjectName(),$this->Id,"GET");
		
		$xml = new SimpleXMLElement($result);
		
		if (isset($xml->ErrorCode) && (integer)$xml->ErrorCode>0)
		{
			throw new Exception((string)$xml->ErrorMessage,(integer)$xml->ErrorCode);
		}
		
		$this->_loadStandardQBOXML($xml);
		$this->_loadFromQBOXML($xml);
	}
	
	protected function _deleteQBO()
	{
		if (!isset($this->Id) || $this->Id <= 0)
		{
			throw new Exception("Can not delete an object without an Id.");
		}
		if (!isset($this->SyncToken))
		{
			// need to load the object so we have the correct SyncToken before deleting
			$this->load();
		}
		
		$xml = $this->_getXMLForQBODelete();
		
		if (!$this->ia->query($this->_getQBDObjectName(),$this->Id,"POST","methodx=delete",$xml))
		{
			return false;
		}
		return true;
	}
	
	protected function _listAllQBO($filters,$limit=null)
	{
		$page = 1;
		$per_page = 100;
		$objects = array();
		
		if (isset($limit) && $per_page>$limit)
		{
			$per_page = $limit;
		}
		
		$body = null;
		$params = array();
		$params["PageNum"] = $page;
		$params["ResultsPerPage"] = $per_page;
		
		if (isset($filters) && count($filters)>0)
		{
			foreach ($filters as $key=>$value)
			{
				$params['Filter'] = $key . " :EQUALS: " . $value;
			}
		}
		
		$classname = get_class($this);
		
		$total = 0;
		while (true)
		{
			$result = $this->ia->query($this->_getQBDObjectNamePlural(),null,"POST",$params,$body);
			
			$xml = new SimpleXMLElement($result);
			
			if (isset($xml->ErrorCode) && (integer)$xml->ErrorCode>0)
			{
				throw new Exception((string)$xml->ErrorMessage,(integer)$xml->ErrorCode);
			}
			
			$namespaces = $xml->getNamespaces(true);
			$qbo_xml = $xml->children($namespaces["qbo"]);
			
			$count = (integer)$qbo_xml->Count;
			$total += $count;
			if ($count==0)
			{
				break;
			}
			
			foreach ($qbo_xml->CdmCollections->children() as $child)
			{
				$object = new $classname($this->ia);
				$object->_loadStandardQBOXML($child);
				$object->_loadFromQBOXML($child);
				$objects[] = $object;
			}
			
			if ($count<$per_page || (isset($limit) && $total>=$limit))
			{
				break;
			}
			$page++;
		}
		
		return $objects;
	}
	
	protected function _loadStandardQBOXML($xml)
	{
		$this->Id = (integer)$xml->Id;
		$this->SyncToken = (integer)$xml->SyncToken;
		$this->CreateTime = new DateTime((string)$xml->MetaData->CreateTime);
		$this->LastUpdatedTime = new DateTime((string)$xml->MetaData->LastUpdatedTime);
	}
}

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
	
	abstract protected function _getXMLForQBO();
	abstract protected function _getXMLForQBD();
	
	function __construct($intuit_anywhere_obj)
	{
		$this->ia = $intuit_anywhere_obj;
	}
	
	function getOne($id)
	{
		if ($this->ia->isQBO())
		{
			return $this->_getOneQBO($id);
		}
		return $this->_getOneQBD($id);
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
	
	protected function _listAllDBD($filters,$limit=null)
	{
		throw new Exception("_listAllDBD: not implemented.");
	}
	
	protected function _saveQBD()
	{
		throw new Exception("_saveQBD: not implemented.");
	}
	
	protected function _saveQBO()
	{
		$xml = $this->_getXMLForQBO();
		
		$result = $this->ia->query($this->_getQBOObjectName(),null,"POST",null,$xml);
		
		$result_xml = new SimpleXMLElement($result);
		
		$this->_loadStandardQBOXML($result_xml);
		$this->_loadFromQBOXML($result_xml);
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

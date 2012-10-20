<?php

class SessionAccess
{
	private $type;
	function __construct($type)
	{
		$this->type = $type;
	}
	function __get($name)
	{
		return $_SESSION[$this->type][$name];
	}
	function __set($name,$value)
	{
		$_SESSION[$this->type][$name] = $value;
	}
	function __isset($name)
	{
		if (isset($_SESSION[$this->type][$name]))
		{
			return true;
		}
		return false;
	}
	function __unset($name)
	{
		if (isset($_SESSION[$this->type][$name]))
		{
			unset($_SESSION[$this->type][$name]);
		}
	}
	function getArray()
	{
		return $_SESSION[$this->type];
	}
	
	function storeCache($name,$value)
	{
		$_SESSION[$this->type][$name."_cache"] = array("time"=>time(),"value"=>$value);
	}
	function getCache($name,$timeout)
	{
		if (!isset($_SESSION[$this->type][$name."_cache"]))
		{
			return null;
		}
		$cache = $_SESSION[$this->type][$name."_cache"];
		if (time() - $shops_cache['time'] < $timeout)
		{
			return $cache['value'];
		}
		return null;
	}
	
	function clear()
	{
		$_SESSION[$this->type] = array();
	}
}

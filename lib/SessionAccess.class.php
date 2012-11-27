<?php

/**
 * Abstraction for session access that allows more control over how and where data is stored.
 */
class SessionAccess
{
	private $type;
	function __construct($type)
	{
		$this->type = $type;
		if (!isset($_SESSION[$this->type]))
		{
			$_SESSION[$this->type] = array();
		}
	}
	function __get($name)
	{
		if (!isset($_SESSION[$this->type][$name]))
		{
			return null;
		}
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
	
	/**
	 *  Return this store as an array.
	 *  @return array The values in this store.
	 */
	function getArray()
	{
		return $_SESSION[$this->type];
	}
	
	/**
	 * Take an array and load it into this store.
	 * @param array $arr The string indexed array.
	 */
	function loadArray($arr)
	{
		$_SESSION[$this->type] = $arr;
	}
	
	/**
	 * Cache data in the Session for some period of time.
	 * @param string $name Name of the cache
	 * @param mixed $value Value to cache
	 */
	function storeCache($name,$value)
	{
		$_SESSION[$this->type][$name."_cache"] = array("time"=>time(),"value"=>$value);
	}
	
	/**
	 * Retrieve data from the cache, checks the timeout before returning to make sure it's still valid.
	 * @param string $name Name of the cache that was stored
	 * @return mixed| The cache or null if cache is not set or expired.
	 */
	function getCache($name,$timeout)
	{
		if (!isset($_SESSION[$this->type][$name."_cache"]))
		{
			return null;
		}
		$cache = $_SESSION[$this->type][$name."_cache"];
		if (isset($cache['time']) && time() - $cache['time'] < $timeout)
		{
			return $cache['value'];
		}
		return null;
	}
	
	/**
	 * Clear this session storage.
	 */
	function clear()
	{
		$_SESSION[$this->type] = array();
	}
}

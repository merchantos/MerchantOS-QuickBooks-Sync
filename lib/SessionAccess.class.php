<?php

class SessionAccess
{
	private $type;
	function __construct($type)
	{
		if (!session_id())
		{
			session_start();
		}
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
	function getArray()
	{
		return $_SESSION[$this->type];
	}
}

<?php

require_once("lib/MemcacheHelper.class.php");

class MemcacheSession
{
	protected $expire_minutes;
	protected $memcache;
	
	public static function Init($expire_minutes=60)
	{
		$session_class = new MemcacheSession($expire_minutes);
		
		/* Change the save_handler to use the class functions */
		
		session_set_save_handler (array(&$session_class, '_open'),
								  array(&$session_class, '_close'),
								  array(&$session_class, '_read'),
								  array(&$session_class, '_write'),
								  array(&$session_class, '_destroy'),
								  array(&$session_class, '_gc'));
	}
	
	function __construct($expire_minutes)
	{
		$this->expire_minutes = $expire_minutes;
	}
	
    function _open($path, $name)
	{
    	$this->memcache = MemcacheHelper::getSingleton();
        return TRUE;
    }

    /* Close session */
    function _close()
	{
        return TRUE;
    }
    
    function readMemcache($ses_id)
    {
    	return $this->memcache->readMemcache($ses_id);
    }
    function writeMemcache($ses_id,$data,$expire_minutes)
    {
    	return $this->memcache->writeMemcache($ses_id,$data,$expire_minutes);
    }
    function deleteMemcache($ses_id)
    {
    	return $this->memcache->writeMemcache($ses_id);
    }

    /* Read session data from database */
	function _read($ses_id) {
		$ses_data = $this->readMemcache($ses_id);
		if ($ses_data !== false)
		{
			return $ses_data;
		}
		
		return '';
	}

    function _write($ses_id, $data)
	{
		if ($this->writeMemcache($ses_id,$data,$this->expire_minutes) === true)
		{
			return TRUE;
		}
		
		return TRUE;
    }

    function _destroy($ses_id)
	{
    	$this->deleteMemcache($ses_id);
    	return TRUE;
    }

    function _gc($life)
	{
		// memcache will clean itself up as needed
        return TRUE;
    }
}

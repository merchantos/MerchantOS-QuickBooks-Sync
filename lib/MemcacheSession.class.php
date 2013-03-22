<?php

require_once("lib/MemcacheHelper.class.php");

class MemcacheSession
{
	protected $expire_minutes;
	protected $memcache;
	
	public function __construct($expire_minutes=60)
	{
		$this->expire_minutes = $expire_minutes;
	}
	
	public function register()
	{
		session_set_save_handler (array($this, '_open'),
								  array($this, '_close'),
								  array($this, '_read'),
								  array($this, '_write'),
								  array($this, '_destroy'),
								  array($this, '_gc'));
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
    
    protected function _readMemcache($ses_id)
    {
    	return $this->memcache->readMemcache($ses_id);
    }
    protected function _writeMemcache($ses_id,$data,$expire_minutes)
    {
    	return $this->memcache->writeMemcache($ses_id,$data,$expire_minutes);
    }
    protected function _deleteMemcache($ses_id)
    {
    	return $this->memcache->writeMemcache($ses_id);
    }

    /* Read session data from database */
	function _read($ses_id) {
		$ses_data = $this->_readMemcache($ses_id);
		if ($ses_data !== false)
		{
			return $ses_data;
		}
		
		return '';
	}

    function _write($ses_id, $data)
	{
		if ($this->_writeMemcache($ses_id,$data,$this->expire_minutes) === true)
		{
			return TRUE;
		}
		
		return TRUE;
    }

    function _destroy($ses_id)
	{
    	$this->_deleteMemcache($ses_id);
    	return TRUE;
    }

    function _gc($life)
	{
		// memcache will clean itself up as needed
        return TRUE;
    }
}

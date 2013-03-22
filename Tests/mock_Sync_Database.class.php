<?php

class mock_Sync_Database
{
	public $args;
	public $returns;
	
	public function __construct()
	{
		$this->args = array();
		$this->returns = array();
	}
	
	public function writeAccount($api_key)
	{
		$this->args['writeAccount'] = func_get_args();
		if (isset($this->returns['writeAccount'])) return $this->returns['writeAccount'];
		return null;
	}
	public function readOAuth($account_id)
	{
		$this->args['readOAuth'] = func_get_args();
		if (isset($this->returns['readOAuth'])) return $this->returns['readOAuth'];
		return null;
	}
	public function readSyncSetup($account_id)
	{
		$this->args['readSyncSetup'] = func_get_args();
		if (isset($this->returns['readSyncSetup'])) return $this->returns['readSyncSetup'];
		return null;
	}
	public function writeOAuth($account_id,$oauth_data_array)
	{
		$this->args['writeOAuth'] = func_get_args();
		if (isset($this->returns['writeOAuth'])) return $this->returns['writeOAuth'];
		return true;
	}
}

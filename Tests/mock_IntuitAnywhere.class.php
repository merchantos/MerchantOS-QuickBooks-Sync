<?php

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

class mock_OAuthRequester
{
	protected $_parent;
	
	public function __construct($parent)
	{
		$this->_parent = $parent;
	}
	
	function doRequest ( $usr_id = 0, $curl_options = array(), $options = array() )
	{
		$this->_parent->args['OAuthRequester::doRequest'] = func_get_args();
		if (isset($this->returns['OAuthRequester::doRequest'])) return $this->returns['OAuthRequester::doRequest'];
		return null;
	}
}

class mock_OAuthStore
{
	protected $_parent;
	
	public function __construct($parent)
	{
		$this->_parent = $parent;
	}
	
	public function addServerToken ( $consumer_key, $token_type, $token, $token_secret, $user_id, $options = array() ) 
	{
		$this->_parent->args['OAuthStore::addServerToken'] = func_get_args();
		if (isset($this->returns['OAuthStore::addServerToken'])) return $this->returns['OAuthStore::addServerToken'];
		return null;
	}
}

class mock_IntuitAnywhere extends IntuitAnywhere
{
	public $args = array();
	public $returns = array();
	
	/**
	 * Override this function for unit testing mock object.
	 */
	protected function _getIncomingOAuthToken()
	{
		$this->_parent->args['_getIncomingOAuthToken'] = func_get_args();
		if (isset($this->returns['_getIncomingOAuthToken'])) return $this->returns['_getIncomingOAuthToken'];
		return null;
	}
	/**
	 * Override this function for unit testing mock object.
	 */
	protected function _getIncomingRealmID()
	{
		$this->_parent->args['_getIncomingRealmID'] = func_get_args();
		if (isset($this->returns['_getIncomingRealmID'])) return $this->returns['_getIncomingRealmID'];
		return null;
	}
	/**
	 * Override this function for unit testing mock object.
	 */
	protected function _getIncomingDataSource()
	{
		$this->_parent->args['_getIncomingDataSource'] = func_get_args();
		if (isset($this->returns['_getIncomingDataSource'])) return $this->returns['_getIncomingDataSource'];
		return null;
	}
	/**
	 * Override this function for unit testing mock object.
	 */
	protected function _getIncomingParams()
	{
		$this->_parent->args['_getIncomingParams'] = func_get_args();
		if (isset($this->returns['_getIncomingParams'])) return $this->returns['_getIncomingParams'];
		return null;
	}
	/**
	 * Override this function for unit testing mock object.
	 */
	protected function _getOAuthRequester($request, $method = null, $params = null, $body = null, $files = null)
	{
		$this->_parent->args['_getOAuthRequester'] = func_get_args();
		return new mock_OAuthRequester($this);
	}
	/**
	 * Override this function for unit testing mock object.
	 */
	protected function _OAuthStoreInstance($options=array())
	{
		$this->_parent->args['_OAuthStoreInstance'] = func_get_args();
		return new mock_OAuthStore($this);
	}
	/**
	 * Override this function for unit testing mock object.
	 */
	protected function _requestRequestToken($consumer_key, $usr_id, $params = null, $method = 'POST', $options = array(), $curl_options = array())
	{
		$this->_parent->args['_requestRequestToken'] = func_get_args();
		if (isset($this->returns['_requestRequestToken'])) return $this->returns['_requestRequestToken'];
		return null;
	}
	/**
	 * Override this function for unit testing mock object.
	 */
	protected function _requestAccessToken( $consumer_key, $token, $usr_id, $method = 'POST', $options = array(), $curl_options = array() )
	{
		$this->_parent->args['_requestAccessToken'] = func_get_args();
		if (isset($this->returns['_requestAccessToken'])) return $this->returns['_requestAccessToken'];
		return null;
	}
}

<?php

require_once("lib/SessionAccess.class.php");

/**
 * @codeCoverageIgnore
 */
class mock_SessionAccess extends SessionAccess
{
	public function __construct()
	{
		$this->_type = "me";
		$this->_sess = array("me"=>array());
	}
}

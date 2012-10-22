<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.php");

$merchantos_sess_access = new SessionAccess("merchantos");

require_once("MerchantOS/Option.class.php");

try
{
	$mos_options = new MerchantOS_Option($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
	var_dump($mos_options->listAll());
}	
catch(Exception $e)
{
	echo "Exception: " . $e->getMessage();
	var_dump($e);
}

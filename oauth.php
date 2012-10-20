<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

try
{
	$ianywhere = new IntuitAnywhere($qb_sess_access);
	$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,true);
	
	$user = $ianywhere->getCurrentUser();
	
	$qb_sess_access->CurrentUser = $user;
	
	header("location: ./");
}
catch(Exception $e) {
	echo "Exception: " . $e->getMessage();
	var_dump($e);
}

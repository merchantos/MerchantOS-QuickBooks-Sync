<?php

include_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

include_once("lib/SessionAccess.class.php");

include_once("oauth/library/OAuthStore.php");
include_once("oauth/library/OAuthRequester.php");

include_once("IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

try
{
	$menu = $qb_sess_access->getCache("menu",600);
	if (!$menu)
	{
		$ianywhere = new IntuitAnywhere($qb_sess_access);
		$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false);
		$menu = $ianywhere->getMenu();
		$qb_sess_access->storeCache("menu",$menu);
	}
	echo $menu;
}
catch(Exception $e) {
	echo "Exception: " . $e->getMessage();
	var_dump($e);
}

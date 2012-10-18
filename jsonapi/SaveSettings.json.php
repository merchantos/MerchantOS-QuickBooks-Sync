<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("../lib/SessionAccess.class.php");

require_once("../oauth/library/OAuthStore.php");
require_once("../oauth/library/OAuthRequester.php");

require_once("../IntuitAnywhere/IntuitAnywhere.class.php");

$setup_sess_access = new SessionAccess("setup");

header("Content-Type: application/json");

function returnOutput($output)
{
	if ($_GET['callback'])
	{
		return $_GET['callback'] . "(" . $output . ");";
	}
	return $output;
}

try
{
	foreach ($_POST as $setting_name=>$setting_value)
	{
		$setup_sess_access->{$setting_name} = $setting_value;
	}
	echo returnOutput("{\"" . serialize($setup_sess_access->getArray()). "\"}");
}
catch (Exception $e)
{
	echo returnOutput("{error:'" . $e->Message() . " (" . $e->getCode() . ")'}");
	exit;
}

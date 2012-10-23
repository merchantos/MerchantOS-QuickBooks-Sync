<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.php");

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
	$old_settings = $setup_sess_access->getArray();
	foreach ($old_settings as $old_setting_name=>$old_setting_value)
	{
		if (!isset($_POST[$old_setting_name]))
		{
			unset($setup_sess_access->{$old_setting_name});
		}
	}
	foreach ($_POST as $setting_name=>$setting_value)
	{
		if ($setting_value == "0") {
			unset($setup_sess_access->{$setting_name});
			continue;
		}
		$setup_sess_access->{$setting_name} = $setting_value;
	}
	echo returnOutput("{\"success\":true}");
}
catch (Exception $e)
{
	echo returnOutput("{error:'" . $e->Message() . " (" . $e->getCode() . ")'}");
	exit;
}

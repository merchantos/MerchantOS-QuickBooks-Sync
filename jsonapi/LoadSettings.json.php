<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

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

$settings = $setup_sess_access->getArray();
if (!$settings)
{
	$settings = array();
}

$settings_json = array();
foreach ($settings as $name=>$value)
{
	$value_json = "\"$value\"";
	if (is_array($value))
	{
		$value_json = array();
		foreach ($value as $value_index=>$value_value)
		{
			$value_json[] = "\"$value_index\":\"$value_value\"";
		}
		$value_json = "{" . join(",",$value_json) . "}";
	}
	$settings_json[] = "\"$name\":$value_json";
}

echo returnOutput("{" . join(",",$settings_json) . "}");

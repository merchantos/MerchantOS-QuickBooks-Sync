<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("lib/SessionAccess.class.php");
require_once("MerchantOS/Shop.class.php");

$merchantos_sess_access = new SessionAccess("merchantos");

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
	$shops = $merchantos_sess_access->getCache("shops",600);
	if (!$shops)
	{
		require_once("MerchantOS/Shop.class.php");
		$mos_shop = new MerchantOS_Shop(MOS_API_KEY);
		$shops = $mos_shop->listAll();
		$merchantos_sess_access->storeCache($shops);
	}
	$shops_json = array();
	foreach ($shops as $shop)
	{
		$shops_json[] = "{\"shopID\":" . $shop['shopID'] . ",\"name\":\"" . $shop['name'] . "\"}";
	}
	echo returnOutput("[" . join(",",$shops_json) . "]");
}
catch (Exception $e)
{
	echo returnOutput("{error:'" . $e->Message() . " (" . $e->getCode() . ")'}");
	exit;
}

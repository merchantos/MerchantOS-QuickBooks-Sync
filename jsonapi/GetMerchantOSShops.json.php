<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

$merchantos_sess_access = new SessionAccess("merchantos");

header("Content-Type: application/json");

function returnOutput($output)
{
	if (isset($_GET['callback']))
	{
		return $_GET['callback'] . "(" . $output . ");";
	}
	return $output;
}

$shops = $merchantos_sess_access->getCache("shops",600);
if (!$shops)
{
	require_once("MerchantOS/Shop.class.php");
	$mos_shop = new MerchantOS_Shop($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
	$shops = $mos_shop->listAll();
	$merchantos_sess_access->storeCache("shops",$shops);
}
$shops_json = array();
foreach ($shops as $shop)
{
	$shops_json[] = "{\"shopID\":" . $shop['shopID'] . ",\"name\":\"" . $shop['name'] . "\"}";
}
echo returnOutput("[" . join(",",$shops_json) . "]");

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

$tax_categories = $merchantos_sess_access->getCache("tax_categories",600);
if (!$tax_categories)
{
	require_once("MerchantOS/TaxCategory.class.php");
	$mos_tax_cat = new MerchantOS_TaxCategory($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
	$tax_categories = $mos_tax_cat->listAll();
	$merchantos_sess_access->storeCache("tax_categories",$tax_categories);
}
$tax_categories_json = array();
foreach ($tax_categories as $tax_cat)
{
	$tax_categories_json[] = "{\"taxCategoryID\":" . $tax_cat['taxCategoryID'] . ",\"name\":\"" . $tax_cat['name'] . "\"}";
}
echo returnOutput("[" . join(",",$tax_categories_json) . "]");

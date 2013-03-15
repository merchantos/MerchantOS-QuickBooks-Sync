<?php

echo "For testing only";
exit;

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

$ianywhere = new IntuitAnywhere($qb_sess_access);	
$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization

require_once("IntuitAnywhere/CompanyMetaData.class.php");
$ia_company = new IntuitAnywhere_CompanyMetaData($ianywhere);
$ia_companies = $ia_company->listAll();

if (count($ia_companies)!=1)
{
	var_dump("There should have been one record returned.");
}

$ia_company = $ia_companies[0];

if (strlen(trim($ia_company->CompanyName))<=0)
{
	var_dump("Company name is blank, it should not be.");
}

echo "Done.<br />";

var_dump($ia_company->CompanyName);
var_dump($ia_company->getCompanyEmail());
var_dump($ia_company->getCompanyPhone());
var_dump($ia_company->getCompanyAddress());

var_dump($ia_company);

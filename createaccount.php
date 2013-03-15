<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("Sync/Database.class.php");
require_once("IntuitAnywhere/IntuitAnywhere.class.php");
require_once("Sync/AccountCreation.class.php");

$login_sess_access = new SessionAccess("login");

if (!$login_sess_access->account_creation || !$login_sess_access->account_creation_email)
{
	throw new Exception("Could not create account.");
}

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

// setup oauth but don't do any user authorization (that should have been done in oauth.php before we got here)
$ianywhere = new IntuitAnywhere($qb_sess_access);
$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = don't do auth

if (!$ianywhere->isUserAuthorized())
{
	throw new Exception("Could not establish OAuth connection to Intuit.");
}

try
{
	$loginSessionAccess = new SessionAccess('login');
	$qbSessionAccess = new SessionAccess('qb');
	$merchantosSessionAcccess = new SessionAccess('merchantos');
	$oauthSessionAccess = new SessionAccess('oauth');
	
	$db = new Sync_Database();
	
	$accountCreation = new Sync_AccountCreation($db,$ianywhere,$loginSessionAccess,$qbSessionAccess,$merchantosSessionAcccess,$oauthSessionAccess);
	$accountRedirectURL = $accountCreation->createMOSAccount();
}
catch (Exception $e)
{
	// disconnect so we don't pay for this connection
	$ianywhere->disconnect();
	// rethrow
	throw $e;
}

header("location: $accountRedirectURL");


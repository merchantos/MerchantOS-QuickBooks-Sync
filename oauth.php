<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("database.inc.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");
$login_sess_access = new SessionAccess("login");

$ianywhere = new IntuitAnywhere($qb_sess_access);
$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,true);

$user = $ianywhere->getCurrentUser();

$qb_sess_access->CurrentUser = $user;

if ($login_sess_access->account_creation)
{
	/* we are hypothetically creating a new account, so let's try to do that
	   we need to take the OAuth access, gather whatever info we want from QB
	   then use the MerchantOS API (with a system key) to create a MOS account.
	   Then get an API key to this MOS account. Then forward on to first start.
	   The user will loop back here for sync setup.
	*/
	/*
	   Info to get from QB:
		from current user: First Name, Last Name, Email
		from company meta data:
			email, if not grabbed above (COMPANY_EMAIL or EMAIL_ADDRESS_FOR_CUSTOMERS)
			business QBNRegisteredCompanyName
			business address (PUBLIC_ADDRESS or COMPANY_ADDRESS)
			business phone FreeFormNumber
	*/
	header("location: ./createaccount.php");
	exit;
}

// we're all done so save this to the db
$oauth_array = $oauth_sess_access->getArray();
$qb_array = $qb_sess_access->getArray();

// when should we reconnect/renew another access token?
$renew = time() + (60*60*24*30*4); // 4 months/120 days from now, to be safe (tokens last 6 months).

mosqb_database::writeOAuth($login_sess_access->account_id,array("oauth"=>$oauth_array,"qb"=>$qb_array,"renew"=>$renew));

header("location: ./");

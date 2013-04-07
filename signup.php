<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;
require_once("session.inc.php");
/*
require_once("session.inc.php");
require_once("Sync/Database.class.php");
*/
require_once("Views/Render.class.php");
$login_sess_access = new SessionAccess("login");

if (isset($login_sess_access->account_id) && $login_sess_access->account_id>0)
{
	// this user is already logged in, they shouldn't be here
	header("location: ./");
	exit;
}

$email = (isset($_GET['email'])?$_GET['email']:'');
$firstName = (isset($_GET['firstName'])?$_GET['firstName']:'');
$lastName = (isset($_GET['lastName'])?$_GET['lastName']:'');
$openid = (isset($_GET['openid'])?$_GET['openid']:'');

// we'll use these when we create the MOS account later
$login_sess_access->account_creation_first_name = $firstName;
$login_sess_access->account_creation_last_name = $lastName;
$login_sess_access->account_creation_openid = $openid;

views_Render::renderView('signupform', $locals = array('email' => $email, 'openid_login_url' => MOS_OPENID_URL, 'no_title'=>true));

<?php

require_once("config.inc.php");
require_once("view.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

// we'll load the session ourself for $needkey=false handling (instead of calling session.inc.php)
require_once("lib/Session.class.php");
try
{
	lib_Session::init(false);
}
catch (Exception $e)
{
	echo $e->getMessage();
	exit;
}

$login_sess_access = new SessionAccess("login");

if (isset($login_sess_access->account_id))
{
	// they are already setup with an account, so go to the normal welcome screen.
    header("location: ./");
	exit;
}

/*
 * They have no account so we'll continue, and call idrectConnectToIntuit() with the javascript library
 * We'll record that we are in account creation mode, so when we return from oauth.php we'll know where to go
 */
$login_sess_access->account_creation = true;

render_view('incoming', $locals = array('incoming' => true));

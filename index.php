<?php

require_once("config.inc.php");

GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("Views/Render.class.php");
require_once("session.inc.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$setup_sess_access = new SessionAccess("setup");
$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");
$merchantos_sess_access = new SessionAccess("merchantos");
$login_sess_access = new SessionAccess("login");

if (!$login_sess_access->account_id)
{
	echo "Login/account creation failed. <a href='https://shop.merchantos.com/'>Return to MerchantOS.</a>";
	exit;
}

$ianywhere = new IntuitAnywhere($qb_sess_access);

$is_authorized = false;
$is_setup = false;
$user = null;

if ($ianywhere->isUserAuthorized())
{
	$user = $qb_sess_access->CurrentUser;
	$is_authorized = true;
	
	if (isset($setup_sess_access->data_delay))
	{
		$is_setup = true;
		
		if (isset($_GET['return_url']) && isset($_GET['return_on_setup']))
		{
			header ("location: " . $_GET['return_url']);
			exit;
		}
	}
}

views_Render::renderView('settings', $locals = array('is_authorized' => $is_authorized, 'is_setup' => $is_setup, 'merchantos_sess_access' => $merchantos_sess_access, 'ianywhere' => $ianywhere, 'user' => $user, 'merchantos_sess_access' => $merchantos_sess_access));

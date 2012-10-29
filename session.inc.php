<?php

// initialize the session and handle any session related get params

if (!$_REQUEST[session_name()])
{
	if ($_GET['key'])
	{
		session_id($_GET['key']);
		session_start();
	}
	else
	{
		echo "This application must be accessed through MerchantOS -> Admin -> Setup QuickBooks Sync.";
		exit;
	}
}
else
{
	session_start();
}

if ($_GET['key'])
{
	require_once("lib/SessionAccess.class.php");
	$merchantos_sess_access = new SessionAccess("merchantos");
	if ($_GET['key'])
	{
		// this is where we will eventually either create a new account or login based on a login credential of $_POST['key']
		$merchantos_sess_access->api_key = $_GET['key'];
		if ($_GET['return_url'])
		{
			$merchantos_sess_access->return_url = $_GET['return_url'];
		}
		if ($_GET['account'])
		{
			$merchantos_sess_access->api_account = $_GET['account'];
		}
	}
}

require_once("lib/SessionAccess.class.php");

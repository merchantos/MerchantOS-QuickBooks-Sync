<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("lib/Validation.class.php");

$email = $_POST['email'];

// @todo - we're probably going to need some more stringent validation here (duh)
if (!lib_Validation::ValidateAddress($email))
{
	throw new Exception("$email email address is invalid.");
}

$login_sess_access = new SessionAccess("login");
$login_sess_access->account_creation = true;
$login_sess_access->account_creation_email = $email;

header("Content-Type: application/json");

$res = "{\"success\":true}";

if (isset($_GET['callback']))
{
	echo $_GET['callback'] . "(" . $res . ");";
}
echo $res;

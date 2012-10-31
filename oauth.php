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

// we're all do so save this to the db
$oauth_array = $oauth_sess_access->getArray();
$qb_array = $qb_sess_access->getArray();

// when should we reconnect/renew another access token?
$renew = time() + (60*60*24*30*4); // 4 months/120 days from now, to be safe (tokens last 6 months).

mosqb_database::writeOAuth($login_sess_access->account_id,array("oauth"=>$oauth_array,"qb"=>$qb_array,"renew"=>$renew));

header("location: ./");

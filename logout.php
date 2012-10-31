<?php

include_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

$merchantos_sess_access = new SessionAccess("merchantos");
$return_url = $merchantos_sess_access->return_url;

session_unset();
session_destroy();

header("location: $return_url");

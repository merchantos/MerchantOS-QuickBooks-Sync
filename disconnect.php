<?php

include_once("config.inc.php");
require_once("session.php");

$oauth_sess_access = new SessionAccess("oauth");
$oauth_sess_access->clear();

$qb_sess_access = new SessionAccess("qb");
$qb_sess_access->clear();

header("location: ./?disconnected=1");

<?php

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("Sync/Database.class.php");

$db = new Sync_Database();
$db->checkSetup();

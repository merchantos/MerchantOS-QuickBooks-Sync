<?php
require_once("config.inc.php");

// redirect
header("location: " . MOS_OPENID_URL . "&return_to=qbsync");

<?php

include_once("config.inc.php");

session_start();
session_destroy();

header("location: ./index.php");

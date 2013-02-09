<?php
require_once("lib/Session.class.php");
try
{
	lib_Session::init();
}
catch (Exception $e)
{
	echo $e->getMessage();
	exit;
}

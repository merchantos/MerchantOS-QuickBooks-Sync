<?php

echo "For testing only";
exit;

require_once("../config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

$ianywhere = new IntuitAnywhere($qb_sess_access);	
$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization

require_once("IntuitAnywhere/Class.class.php");
$ia_class = new IntuitAnywhere_Class($ianywhere);

// Get a list of matching classes

$class_name = "Test Class " . time();

$filters = array('Name'=>$class_name);
	
$classes = $ia_class->listAll($filters);

if (count($classes)>0)
{
	var_dump($classes);
	var_dump("Should have been empty.");
}

$ia_class->Name = $class_name;
$ia_class->save();

if (!isset($ia_class->Id))
{
	var_dump($ia_class);
	var_dump("Was saved but Id was not filled. Something went wrong with save.");
}

$classes = $ia_class->listAll($filters);

if (count($classes)!=1)
{
	var_dump($classes);
	var_dump("Should have one result but has more or less.");
}

if ($classes[0]->Id != $ia_class->Id)
{
	var_dump($classes[0]->Id);
	var_dump($ia_class->Id);
	var_dump("Should have been equal.");
}

echo "Done.";

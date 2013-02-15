<?php

set_include_path(get_include_path() . PATH_SEPARATOR . "<path to this file>");

// Set the default timezone, this is required for PHP 5.3
date_default_timezone_set('America/Los_Angeles');

ini_set('display_errors', 'On');

define("APPLICATION_NAME","MerchantOS QB Sync");
define("DEVELOPMENT_STACK",true); // set to false for production
define("DISPLAY_ALL_ERRORS",true); // set to false for production
define("SHOW_NOTICE",true); // set to false for production
define("MERCHANTOS_ENVIRONMENT","development"); // could be development, staging, production

define("AIRBRAKE_API_KEY","<airbrake project key>");

define("MOS_API_URL","https://api.merchantos.com/API/");
define("MOS_SYSTEM_API_KEY","<MerchantOS API Key With System Access To Account Control>");
define("MOS_API_CLIENT_ID","MerchantOS API Client ID");

define("INTUIT_CONSUMER_KEY", "<oauth client key>");
define("INTUIT_CONSUMER_SECRET", "<oauth client secrete>");

define("INTUIT_OAUTH_HOST", "https://oauth.intuit.com");
define("INTUIT_REQUEST_TOKEN_URL", "https://oauth.intuit.com/oauth/v1/get_request_token");
define("INTUIT_AUTHORIZE_URL", "https://appcenter.intuit.com/Connect/Begin");
define("INTUIT_ACCESS_TOKEN_URL", "https://oauth.intuit.com/oauth/v1/get_access_token");

define("INTUIT_DISPLAY_NAME","MerchantOS QuickBooks Sync");
define("INTUIT_CALLBACK_URL","https://quickbooks.merchantos.com/oauth.php");

global $_OAUTH_INTUIT_CONFIG;
$_OAUTH_INTUIT_CONFIG= array(
		'consumer_key'		=> INTUIT_CONSUMER_KEY, 
		'consumer_secret'	=> INTUIT_CONSUMER_SECRET,
		'server_uri'		=> INTUIT_OAUTH_HOST,
		'request_token_uri'	=> INTUIT_REQUEST_TOKEN_URL,
		'authorize_uri'		=> INTUIT_AUTHORIZE_URL,
		'access_token_uri'	=> INTUIT_ACCESS_TOKEN_URL
	);

define('OAUTH_TMP_DIR', '/web/dumps/');

// OAUTH_STORE_DYNAMODB_REGION defaults to AmazonDynamoDB::REGION_US_E1;
define("OAUTH_STORE_DYNAMODB_TABLE","QuickBooksOAuthClient");
define("OAUTH_STORE_DYNAMODB_HASH","consumerKey");

require_once("helpers/Errors.class.php");
$errors = new helpers_Errors();
$errors->setup();

function cust_shutdown_func()
{
	$errors = new helpers_Errors();
	$message = $errors->fatalHandler();
	if ($message)
	{
		ob_end_clean();
		
		echo $message;
	}
	// there's an explicit output buffer flush at the end of the script
}

// initialize the shutdown function (saves state and syncs cache upon shutdown)
register_shutdown_function(cust_shutdown_func);

// output buffering
ob_start(); // no custom output handler right now

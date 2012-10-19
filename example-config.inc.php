<?php

set_include_path(get_include_path() . PATH_SEPARATOR . "<path to this file>");

// Set the default timezone, this is required for PHP 5.3
date_default_timezone_set('America/Los_Angeles');

ini_set('display_errors', 'On');

define("MOS_API_URL","https://api.merchantos.com/API/");
define("MOS_API_KEY","<MerchantOS API Key>"); // todo, this won't be hard coded

define("INTUIT_CONSUMER_KEY", "<oauth client key>");
define("INTUIT_CONSUMER_SECRET", "<oauth client secrete>");

define("INTUIT_OAUTH_HOST", "https://oauth.intuit.com");
define("INTUIT_REQUEST_TOKEN_URL", "https://oauth.intuit.com/oauth/v1/get_request_token");
define("INTUIT_AUTHORIZE_URL", "https://appcenter.intuit.com/Connect/Begin");
define("INTUIT_ACCESS_TOKEN_URL", "https://oauth.intuit.com/oauth/v1/get_access_token");

define("INTUIT_DISPLAY_NAME","MerchantOS QuickBooks Sync");
define("INTUIT_CALLBACK_URL","https://rad.localdev/QuickBooks/start.php");

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

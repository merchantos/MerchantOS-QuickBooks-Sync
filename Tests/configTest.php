<?php
require_once("config.inc.php");

class configTest extends PHPUnit_Framework_TestCase
{
    public function testConfigOK()
    {
		$this->assertTrue(defined("DEFAULT_TIMEZONE"),"DEFAULT_TIMEZONE not defined in config.");
		$this->assertEquals(date_default_timezone_get(),"America/Los_Angeles","Time zone not set to America/Los_Angeles.");
		$this->assertTrue(defined("APPLICATION_NAME"),"APPLICATION_NAME not defined in config.");
		$this->assertTrue(defined("DEVELOPMENT_STACK"),"DEVELOPMENT_STACK not defined in config.");
		$this->assertTrue(defined("DISPLAY_ALL_ERRORS"),"DISPLAY_ALL_ERRORS not defined in config.");
		$this->assertTrue(defined("SHOW_NOTICE"),"SHOW_NOTICE not defined in config.");
		$this->assertTrue(is_bool(DEVELOPMENT_STACK),"DEVELOPMENT_STACK not defined as bool in config.");
		$this->assertTrue(is_bool(DISPLAY_ALL_ERRORS),"DISPLAY_ALL_ERRORS not defined as bool in config.");
		$this->assertTrue(is_bool(SHOW_NOTICE),"SHOW_NOTICE not defined as bool in config.");
		$this->assertTrue(defined("MERCHANTOS_ENVIRONMENT"),"MERCHANTOS_ENVIRONMENT not defined in config.");
		$this->assertTrue(defined("AIRBRAKE_API_KEY"),"AIRBRAKE_API_KEY not defined in config.");
		$this->assertTrue(defined("MOS_OPENID_URL"),"MOS_OPENID_URL not defined in config.");
		$this->assertTrue(defined("MOS_API_URL"),"MOS_API_URL not defined in config.");
		$this->assertTrue(defined("MOS_SYSTEM_API_KEY"),"MOS_SYSTEM_API_KEY not defined in config.");
		$this->assertTrue(is_integer(MOS_API_CLIENT_ID),"MOS_API_CLIENT_ID is not an integer.");
		$this->assertTrue(defined("INTUIT_CONSUMER_KEY"),"INTUIT_CONSUMER_KEY not defined in config.");
		$this->assertTrue(defined("INTUIT_CONSUMER_SECRET"),"INTUIT_CONSUMER_SECRET not defined in config.");
		$this->assertTrue(defined("INTUIT_OAUTH_HOST"),"INTUIT_OAUTH_HOST not defined in config.");
		$this->assertTrue(defined("INTUIT_REQUEST_TOKEN_URL"),"INTUIT_REQUEST_TOKEN_URL not defined in config.");
		$this->assertTrue(defined("INTUIT_AUTHORIZE_URL"),"INTUIT_AUTHORIZE_URL not defined in config.");
		$this->assertTrue(defined("INTUIT_ACCESS_TOKEN_URL"),"INTUIT_ACCESS_TOKEN_URL not defined in config.");
		$this->assertTrue(defined("INTUIT_AUTHORIZE_URL"),"INTUIT_AUTHORIZE_URL not defined in config.");
		$this->assertTrue(defined("INTUIT_DISPLAY_NAME"),"INTUIT_DISPLAY_NAME not defined in config.");
		$this->assertTrue(defined("INTUIT_CALLBACK_URL"),"INTUIT_CALLBACK_URL not defined in config.");
		
		global $_OAUTH_INTUIT_CONFIG;
		$this->assertArrayHasKey('consumer_key',$_OAUTH_INTUIT_CONFIG,"global \$_OAUTH_INTUIT_CONFIG doesn't have key consumer_key in config.");
		$this->assertArrayHasKey('server_uri',$_OAUTH_INTUIT_CONFIG,"global \$_OAUTH_INTUIT_CONFIG doesn't have key server_uri in config.");
		$this->assertArrayHasKey('request_token_uri',$_OAUTH_INTUIT_CONFIG,"global \$_OAUTH_INTUIT_CONFIG doesn't have key request_token_uri in config.");
		$this->assertArrayHasKey('authorize_uri',$_OAUTH_INTUIT_CONFIG,"global \$_OAUTH_INTUIT_CONFIG doesn't have key authorize_uri in config.");
		$this->assertArrayHasKey('access_token_uri',$_OAUTH_INTUIT_CONFIG,"global \$_OAUTH_INTUIT_CONFIG doesn't have key access_token_uri in config.");
		
		$this->assertTrue(defined("OAUTH_TMP_DIR"),"OAUTH_TMP_DIR not defined in config.");
		$this->assertTrue(is_dir(OAUTH_TMP_DIR),"OAUTH_TMP_DIR=" . OAUTH_TMP_DIR . " is not a directory.");
		
		$this->assertTrue(defined("OAUTH_STORE_DYNAMODB_TABLE"),"OAUTH_STORE_DYNAMODB_TABLE not defined in config.");
		$this->assertTrue(defined("OAUTH_STORE_DYNAMODB_HASH"),"OAUTH_STORE_DYNAMODB_HASH not defined in config.");
		$this->assertTrue(defined("MOS_QB_SYNC_DATABASE_HOST"),"MOS_QB_SYNC_DATABASE_HOST not defined in config.");
		$this->assertTrue(defined("MOS_QB_SYNC_DATABASE_NAME"),"MOS_QB_SYNC_DATABASE_NAME not defined in config.");
		$this->assertTrue(defined("MOS_QB_SYNC_DATABASE_USERNAME"),"MOS_QB_SYNC_DATABASE_USERNAME not defined in config.");
		$this->assertTrue(defined("MOS_QB_SYNC_DATABASE_PASSWORD"),"MOS_QB_SYNC_DATABASE_PASSWORD not defined in config.");
		
		$this->assertTrue(helpers_Errors::isSetup(),"Error helper is not setup in config.");
		
		$this->assertEquals("On",ini_get("display_errors"),"Display errors was not set on in config.");
		
		$this->assertGreaterThan(0,ob_get_level(),"Output buffering is not on in config.");
    }
}

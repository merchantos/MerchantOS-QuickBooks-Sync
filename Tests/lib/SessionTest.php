<?php
require_once("config.inc.php");
require_once("lib/Session.class.php");

require_once("Tests/mock_Sync_Database.class.php");
require_once("Tests/mock_SessionAccess.class.php");

class mock_lib_Session extends lib_Session
{
	public $returns = array();
	public $args = array();
	
	
	protected function _getSessionAccess($type)
	{
		if (!isset($this->_sessionAccess[$type]))
		{
			$this->_sessionAccess[$type] = new mock_SessionAccess();
		}
		return $this->_sessionAccess[$type];
	}
	public function getSessionAccess($type)
	{
		if (!isset($this->_sessionAccess[$type]))
		{
			return null;
		}
		return $this->_sessionAccess[$type];
	}
	
	public function getDB()
	{
		return $this->_db;
	}
	
	protected function _setSessionID($id)
	{
		$this->args['_setSessionID'] = func_get_args();
	}
	protected function _sessionStart()
	{
		$this->args['_sessionStart'] = func_get_args();
	}
	protected function _setupMemcacheSession()
	{
		$this->args['_setupMemcacheSession'] = func_get_args();
	}
	protected function _isSessionCookieSet()
	{
		$this->args['_isSessionCookieSet'] = func_get_args();
		if (isset($this->returns['_isSessionCookieSet'])) return $this->returns['_isSessionCookieSet'];
		return true;
	}
	protected function _isKeySet()
	{
		$this->args['_isKeySet'] = func_get_args();
		if (isset($this->returns['_isKeySet'])) return $this->returns['_isKeySet'];
		return true;
	}
	protected function _getKey()
	{
		$this->args['_getKey'] = func_get_args();
		if (isset($this->returns['_getKey'])) return $this->returns['_getKey'];
		return "test";
	}
	protected function _getReturnURL()
	{
		$this->args['_getReturnURL'] = func_get_args();
		if (isset($this->returns['_getReturnURL'])) return $this->returns['_getReturnURL'];
		return "test";
	}
	protected function _getAccountNumber()
	{
		$this->args['_getAccountNumber'] = func_get_args();
		if (isset($this->returns['_getAccountNumber'])) return $this->returns['_getAccountNumber'];
		return 42;
	}
	protected function _isReturnURLSet()
	{
		$this->args['_isReturnURLSet'] = func_get_args();
		if (isset($this->returns['_isReturnURLSet'])) return $this->returns['_isReturnURLSet'];
		return true;
	}
	protected function _isAccountNumberSet()
	{
		$this->args['_isAccountNumberSet'] = func_get_args();
		if (isset($this->returns['_isAccountNumberSet'])) return $this->returns['_isAccountNumberSet'];
		return true;
	}
}

class lib_SessionTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
		$foo = "hello world";
		$mock_lib_sess = new mock_lib_Session($foo);
		$this->assertEquals("hello world",$mock_lib_sess->getDB());
	}
	
	public function testInitDry()
	{
		$mock_sync_db = new mock_Sync_Database();
		
		$mock_sync_db->returns['readOAuth'] = array("oauth"=>array("foo"=>"bar"),"qb"=>array("bat"=>"baz"),"renew"=>1234);
		$mock_sync_db->returns['readSyncSetup'] = array("foo"=>"bar");
		
		$mock_lib_sess = new mock_lib_Session($mock_sync_db);
		
		$mock_lib_sess->init();
		
		$this->assertEquals(array(),$mock_lib_sess->args['_setupMemcacheSession']);
		$this->assertEquals(array(),$mock_lib_sess->args['_sessionStart']);
		$this->assertFalse(isset($mock_lib_sess->args['_setSessionID']));
		$this->assertEquals(array(),$mock_lib_sess->args['_isKeySet']);
	}
	
	public function testInitNoCookieWithKey()
	{
		$mock_sync_db = new mock_Sync_Database();
		
		$mock_sync_db->returns['readOAuth'] = array("oauth"=>array("foo"=>"bar"),"qb"=>array("bat"=>"baz"),"renew"=>1234);
		$mock_sync_db->returns['readSyncSetup'] = array("foo"=>"bar");
		
		$mock_lib_sess = new mock_lib_Session($mock_sync_db);
		
		$mock_lib_sess->returns['_isSessionCookieSet'] = false;
		$mock_lib_sess->returns['_getKey'] = "fookey";
		
		$mock_lib_sess->init();
		
		$this->assertEquals(array(),$mock_lib_sess->args['_setupMemcacheSession']);
		$this->assertEquals(array(),$mock_lib_sess->args['_sessionStart']);
		$this->assertEquals("fookey",$mock_lib_sess->args['_setSessionID'][0]);
		$this->assertEquals(array(),$mock_lib_sess->args['_isKeySet']);
	}
	
	public function testInitNoCookieNoKey()
	{
		$mock_sync_db = new mock_Sync_Database();
		
		$mock_sync_db->returns['readOAuth'] = array("oauth"=>array("foo"=>"bar"),"qb"=>array("bat"=>"baz"),"renew"=>1234);
		$mock_sync_db->returns['readSyncSetup'] = array("foo"=>"bar");
		
		$mock_lib_sess = new mock_lib_Session($mock_sync_db);
		
		$mock_lib_sess->returns['_isSessionCookieSet'] = false;
		$mock_lib_sess->returns['_isKeySet'] = false;
		
		// needkey = true
		$exception = false;
		try
		{
			$mock_lib_sess->init();
		}
		catch (Exception $e)
		{
			$exception = true;
			$this->assertEquals("This application must be accessed through MerchantOS -> Admin -> Setup QuickBooks Sync.",$e->getMessage());
		}
		$this->assertTrue($exception,"Exception expected but not thrown.");
		
		$this->assertEquals(array(),$mock_lib_sess->args['_setupMemcacheSession']);
		$this->assertEquals(array(),$mock_lib_sess->args['_isSessionCookieSet']);
		$this->assertEquals(array(),$mock_lib_sess->args['_isKeySet']);
		
		// needkey = false
		$mock_lib_sess->init(false);
		
		$this->assertEquals(array(),$mock_lib_sess->args['_setupMemcacheSession']);
		$this->assertEquals(array(),$mock_lib_sess->args['_isSessionCookieSet']);
		$this->assertEquals(array(),$mock_lib_sess->args['_isKeySet']);
		$this->assertEquals(array(),$mock_lib_sess->args['_sessionStart']);
		$this->assertFalse(isset($mock_lib_sess->args['_initMOSKey']));
	}
	
	public function testInitMOSKey()
	{
		$mock_sync_db = new mock_Sync_Database();
		$mock_sync_db->returns['readSyncSetup'] = array("foo"=>"bar");
		$mock_lib_sess = new mock_lib_Session($mock_sync_db);
		$mock_lib_sess->returns['_isSessionCookieSet'] = false;
		$mock_lib_sess->returns['_getKey'] = "fookey";
		
		$mock_lib_sess->returns['_getReturnURL'] = "foobar";
		$mock_lib_sess->returns['_getAccountNumber'] = 43;
		
		$mock_sync_db->returns['writeAccount'] = 44;
		
		// test oauth params ok
		$mock_sync_db->returns['readOAuth'] = array("oauth"=>array("foo"=>"bar"),"qb"=>array("bat"=>"baz"),"renew"=>1234);
		$mock_lib_sess->init();
		
		$mos_sess_access = $mock_lib_sess->getSessionAccess('merchantos');
		
		$this->assertEquals("foobar",$mos_sess_access->return_url);
		$this->assertEquals(43,$mos_sess_access->api_account);
		$this->assertEquals("fookey",$mos_sess_access->api_key);
		
		$this->assertEquals("fookey",$mock_sync_db->args['writeAccount'][0]);
		
		$login_sess_access = $mock_lib_sess->getSessionAccess('login');
		
		$this->assertEquals(44,$login_sess_access->account_id);
		
		$this->assertEquals(44,$mock_sync_db->args['readOAuth'][0]);
		
		// test oauth params missing oauth
		$mock_sync_db->returns['readOAuth'] = array("qb"=>array("bat"=>"baz"),"renew"=>1234);
		$exception = false;
		try
		{
			$mock_lib_sess->init();
		}
		catch (Exception $e)
		{
			$this->assertEquals("OAuth connection to Intuit was not initialized.",$e->getMessage());
			$exception = true;
		}
		$this->assertTrue($exception,"Exception expected but not thrown.");
		
		// test oauth params missing qb
		$mock_sync_db->returns['readOAuth'] = array("oauth"=>array("foo"=>"bar"),"renew"=>1234);
		$exception = false;
		try
		{
			$mock_lib_sess->init();
		}
		catch (Exception $e)
		{
			$this->assertEquals("OAuth connection to Intuit was not initialized.",$e->getMessage());
			$exception = true;
		}
		$this->assertTrue($exception,"Exception expected but not thrown.");
		
		// test oauth params missing renew
		$mock_sync_db->returns['readOAuth'] = array("oauth"=>array("foo"=>"bar"),"qb"=>array("bat"=>"baz"));
		$exception = false;
		try
		{
			$mock_lib_sess->init();
		}
		catch (Exception $e)
		{
			$this->assertEquals("OAuth connection to Intuit was not initialized.",$e->getMessage());
			$exception = true;
		}
		$this->assertTrue($exception,"Exception expected but not thrown.");
	}
	
	public function testLoadOAuth()
	{
		$mock_sync_db = new mock_Sync_Database();
		$mock_sync_db->returns['readSyncSetup'] = array("qux"=>"bar");
		$mock_lib_sess = new mock_lib_Session($mock_sync_db);
		$mock_lib_sess->returns['_isSessionCookieSet'] = false;
		$mock_lib_sess->returns['_getKey'] = "fookey";
		
		$mock_lib_sess->returns['_getReturnURL'] = "foobar";
		$mock_lib_sess->returns['_getAccountNumber'] = 43;
		
		$mock_sync_db->returns['writeAccount'] = 44;
		
		$mock_sync_db->returns['readOAuth'] = array("oauth"=>array("foo"=>"bar"),"qb"=>array("bat"=>"baz"),"renew"=>1234);
		$mock_lib_sess->init();
		
		$oauth_sess_access = $mock_lib_sess->getSessionAccess("oauth");
		$login_sess_access = $mock_lib_sess->getSessionAccess("login");
		$setup_sess_access = $mock_lib_sess->getSessionAccess("setup");
		$qb_sess_access = $mock_lib_sess->getSessionAccess("qb");
		
		$this->assertEquals("bar",$oauth_sess_access->foo);
		$this->assertEquals("baz",$qb_sess_access->bat);
		$this->assertEquals("bar",$setup_sess_access->qux);
	}
}

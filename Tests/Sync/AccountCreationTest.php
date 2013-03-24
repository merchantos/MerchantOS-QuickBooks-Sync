<?php
require_once("config.inc.php");
require_once("Sync/AccountCreation.class.php");
require_once("IntuitAnywhere/IntuitAnywhere.class.php");
require_once("lib/SessionAccess.class.php");

require_once("Tests/mock_Sync_Database.class.php");
require_once("Tests/mock_SessionAccess.class.php");
require_once("Tests/mock_IntuitAnywhere.class.php");

class mock_Sync_AccountCreation extends Sync_AccountCreation
{
	public $func_fail = array();
	
	public $args = array();
	
	/**
	 * mock_Sync_Database
	 */
	public function get_db()
	{
		return $this->_db;
	}
	/**
	 * mock_IntuitAnywhere
	 */
	public function get_ia()
	{
		return $this->_ia;
	}
	/**
	 * mock_SessionAccess
	 */
	public function get_login_session()
	{
		return $this->_login_session;
	}
	/**
	 * mock_SessionAccess
	 */
	public function get_qb_session()
	{
		return $this->_qb_session;
	}
	/**
	 * mock_SessionAccess
	 */
	public function get_mos_session()
	{
		return $this->_mos_session;
	}
	/**
	 * mock_SessionAccess
	 */
	public function get_oauth_session()
	{
		return $this->_oauth_session;
	}
	
	/**
	 * Use MerchantOS_Account to create a new object on MOS API. Useful to override in mock object for testing.
	 * @return MerchantOS_Account
	 */
	protected function _createMerchantOSAccount($shop_name,$email,$firstName,$lastName,$phone,$password)
	{
		if (isset($this->func_fail['_createMerchantOSAccount'])) return new SimpleXMLElement("<message>test failure</message>");
		$this->args['_createMerchantOSAccount'] = func_get_args();
		return new SimpleXMLElement("<SystemCustomer><systemCustomerID>42</systemCustomerID><SystemUsers><SystemUser><systemUserID>43</systemUserID></SystemUser></SystemUsers><redirect>test</redirect></SystemCustomer>");
	}
	protected function _listAllMerchantOSShops($apiKey,$systemCustomerID)
	{
		if (isset($this->func_fail['_listAllMerchantOSShops'])) return new SimpleXMLElement("<message>test failure</message>");
		$this->args['_listAllMerchantOSShops'] = func_get_args();
		return new SimpleXMLElement("<Shops><Shop><shopID>123</shopID></Shop></Shops>");
	}
	protected function _updateMerchantOSShops($apiKey,$systemCustomerID,$shopID,$shop_updates)
	{
		if (isset($this->func_fail['_updateMerchantOSShops'])) return new SimpleXMLElement("<message>test failure</message>");
		$this->args['_updateMerchantOSShops'] = func_get_args();
	}
	protected function _getIntuitAnywhereCompanies()
	{
		if (isset($this->func_fail['_getIntuitAnywhereCompanies'])) return null;
		return array(new mock_IntuitAnywhere_CompanyMetaData());
	}
	protected function _createMerchantOSSystemAPIKey($systemCustomerID,$systemUserID)
	{
		if (isset($this->func_fail['_createMerchantOSSystemAPIKey'])) return new SimpleXMLElement("<message>test failure</message>");
		$this->args['_createMerchantOSSystemAPIKey'] = func_get_args();
		return new SimpleXMLElement("<SystemAPIKey><apiKey>testkey</apiKey><systemAPIKeyID>44</systemAPIKeyID></SystemAPIKey>");
	}
	protected function _createMerchantOSSystemAPIKeyAccess($systemAPIKeyID)
	{
		if (isset($this->func_fail['_createMerchantOSSystemAPIKeyAccess'])) return new SimpleXMLElement("<message>test failure</message>");
		$this->args['_createMerchantOSSystemAPIKeyAccess'] = func_get_args();
		return new SimpleXMLElement("<SystemAPIKeyAccess><systemAPIKeyAccessID>45</systemAPIKeyAccessID></SystemAPIKeyAccess>");
	}
	protected function _associateMerchantOSOpenID($systemUserID,$openid)
	{
		if (isset($this->func_fail['_associateMerchantOSOpenID'])) return new SimpleXMLElement("<message>test failure</message>");
		$this->args['_associateMerchantOSOpenID'] = func_get_args();
		return new SimpleXMLElement("<SystemOpenID><systemOpenIDID>46</systemOpenIDID></SystemOpenID>");
	}
}

class mock_IntuitAnywhere_CompanyMetaData
{
	public $CompanyName = "acme";
	public function getCompanyPhone() { return "(866) 554-2453"; }
	public function getCompanyAddress() { return array("line1"=>"711 Capitol Way S.","#705"=>"test2","city"=>"olympia","state"=>"WA","zip"=>"98501"); }
}

class Sync_AccountCreationTest extends PHPUnit_Framework_TestCase
{
	protected static $openid = "foobar";
	protected static $firstName = "justin";
	protected static $lastName = "laing";
	protected static $email = "jlaing@gmail.com";
	
	protected function _getMockLoginSessionAccess()
	{
		$loginSessionAccess = new mock_SessionAccess();
		$loginSessionAccess->account_creation_openid = self::$openid;
		$loginSessionAccess->account_creation_first_name = self::$firstName;
		$loginSessionAccess->account_creation_last_name = self::$lastName;
		$loginSessionAccess->account_creation_email = self::$email;
		return $loginSessionAccess;
	}
	
	protected function _getMockQBSessionAccess()
	{
		$qbSessionAccess = new mock_SessionAccess();
		$qbSessionAccess->test1 = "foo";
		$qbSessionAccess->test2 = "bar";
		return $qbSessionAccess;
	}
	
	protected function _getMockOAuthSessionAccess()
	{
		$oauthSessionAccess = new mock_SessionAccess();
		$oauthSessionAccess->test1 = "baz";
		$oauthSessionAccess->test2 = "qux";
		return $oauthSessionAccess;
	}
	
	protected function _getMockDB()
	{
		$db = new mock_Sync_Database();
		$db->returns["writeAccount"] = 46;
		$db->returns["writeOAuth"] = true;
		return $db;
	}
	
    public function testConstruct()
    {
		$db = $this->_getMockDB();
		$ianywhere = new mock_IntuitAnywhere(new mock_SessionAccess());
		$merchantosSessionAcccess = new mock_SessionAccess();
		
		$oauthSessionAccess = $this->_getMockOAuthSessionAccess();
		$qbSessionAccess = $this->_getMockQBSessionAccess();
		$loginSessionAccess = $this->_getMockLoginSessionAccess();
		
		$mock_ac = new mock_Sync_AccountCreation($db,$ianywhere,$loginSessionAccess,$qbSessionAccess,$merchantosSessionAcccess,$oauthSessionAccess);
		
		$this->assertEquals($db,$mock_ac->get_db());
		$this->assertEquals($ianywhere,$mock_ac->get_ia());
		$this->assertEquals($loginSessionAccess,$mock_ac->get_login_session());
		$this->assertEquals($qbSessionAccess,$mock_ac->get_qb_session());
		$this->assertEquals($merchantosSessionAcccess,$mock_ac->get_mos_session());
		$this->assertEquals($oauthSessionAccess,$mock_ac->get_oauth_session());
    }
	
	protected function _getMockSyncAccountCreation()
	{
		$db = $this->_getMockDB();
		
		$ianywhere = new mock_IntuitAnywhere(new mock_SessionAccess());
		$merchantosSessionAcccess = new mock_SessionAccess();
		$oauthSessionAccess = $this->_getMockOAuthSessionAccess();
		$qbSessionAccess = $this->_getMockQBSessionAccess();
		$loginSessionAccess = $this->_getMockLoginSessionAccess();
		$loginSessionAccess = $this->_getMockLoginSessionAccess();
		return new mock_Sync_AccountCreation($db,$ianywhere,$loginSessionAccess,$qbSessionAccess,$merchantosSessionAcccess,$oauthSessionAccess);
	}
	
	public function testEmailValidation()
	{
		// test valid email
		$mock_ac = $this->_getMockSyncAccountCreation();
		
		$mock_ac->createMOSAccount();
		
		// email is arg 1
		$this->assertEquals("jlaing@gmail.com",$mock_ac->args['_createMerchantOSAccount'][1]);
		
		// test invalid email
		self::$email = "bademail";
		$exception = false;
		
		$mock_ac = $this->_getMockSyncAccountCreation();
		
		try
		{
			$mock_ac->createMOSAccount();
		}
		catch (Exception $e)
		{
			$this->assertTrue(stripos($e->getMessage(),"email")!==false);
			$exception = true;
		}
		$this->assertTrue($exception);
		
		// reset to valid email
		self::$email = "jlaing@gmail.com";
	}
	
	public function testPasswordGeneration()
	{
		$mock_ac = $this->_getMockSyncAccountCreation();
		$mock_ac->createMOSAccount();
		
		// strlen > 5
		// password is arg 5
		$this->assertGreaterThan(5,strlen($mock_ac->args['_createMerchantOSAccount'][5]));
	}
	
	public function testCreateMerchantOSAccount()
	{
		// test good create
		$mock_ac = $this->_getMockSyncAccountCreation();
		$mock_ac->createMOSAccount();
		
		$mock_company_meta = new mock_IntuitAnywhere_CompanyMetaData();
		
		$this->assertEquals($mock_company_meta->CompanyName,$mock_ac->args['_createMerchantOSAccount'][0]);
		$this->assertEquals("jlaing@gmail.com",$mock_ac->args['_createMerchantOSAccount'][1]);
		$this->assertEquals("justin",$mock_ac->args['_createMerchantOSAccount'][2]);
		$this->assertEquals("laing",$mock_ac->args['_createMerchantOSAccount'][3]);
		$this->assertEquals($mock_company_meta->getCompanyPhone(),$mock_ac->args['_createMerchantOSAccount'][4]);
		$this->assertGreaterThan(5,strlen($mock_ac->args['_createMerchantOSAccount'][5]));
		
		// test fail of _createMerchantOSAccount
		$mock_ac->func_fail = array('_createMerchantOSAccount'=>true);
		
		$exception = false;
		try
		{
			$mock_ac->createMOSAccount();
		}
		catch (Exception $e)
		{
			$this->assertTrue(stripos($e->getMessage(),"Could not create MerchantOS account")!==false);
			$exception = true;
		}
		$this->assertTrue($exception);
	}
	
	public function testCreateMOSAPIKey()
	{
		// test normal
		$mock_ac = $this->_getMockSyncAccountCreation();
		$mock_ac->createMOSAccount();
		
		$this->assertEquals(42,$mock_ac->args['_createMerchantOSSystemAPIKey'][0]);
		$this->assertEquals(43,$mock_ac->args['_createMerchantOSSystemAPIKey'][1]);
		
		$this->assertEquals(44,$mock_ac->args['_createMerchantOSSystemAPIKeyAccess'][0]);
		
		// should use our new key we returned
		$this->assertEquals('testkey',$mock_ac->args['_listAllMerchantOSShops'][0]);
		$this->assertEquals('testkey',$mock_ac->args['_updateMerchantOSShops'][0]);
		
		// fail creating key
		$mock_ac->func_fail = array('_createMerchantOSSystemAPIKey'=>true);
		$exception = false;
		try
		{
			$mock_ac->createMOSAccount();
		}
		catch (Exception $e)
		{
			$this->assertTrue(stripos($e->getMessage(),"Could not create MerchantOS API key")!==false);
			$exception = true;
		}
		$this->assertTrue($exception);
		
		// fail creating key access
		$mock_ac->func_fail = array('_createMerchantOSSystemAPIKeyAccess'=>true);
		$exception = false;
		try
		{
			$mock_ac->createMOSAccount();
		}
		catch (Exception $e)
		{
			$this->assertTrue(stripos($e->getMessage(),"Could not create MerchantOS API key access")!==false);
			$exception = true;
		}
		$this->assertTrue($exception);
	}
	
	public function testAssociateMOSOpenID()
	{
		// test normal
		$mock_ac = $this->_getMockSyncAccountCreation();
		$mock_ac->createMOSAccount();
		
		$this->assertEquals(43,$mock_ac->args['_associateMerchantOSOpenID'][0]);
		$this->assertEquals(self::$openid,$mock_ac->args['_associateMerchantOSOpenID'][1]);
		
		// test fail
		$mock_ac->func_fail = array('_associateMerchantOSOpenID'=>true);
		$exception = false;
		try
		{
			$mock_ac->createMOSAccount();
		}
		catch (Exception $e)
		{
			$this->assertTrue(stripos($e->getMessage(),"Could not associate OpenID with MerchantOS SystemUser")!==false);
			$exception = true;
		}
		$this->assertTrue($exception);
	}
	
	public function testCreateQBSyncAccount()
	{
		$mock_ac = $this->_getMockSyncAccountCreation();
		$mock_ac->createMOSAccount();
		
		$mos_session = $mock_ac->get_mos_session();
		$this->assertEquals("testkey",$mos_session->api_key);
		$this->assertEquals("test?form_name=intuit&return=1",$mos_session->return_url);
		$this->assertEquals(42,$mos_session->api_account);
		
		$login_session = $mock_ac->get_login_session();
		$this->assertEquals(46,$login_session->account_id);
		
		$mock_db = $mock_ac->get_db();
		$this->assertEquals("testkey",$mock_db->args['writeAccount'][0]);
		$this->assertEquals(46,$mock_db->args['writeOAuth'][0]);
		
		$oauth_data_array = $mock_db->args['writeOAuth'][1];
		
		$this->assertEquals('foo',$oauth_data_array['qb']['test1']);
		$this->assertEquals('bar',$oauth_data_array['qb']['test2']);
		$this->assertEquals('baz',$oauth_data_array['oauth']['test1']);
		$this->assertEquals('qux',$oauth_data_array['oauth']['test2']);
		
		$this->assertGreaterThan(time() + (60*60*24*30*4) - 2,$oauth_data_array['renew']);
		$this->assertLessThan(time() + (60*60*24*30*4) + 1,$oauth_data_array['renew']);
	}
	
	public function testRedirectReturn()
	{
		
		$mock_ac = $this->_getMockSyncAccountCreation();
		
		$redirect = $mock_ac->createMOSAccount();
		
		$password = $mock_ac->args['_createMerchantOSAccount'][5];
		
		$redirect_should_be = "test?form_name=login&login_name=" . urlencode("jlaing@gmail.com") . "&login_password=" . urlencode($password) . "&redirect_after_login=" . urlencode("openid.php?form_name=intuit&return=1");
		
		$this->assertEquals($redirect_should_be,$redirect);
	}
	
}

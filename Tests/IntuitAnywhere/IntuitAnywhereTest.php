<?php
require_once("config.inc.php");
require_once("IntuitAnywhere/IntuitAnywhere.class.php");
require_once("Tests/mock_SessionAccess.class.php");
//require_once("Tests/mock_IntuitAnywhere.class.php");

class lib_IntuitAnywhereTest extends PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$store = new mock_SessionAccess();
		$ianywhere = new IntuitAnywhere($store);
		$this->assertEquals($store,$ianywhere->store);
	}
	
	public function testIsUserAuthorized()
	{
		$store = new mock_SessionAccess();
		$ianywhere = new IntuitAnywhere($store);
		
		$this->assertFalse($ianywhere->isUserAuthorized());
		
		$store->realmId = true;
		
		$this->assertTrue($ianywhere->isUserAuthorized());
	}
	
	public function testIsQBO()
	{
		$store = new mock_SessionAccess();
		$ianywhere = new IntuitAnywhere($store);
		
		$store->dataSource = "QBD";
		
		$this->assertFalse($ianywhere->isQBO());
		
		$store->dataSource = "QBO";
		
		$this->assertTrue($ianywhere->isQBO());
	}
	public function testIsQBOException()
	{
		$store = new mock_SessionAccess();
		$ianywhere = new IntuitAnywhere($store);
		
		$this->setExpectedException("Exception","dataSource is not set, so isQBO can not be determined");
		$ianywhere->isQBO();
	}
	
	public function testIsQBD()
	{
		$store = new mock_SessionAccess();
		$ianywhere = new IntuitAnywhere($store);
		
		$store->dataSource = "QBO";
		
		$this->assertFalse($ianywhere->isQBD());
		
		$store->dataSource = "QBD";
		
		$this->assertTrue($ianywhere->isQBD());
	}
	public function testIsQBDException()
	{
		$store = new mock_SessionAccess();
		$ianywhere = new IntuitAnywhere($store);
		
		$this->setExpectedException("Exception","dataSource is not set, so isQBD can not be determined");
		$ianywhere->isQBD();
	}
	
	public function testInitOAuth()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',array('_OAuthStoreInstance','_authorize','_getBaseURI','isQBO'),array($store));
		
		$mock_ianywhere->expects($this->any())
			->method("_getBaseURI")
			->will($this->returnValue('foo'));
		
		$mock_ianywhere->expects($this->exactly(4))
			->method("_OAuthStoreInstance")
			->with($this->equalTo(array("consumer_key"=>"foo","authorize_uri"=>"bar","access"=>"bat")));
		
		$mock_ianywhere->expects($this->once())
			->method("_authorize");
		
		$mock_ianywhere->expects($this->once())
			->method("_getBaseURI")
			->will($this->returnValue('foo'));
		
		$mock_ianywhere->expects($this->exactly(2))
			->method("isQBO")
			->will($this->onConsecutiveCalls(true, false));
		
		$store->BaseURI = "qux";
		
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		$store->realmId = "baz";
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		
		unset($store->BaseURI);
		
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		
		$this->assertEquals("foo",$store->BaseURI);
		
		unset($store->BaseURI);
		
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		
		$this->assertEquals("https://services.intuit.com/sb",$store->BaseURI);
	}
	public function testInitOAuthConsumerKeyMissingException()
	{
		$store = new mock_SessionAccess();
		$mock_ianywhere = $this->getMock('IntuitAnywhere',array('_OAuthStoreInstance','_authorize','_getBaseURI'),array($store));
		
		$this->setExpectedException("Exception","consumer_key option must be set for initOAuth().");
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array(),false);
	}
	public function testInitOAuthAuthorizeURIMissingException()
	{
		$store = new mock_SessionAccess();
		$mock_ianywhere = $this->getMock('IntuitAnywhere',array('_OAuthStoreInstance','_authorize','_getBaseURI'),array($store));
		
		$this->setExpectedException("Exception","authorize_uri option must be set for initOAuth().");
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo"),false);
	}
	public function testInitOAuthNotInteractiveException()
	{
		$store = new mock_SessionAccess();
		$mock_ianywhere = $this->getMock('IntuitAnywhere',array('_OAuthStoreInstance','_authorize','_getBaseURI'),array($store));
		
		$this->setExpectedException("Exception","Not authorized.",401);
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),false);
	}

	public function testAuthorize()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_OAuthStoreInstance',
				'_getBaseURI',
				'_getIncomingOAuthToken',
				'_redirect',
				'_requestRequestToken',
				'_requestAccessToken',
				'_getIncomingParams',
				'_getIncomingRealmID',
				'_getIncomingDataSource'),
			array($store));
		
		$mock_ianywhere->expects($this->any())
			->method("_getBaseURI")
			->will($this->returnValue('foo'));
		
		$mock_ianywhere->expects($this->exactly(2))
			->method("_OAuthStoreInstance")
			->with($this->equalTo(array("consumer_key"=>"foo","authorize_uri"=>"bar","access"=>"bat")));
		
		$mock_ianywhere->expects($this->exactly(3))
			->method("_getIncomingOAuthToken")
			->will($this->onConsecutiveCalls(null,true,"qux"));
		
		$store->BaseURI = "baz";
		
		// _getIncomingOAuthToken === null
		$mock_ianywhere->expects($this->once())
			->method("_requestRequestToken")
			->with($this->equalTo("foo"),$this->equalTo(0),array("xoauth_displayname"=>"testdisplayname","oauth_callback"=>"testcallbackurl"))
			->will($this->returnValue(array("token"=>"quux")));
		
		$mock_ianywhere->expects($this->once())
			->method("_redirect")
			->with($this->equalTo("bar?oauth_token=quux"));
		
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		
		
		// _getIncomingOAuthToken !== null (=true)
		$mock_ianywhere->expects($this->once())
			->method("_getIncomingParams")
			->will($this->returnValue("garply"));
		
		$mock_ianywhere->expects($this->once())
			->method("_requestAccessToken")
			->with($this->equalTo("foo"),$this->equalTo("qux"),$this->equalTo(0),$this->equalTo("POST"),$this->equalTo("garply"));
		
		$mock_ianywhere->expects($this->once())
			->method("_getIncomingRealmID")
			->will($this->returnValue("corge"));
		
		$mock_ianywhere->expects($this->once())
			->method("_getIncomingDataSource")
			->will($this->returnValue("grault"));
		
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		
		$this->assertEquals("corge",$store->realmId);
		$this->assertEquals("grault",$store->dataSource);
	}
	public function testAuthorizeAccessTokenOAuthException()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_OAuthStoreInstance',
				'_getBaseURI',
				'_getIncomingOAuthToken',
				'_requestAccessToken',
				'_getIncomingParams',),
			array($store));
		
		$mock_ianywhere->expects($this->any())
			->method("_getBaseURI")
			->will($this->returnValue('foo'));
		
		$mock_ianywhere->expects($this->exactly(1))
			->method("_OAuthStoreInstance")
			->with($this->equalTo(array("consumer_key"=>"foo","authorize_uri"=>"bar","access"=>"bat")));
		
		$mock_ianywhere->expects($this->exactly(2))
			->method("_getIncomingOAuthToken")
			->will($this->onConsecutiveCalls(true,"qux"));
		
		$mock_ianywhere->expects($this->once())
			->method("_getIncomingParams")
			->will($this->returnValue("garply"));
		
		$mock_ianywhere->expects($this->once())
			->method("_requestAccessToken")
			->with($this->equalTo("foo"),$this->equalTo("qux"),$this->equalTo(0),$this->equalTo("POST"),$this->equalTo("garply"))
			->will($this->throwException(new OAuthException2("test message")));
		
		$store->BaseURI = "baz";
		
		$this->setExpectedException("OAuthException2");
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
	}
}

<?php
require_once("config.inc.php");
require_once("IntuitAnywhere/IntuitAnywhere.class.php");
require_once("Tests/mock_SessionAccess.class.php");
//require_once("Tests/mock_IntuitAnywhere.class.php");

class mock_OAuthRequesterForIntuitAnywhere
{
	function doRequest($one,$two=null)
	{
		return null;
	}
}

/**
 * class mock_IntuitAnywhereForQueryURL
 */
class mock_IntuitAnywhereForQueryURL extends IntuitAnywhere
{
	public function getQueryURL($one,$two=null)
	{
		return $this->_getQueryURL($one,$two);
	}
}

/**
 * class mock_IntuitAnywhereOAuthStoreForReconnect
 */
class mock_IntuitAnywhereOAuthStoreForReconnect
{
	public function addServerToken($one, $two, $three, $four, $five, $six)
	{
		return null;
	}
}



class IntuitAnywhereTest extends PHPUnit_Framework_TestCase
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
				'_getIncomingOAuthToken',
				'_redirect',
				'_requestRequestToken',
				'_requestAccessToken',
				'_getIncomingParams',
				'_getIncomingRealmID',
				'_getIncomingDataSource'),
			array($store));
		
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
				'_getIncomingOAuthToken',
				'_requestAccessToken',
				'_getIncomingParams',),
			array($store));
		
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
	
	public function testGetBaseURI()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_OAuthStoreInstance',
				'isQBO',
				'_getOAuthRequester',
				'_handleError'),
			array($store));
		
		$store->realmId = "foo";
		
		$mock_ianywhere->expects($this->exactly(2))
			->method("isQBO")
			->will($this->returnValue(true));
		
		$mock_requester = $this->getMock("mock_OAuthRequesterForIntuitAnywhere");
		
		$extra_headers = array(
			"Content-Type: application/xml",
			"Host: qbo.intuit.com",
			"Accept-Encoding: gzip,deflate",
		);
		
		$mock_requester->expects($this->exactly(2))
			->method("doRequest")
			->with($this->equalTo(0),$this->equalTo(array(CURLOPT_HTTPHEADER=>$extra_headers,CURLOPT_ENCODING=>1)))
			->will($this->onConsecutiveCalls(
				array(
					'code'=>200,
					'body'=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
						<qbo:QboUser xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
						  <qbo:LoginName>ipp.intuit.com+dr@gmail.com</qbo:LoginName>
						  <qbo:Ticket>V1-73-Q013395388397857cf0f26</qbo:Ticket>
						  <qbo:AgentId>489710665</qbo:AgentId>
						  <qbo:CurrentCompany><qbo:CompanyId>489711245</qbo:CompanyId>
							<qbo:BaseURI>https://qbo.intuit.com/qbo1</qbo:BaseURI>
							<qbo:SubscriptionStatus>SUBSCRIBED</qbo:SubscriptionStatus>
						  </qbo:CurrentCompany>
						</qbo:QboUser>'
				),
				array(
					'code'=>400
				)
			));
		
		$mock_ianywhere->expects($this->exactly(2))
			->method("_getOAuthRequester")
			->will($this->returnValue($mock_requester));
			
		// test good result
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		
		$this->assertEquals("ipp.intuit.com+dr@gmail.com",$store->LoginName);
		$this->assertEquals("489711245",$store->CompanyId);
		$this->assertEquals("https://qbo.intuit.com/qbo1",$store->BaseURI);
		
		// test error
		$mock_ianywhere->expects($this->once())
			->method("_handleError")
			->with($this->equalTo(array("code"=>400)),$this->equalTo("Failed to get BaseURI."));
		
		unset($store->BaseURI);
		
		$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
	}
	
	public function testHandleErrorWithBaseURIError()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_OAuthStoreInstance',
				'isQBO',
				'_getOAuthRequester'),
			array($store));
		
		$store->realmId = "foo";
		
		$mock_ianywhere->expects($this->exactly(3))
			->method("isQBO")
			->will($this->returnValue(true));
		
		$mock_requester = $this->getMock("mock_OAuthRequesterForIntuitAnywhere");
		
		$extra_headers = array(
			"Content-Type: application/xml",
			"Host: qbo.intuit.com",
			"Accept-Encoding: gzip,deflate",
		);
		
		$mock_requester->expects($this->exactly(3))
			->method("doRequest")
			->with($this->equalTo(0),$this->equalTo(array(CURLOPT_HTTPHEADER=>$extra_headers,CURLOPT_ENCODING=>1)))
			->will($this->onConsecutiveCalls(
				array(),
				array(
					"code"=>401,
					"body"=>'<FaultInfo>
				  <Message>Unauthorized Request. The resource you are requesting requires authentication.</Message>
				  <ErrorCode>401</ErrorCode><Cause>SERVER</Cause>
				</FaultInfo>'
				),
				array(
					"code"=>400,
					"body"=>'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<FaultInfo xmlns="http://www.intuit.com/sb/cdm/baseexceptionmodel/xsd">
  <Message>Error validating Header:</Message>
  <ErrorCode>RECEIVE_PAYMENT_REQUEST_BAD</ErrorCode>
  <Cause>RECEIVE_PAYMENT_INVALID_HEADER</Cause>
</FaultInfo>'
				)
			));
			
		$mock_ianywhere->expects($this->exactly(3))
			->method("_getOAuthRequester")
			->will($this->returnValue($mock_requester));
		
		// unknown error
		$exception = false;
		try
		{
			$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		}
		catch (Exception $e)
		{
			$exception = true;
			$this->assertEquals("Failed to get BaseURI.",$e->getMessage());
		}
		$this->assertTrue($exception,"Expected exception for unknown error not thrown.");
		
		// 401 error
		$exception = false;
		try
		{
			$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		}
		catch (Exception $e)
		{
			$exception = true;
			$msg = $e->getMessage();
			$code = $e->getCode();
			$this->assertEquals("Unauthorized Request. The resource you are requesting requires authentication.",$msg);
			$this->assertEquals(401,$code);
		}
		$this->assertTrue($exception,"Expected exception for 401 error not thrown.");
		
		// 400 error
		$exception = false;
		try
		{
			$mock_ianywhere->initOAuth("bat","testdisplayname","testcallbackurl",array("consumer_key"=>"foo","authorize_uri"=>"bar"),true);
		}
		catch (Exception $e)
		{
			$exception = true;
			$msg = $e->getMessage();
			$code = $e->getCode();
			$this->assertEquals("RECEIVE_PAYMENT_INVALID_HEADER (RECEIVE_PAYMENT_REQUEST_BAD): Error validating Header:",$msg);
			$this->assertEquals(400,$code);
		}
		$this->assertTrue($exception,"Expected exception for 400 error not thrown.");
	}
	
	public function testGetQueryURL()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('mock_IntuitAnywhereForQueryURL',
			array(
				'isQBO',
				'isQBD'),
			array($store));
		
		$mock_ianywhere->expects($this->exactly(4))
			->method("isQBO")
			->will($this->onConsecutiveCalls(false,false,true,false));
		
		$mock_ianywhere->expects($this->exactly(2))
			->method("isQBD")
			->will($this->onConsecutiveCalls(false,true));
		
		$store->BaseURI = "foo";
		$store->realmId = "bar";
		
		// no objectID, no QBO, no QBD (not called)
		$uri = $mock_ianywhere->getQueryURL("bat");
		$this->assertEquals("foo/bat/v2/bar",$uri);
		
		// yes objectID, no QBO, no QBD (called)
		$uri = $mock_ianywhere->getQueryURL("bat","baz");
		$this->assertEquals("foo/bat/v2/bar/baz",$uri);
		
		// no objectID, yes QBO, no QBD (not called)
		$uri = $mock_ianywhere->getQueryURL("bat");
		$this->assertEquals("foo/resource/bat/v2/bar",$uri);
		
		// yes objectID & domain, no QBO, yes QBD (called)
		$uri = $mock_ianywhere->getQueryURL("bat","baz:qux");
		$this->assertEquals("foo/bat/v2/bar/qux?idDomain=baz",$uri);
	}
	public function testGetQueryURLNoBaseURIException()
	{
		$store = new mock_SessionAccess();
		$mock_ianywhere = new mock_IntuitAnywhereForQueryURL($store);
		$this->setExpectedException("Exception","BaseURI must be set to construct a query URL.");
		$mock_ianywhere->getQueryURL("foo","bar");
	}
	public function testGetQueryURLNoRealmIdException()
	{
		$store = new mock_SessionAccess();
		$store->BaseURI = "bat";
		$mock_ianywhere = new mock_IntuitAnywhereForQueryURL($store);
		$this->setExpectedException("Exception","realmId must be set to construct a query URL.");
		$mock_ianywhere->getQueryURL("foo","bar");
	}
	
	public function testQuery()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'isQBO',
				'_getQueryURL',
				'_getOAuthRequester',
				'_handleError'),
			array($store));
		
		$mock_ianywhere->expects($this->exactly(4))
			->method("isQBO")
			->will($this->onConsecutiveCalls(true,true,false,false));
		
		$mock_requester = $this->getMock("mock_OAuthRequesterForIntuitAnywhere");
		
		$mock_requester->expects($this->exactly(5))
			->method("doRequest")
			->will($this->onConsecutiveCalls(
				array('code'=>200,'body'=>'foo'),
				array('code'=>200,'body'=>'bar'),
				array('code'=>400,'body'=>'bar'),
				array('code'=>302,'body'=>'bat','headers'=>array('location'=>'baz')),
				array('code'=>200,'body'=>'quux')
			));
		
		$mock_ianywhere->expects($this->exactly(5))
			->method("_getOAuthRequester")
			->will($this->returnValue($mock_requester));
		
		// QBO standard content type, 200
		$res = $mock_ianywhere->query('qux','quux','GET','corge','grault');
		$this->assertEquals('foo',$res);
		
		// QBO application/xml content type, 200
		$res = $mock_ianywhere->query('qux',null,'POST','corge',null);
		$this->assertEquals('bar',$res);
		
		// QBD, 400, error handler
		$mock_ianywhere->expects($this->once())
			->method("_handleerror")
			->with($this->equalTo(array('code'=>400,'body'=>'bar')),$this->equalTo("Query for qux failed."));
		$mock_ianywhere->query('qux','quux','GET','corge','grault');
		
		// QBD 302 then 200
		$res = $mock_ianywhere->query('qux','quux','GET','corge','grault');
		$this->assertEquals('quux',$res);
	}
	
	public function testGetCurrentUser()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_getQueryURL',
				'_getOAuthRequester',
				'_handleError'),
			array($store));
		
		$mock_requester = $this->getMock("mock_OAuthRequesterForIntuitAnywhere");
		
		$mock_requester->expects($this->exactly(4))
			->method("doRequest")
			->will($this->onConsecutiveCalls(
				array('code'=>400,'body'=>'foo'),
				array(
					'code'=>200,
					'body'=>'<?xml version="1.0"?>
								<UserResponse xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
								  xmlns="http://platform.intuit.com/api/v1">
								  <ErrorCode>0</ErrorCode>
								  <ServerTime>2012-04-13T18:47:34.5422493Z</ServerTime>
								  <User>
									<ScreenName>Jonny</ScreenName>
									<FirstName>John</FirstName>
									<LastName>Doe</LastName>
									<EmailAddress>JohnDoe@g88.net</EmailAddress>
									<IsVerified>true</IsVerified>
								  </User>
								</UserResponse>'
				),
				array(
					'code'=>200,
					'body'=>'<?xml version="1.0"?>
								<UserResponse xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
								  xmlns="http://platform.intuit.com/api/v1">
								  <ErrorCode>0</ErrorCode>
								  <ServerTime>2012-04-13T18:47:34.5422493Z</ServerTime>
								  <User>
									<ScreenName></ScreenName>
									<FirstName>John</FirstName>
									<LastName>Doe</LastName>
									<EmailAddress>JohnDoe@g88.net</EmailAddress>
									<IsVerified>true</IsVerified>
								  </User>
								</UserResponse>'
				),
				array(
					'code'=>200,
					'body'=>'<?xml version="1.0"?>
								<UserResponse xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
								  xmlns="http://platform.intuit.com/api/v1">
								  <ErrorCode>0</ErrorCode>
								  <ServerTime>2012-04-13T18:47:34.5422493Z</ServerTime>
								  <User>
									<ScreenName></ScreenName>
									<FirstName></FirstName>
									<LastName></LastName>
									<EmailAddress>JohnDoe@g88.net</EmailAddress>
									<IsVerified>true</IsVerified>
								  </User>
								</UserResponse>'
				)
			));
		
		$mock_ianywhere->expects($this->exactly(4))
			->method("_getOAuthRequester")
			->will($this->returnValue($mock_requester));
		
		// error
		$mock_ianywhere->expects($this->once())
			->method("_handleerror")
			->with($this->equalTo(array('code'=>400,'body'=>'foo')),$this->equalTo("Could not get current user."));
		$mock_ianywhere->getCurrentUser();
		
		// handle=screenanme
		$res = $mock_ianywhere->getCurrentUser();
		$this->assertEquals("Jonny",$res['handle']);
		$this->assertEquals("John",$res['FirstName']);
		$this->assertEquals("Doe",$res['LastName']);
		$this->assertEquals("JohnDoe@g88.net",$res['EmailAddress']);
		$this->assertEquals("Jonny",$res['ScreenName']);
		$this->assertEquals("true",$res['IsVerified']);
		
		// handle=firstname, lastname
		$res = $mock_ianywhere->getCurrentUser();
		$this->assertEquals("John Doe",$res['handle']);
		$this->assertEquals("John",$res['FirstName']);
		$this->assertEquals("Doe",$res['LastName']);
		$this->assertEquals("JohnDoe@g88.net",$res['EmailAddress']);
		$this->assertEquals("",$res['ScreenName']);
		$this->assertEquals("true",$res['IsVerified']);
		
		// handle=email
		$res = $mock_ianywhere->getCurrentUser();
		$this->assertEquals("JohnDoe@g88.net",$res['handle']);
		$this->assertEquals("",$res['FirstName']);
		$this->assertEquals("",$res['LastName']);
		$this->assertEquals("JohnDoe@g88.net",$res['EmailAddress']);
		$this->assertEquals("",$res['ScreenName']);
		$this->assertEquals("true",$res['IsVerified']);
		
	}
	public function testGetCurrentUserException()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_getQueryURL',
				'_getOAuthRequester',
				'_handleError'),
			array($store));
		
		$mock_requester = $this->getMock("mock_OAuthRequesterForIntuitAnywhere");
		
		$mock_requester->expects($this->once())
			->method("doRequest")
			->will($this->returnValue(
				array(
					'code'=>200,
					'body'=>'<?xml version="1.0" encoding="utf-8"?>
								<PlatformResponse xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://platform.intuit.com/api/v1">
								  <ErrorMessage>This API requires Authorization.</ErrorMessage>
								  <ErrorCode>22</ErrorCode>
								  <ServerTime>2013-04-05T18:00:29.4277137Z</ServerTime>
								</PlatformResponse>'
				)
			)
		);
		
		$mock_ianywhere->expects($this->once())
			->method("_getOAuthRequester")
			->will($this->returnValue($mock_requester));
		
		$this->setExpectedException("Exception","This API requires Authorization.",22);
		
		$mock_ianywhere->getCurrentUser();
	}
	
	public function testGetMenu()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_getQueryURL',
				'_getOAuthRequester',
				'_handleError'),
			array($store));
		
		$mock_requester = $this->getMock("mock_OAuthRequesterForIntuitAnywhere");
		
		$mock_requester->expects($this->exactly(2))
			->method("doRequest")
			->will($this->onConsecutiveCalls(
				array('code'=>400,'body'=>'foo'),
				array(
					'code'=>200,
					'body'=>'barmenu'
				)
			));
			
		$mock_ianywhere->expects($this->exactly(2))
			->method("_getOAuthRequester")
			->will($this->returnValue($mock_requester));
		
		// error
		$mock_ianywhere->expects($this->once())
			->method("_handleerror")
			->with($this->equalTo(array('code'=>400,'body'=>'foo')),$this->equalTo("Could not get menu code."));
		$mock_ianywhere->getMenu();
		
		// ok
		$res = $mock_ianywhere->getMenu();
		$this->assertEquals("barmenu",$res);
	}
	
	public function testDisconnect()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_getQueryURL',
				'_getOAuthRequester',
				'_handleError'),
			array($store));
		
		$mock_requester = $this->getMock("mock_OAuthRequesterForIntuitAnywhere");
		
		$mock_requester->expects($this->exactly(2))
			->method("doRequest")
			->will($this->onConsecutiveCalls(
				array('code'=>400,'body'=>'foo'),
				array(
					'code'=>200,
					'body'=>'barmenu'
				)
			));
			
		$mock_ianywhere->expects($this->exactly(2))
			->method("_getOAuthRequester")
			->will($this->returnValue($mock_requester));
		
		// error
		$mock_ianywhere->expects($this->once())
			->method("_handleerror")
			->with($this->equalTo(array('code'=>400,'body'=>'foo')),$this->equalTo("Could not disconnect user."));
		$mock_ianywhere->disconnect();
		
		// ok
		$res = $mock_ianywhere->disconnect();
		$this->assertEquals(true,$res);
	}
	
	public function testReconnect()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_getQueryURL',
				'_getOAuthRequester',
				'_handleError',
				'_OAuthStoreInstance'),
			array($store));
		
		$mock_requester = $this->getMock("mock_OAuthRequesterForIntuitAnywhere");
		
		$mock_requester->expects($this->exactly(2))
			->method("doRequest")
			->will($this->onConsecutiveCalls(
				array('code'=>400,'body'=>'foo'),
				array(
					'code'=>200,
					'body'=>'<reconnect><OAuthToken>bar</OAuthToken><OAuthTokenSecret>bat</OAuthTokenSecret></reconnect>'
				)
			));
			
		$mock_ianywhere->expects($this->exactly(2))
			->method("_getOAuthRequester")
			->will($this->returnValue($mock_requester));
		
		$mock_store = $this->getMock('mock_IntuitAnywhereOAuthStoreForReconnect');
		
		$mock_store->expects($this->once())
			->method("addServerToken")
			->with($this->equalTo(null), $this->equalTo('access'), $this->equalTo('bar'), $this->equalTo('bat'), $this->equalTo(null), $this->equalTo(null));
		
		$mock_ianywhere->expects($this->once())
			->method("_OAuthStoreInstance")
			->will($this->returnValue($mock_store));
		
		// error
		$mock_ianywhere->expects($this->once())
			->method("_handleerror")
			->with($this->equalTo(array('code'=>400,'body'=>'foo')),$this->equalTo("Could not reconnect user."));
		$mock_ianywhere->reconnect();
		
		// ok
		$res = $mock_ianywhere->reconnect();
		$this->assertEquals(true,$res);
	}
	public function testReconnectException()
	{
		$store = new mock_SessionAccess();
		
		$mock_ianywhere = $this->getMock('IntuitAnywhere',
			array(
				'_getQueryURL',
				'_getOAuthRequester'),
			array($store));
		
		$mock_requester = $this->getMock("mock_OAuthRequesterForIntuitAnywhere");
		
		$mock_requester->expects($this->once())
			->method("doRequest")
			->will($this->returnValue(
				array(
					'code'=>200,
					'body'=>'<?xml version="1.0" encoding="utf-8"?>
						<PlatformResponse xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://platform.intuit.com/api/v1">
						  <ErrorMessage>This API requires Authorization.</ErrorMessage>
						  <ErrorCode>22</ErrorCode>
						  <ServerTime>2013-04-05T18:00:29.4277137Z</ServerTime>
						</PlatformResponse>'
				)
			));
			
		$mock_ianywhere->expects($this->any())
			->method("_getOAuthRequester")
			->will($this->returnValue($mock_requester));
		
		$this->setExpectedException("Exception","This API requires Authorization.",22);
		
		$mock_ianywhere->reconnect();
	}
}

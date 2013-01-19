<?php

class IntuitAnywhere
{
	/**
	 * @var SessionAccess Place to store and retrieve information about this API connection. May be session specific or persistant (database).
	 */
	protected $store;
	/**
	 * @var string OAuth consumer key.
	 */
	protected $consumerKey;
	/**
	 * @var string OAuth authorize URI for IntuitAnywhere
	 */
	protected $authorizeURI;
	/**
	 * @var string Display name of this application for use in OAuth authorize process.
	 */
	protected $displayName;
	/**
	 * @var string The URL to return back to after OAuth authorization is done.
	 */
	protected $callbackURL;
	
	/**
	 * Create an instance of IntuitAnywhere for access.
	 * @param SessionAccess $store Place to store and retrieve information about this API connection. May be session specific or persistant (database).
	 */
	public function __construct($store)
	{
		$this->store = $store;
	}
	
	/**
	 * Are we already authorized and ready to make API calls?
	 * @return boolean Are we authorized and ready to go? True == ready to go.
	 */
	public function isUserAuthorized()
	{
		if (!isset($this->store->realmId))
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Is this a connection to QuickBooks Online Edition
	 * @return boolean True if this is a connection to QuickBooks Online Edition (not Desktop)
	 */
	public function isQBO()
	{
		if (!isset($this->store->dataSource))
		{
			throw new Exception("dataSource is not set, so isQBO can not be determined");
		}
		if ($this->store->dataSource=="QBO")
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Is this a connection to QuickBooks Desktop
	 * @return boolean True if this is a connection to QuickBooks Desktop (not Online)
	 */
	public function isQBD()
	{
		if (!isset($this->store->dataSource))
		{
			throw new Exception("dataSource is not set, so isQBD can not be determined");
		}
		if ($this->store->dataSource=="QBD")
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Make sure we have an authorized and ready to go OAuth connection. If $interactive=false will fail if we do not without going through authorize process.
	 * @param SessionAccess $oauth_store Place to store and retrieve information about the OAuth connection. Can be session specific or persistant (database), this class doesn't care.
	 * @param string $displayName Display name of this application for use in OAuth authorize process.
	 * @param string $callbackURL The URL to return back to after OAuth authorization is done.
	 * @param array $options OAuth connection options: array("consumer_key"=>Required,"authorize_uri"=>Required)
	 * @param boolean $interactive true=go through with entire OAuth process if neccesary (redirect user). false=fail if OAuth connection not already established and ready to use.
	 */
	public function initOAuth($oauth_store,$displayName,$callbackURL,$options,$interactive=true)
	{
		require_once("oauth/library/OAuthStore.php");
		require_once("oauth/library/OAuthRequester.php");
		
		$this->displayName = $displayName;
		$this->callbackURL = $callbackURL;
		
		if (!isset($options['consumer_key']))
		{
			throw new Exception("consumer_key option must be set for initOAuth().");
		}
		$this->consumerKey = $options['consumer_key'];
		
		if (!isset($options['authorize_uri']))
		{
			throw new Exception("authorize_uri option must be set for initOAuth()");
		}
		$this->authorizeURI = $options['authorize_uri'];
		
		$options['access'] = $oauth_store;
		OAuthStore::instance("MOSQBSync", $options);
		
		if (!isset($this->store->realmId))
		{
			if (!$interactive)
			{
				// there is no user to authorize, yikes!
				throw new Exception("Not authorized.",401);
			}
			$this->_authorize();
		}
		
		if (!isset($this->store->BaseURI))
		{
			if ($this->isQBO())
			{
				$this->_getBaseURI();
			}
			else
			{
				$this->store->BaseURI = "https://services.intuit.com/sb";
			}
		}
	}
	
	/**
	 * Go through the OAuth authorization process (redirect to Intuit and wait for return back)
	 */
	protected function _authorize()
	{
		//  STEP 1:  If we do not have an OAuth token yet, go get one
		if (empty($_GET["oauth_token"]))
		{
			$getAuthTokenParams = array(
				//'scope' => 'https://rad.localdev/QuickBooks/',
				"xoauth_displayname"=>$this->displayName,
				"oauth_callback"=>$this->callbackURL
			);
			
			// get a request token
			$tokenResultParams = OAuthRequester::requestRequestToken($this->consumerKey, 0, $getAuthTokenParams);
	
			//  redirect to the intui authorization page, they will redirect back
			header("Location: " . $this->authorizeURI . "?oauth_token=" . $tokenResultParams['token']);
			exit;
		}
		
		//  STEP 2:  Get an access token
		$oauthToken = $_GET["oauth_token"];
		
		try
		{
			OAuthRequester::requestAccessToken($this->consumerKey, $oauthToken, 0, 'POST', $_GET);
		}
		catch (OAuthException2 $e)
		{
			// Something wrong with the oauth_token.
			// Could be:
			// 1. Was already ok
			// 2. We were not authorized
			/**
			 * @todo we should handle this better
			 */
			throw $e;
		}
		
		/*
		GET params
		'oauth_token' => string 'qyprdErXzJhXHMPLBANiGjOL24jkdJSM3Mvbn9nRp10DjA5X' (length=48)
		'oauth_verifier' => string 'ax7mwfk' (length=7)
		'realmId' => string '512439790' (length=9)
		'dataSource' => string 'QBO' (length=3)
		*/
		$this->store->realmId = $_GET['realmId'];
		$this->store->dataSource = $_GET['dataSource'];
		
		return true;
	}
	
	/**
	 * IntuitAnywhere API calls require a different URL depending on the user's account. This retrieves the URL to use and stores it for later use when making calls.
	 */
	protected function _getBaseURI()
	{
		$extra_headers = array(
			"Content-Type: application/xml",
			"Host: qbo.intuit.com",
			"Accept-Encoding: gzip,deflate",
		);
		
		// make the docs requestrequest.
		$request = new OAuthRequester("https://qbo.intuit.com/qbo1/rest/user/v2/".$this->store->realmId,'GET');
		$result = $request->doRequest(0,array(CURLOPT_HTTPHEADER=>$extra_headers,CURLOPT_ENCODING=>1));
		if ($result['code'] != 200)
		{
			$this->_handleError($result,"Failed to get BaseURI.");
		}
		
		/*
			<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
			<qbo:QboUser xmlns="http://www.intuit.com/sb/cdm/v2" xmlns:qbp="http://www.intuit.com/sb/cdm/qbopayroll/v1" xmlns:qbo="http://www.intuit.com/sb/cdm/qbo">
			  <qbo:LoginName>ipp.intuit.com+dr@gmail.com</qbo:LoginName>
			  <qbo:Ticket>V1-73-Q013395388397857cf0f26</qbo:Ticket>
			  <qbo:AgentId>489710665</qbo:AgentId>
			  <qbo:CurrentCompany><qbo:CompanyId>489711245</qbo:CompanyId>
				<qbo:BaseURI>https://qbo.intuit.com/qbo1</qbo:BaseURI>
				<qbo:SubscriptionStatus>SUBSCRIBED</qbo:SubscriptionStatus>
			  </qbo:CurrentCompany>
			</qbo:QboUser>
		 */
		$xml = new SimpleXMLElement($result['body']);
		$namespaces = $xml->getNamespaces(true);
		$qbo_xml = $xml->children($namespaces["qbo"]);
		
		$this->store->LoginName = (string)$qbo_xml->LoginName;
		$this->store->CompanyId = (string)$qbo_xml->CurrentCompany->CompanyId;
		$this->store->BaseURI = (string)$qbo_xml->CurrentCompany->BaseURI;
	}
	
	/**
	 * Handle an error from the API.
	 * @throws Exception Package the error into an exception.
	 */
	protected function _handleError($result,$message)
	{
		if (!isset($result['code']))
		{
			throw new Exception($message);
		}
		
		switch ($result['code'])
		{
			case 401:
				/*
				Status Code: 401

				Possible Causes:
				
				Authorization fields are not specified in the request header.
				Invalid OAuth fields are specified in the request header.
				For an Intuit Anywhere app, the OAuth access token has expired or the app has been disconnected, thus invalidating the OAuth access token.
				Example Response XML:
				
				<FaultInfo>
				  <Message>Unauthorized Request. The resource you are requesting requires authentication.</Message>
				  <ErrorCode>401</ErrorCode><Cause>SERVER</Cause>
				</FaultInfo>
				*/
				$message = "Unauthorized request: OAuth access token has expired or the app has been disconnected, thus invalidating the OAuth access token.";
				if ($result['body'])
				{
					$error_xml = new SimpleXMLElement($result['body']);
					$message = (string)$error_xml->FaultInfo->Message;
				}
				throw new Exception($message,401);
			case 400:
			case 402:
			case 403:
			case 404:
			case 405:
			case 406:
			case 407:
			case 408:
			case 409:
			case 410:
			case 411:
			case 412:
			case 413:
			case 414:
			case 415:
			case 416:
			case 417:
				/*
				Status Code: 4xx
				Possible Causes:
				
				An illegal data type is provided in an element of the XML request in a create or update.
				An illegal filter parameter (such as an illegal entity name) is specified in the request.
				*/
			case 500:
			case 501:
			case 502:
			case 503:
			case 504:
			case 505:
				/*
				Status Code: 5xx
				
				Possible Causes:
				
				Illegal permissions.
				Illegal values that are not verified in the validation process.
				Invalid data that the business logic verified.
				Example Response XML:
				*/
				
				/*
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<FaultInfo xmlns="http://www.intuit.com/sb/cdm/baseexceptionmodel/xsd">
  <Message>Error validating Header:</Message>
  <ErrorCode>RECEIVE_PAYMENT_REQUEST_BAD</ErrorCode>
  <Cause>RECEIVE_PAYMENT_INVALID_HEADER</Cause>
</FaultInfo>
				*/
				$error_xml = new SimpleXMLElement($result['body']);
				$message = (string)$error_xml->FaultInfo->Message;
				$code = (string)$error_xml->FaultInfo->ErrorCode;
				$cause = (string)$error_xml->FaultInfo->Cause;
				throw new Exception("$cause ($code): $message",$result['code']);
		}
	}
	
	/**
	 * Build the URL for the query
	 * @param string $objectName The name of the object we are querying.
	 * @param string|null $objectID The id of the object we are querying if we are querying a specific object and not a set.
	 * @return string The URL
	 */
	protected function _getQueryURL($objectName,$objectID=null)
	{
		if (!isset($this->store->BaseURI))
		{
			throw new Exception("BaseURI must be set to construct a query URL.");
		}
		if (!isset($this->store->realmId))
		{
			throw new Exception("realmId must be set to construct a query URL.");
		}
		
		$idDomain = null;
		if ($objectID && stripos($objectID,":")!==false)
		{
			list($idDomain,$objectID) = explode(":",$objectID);
		}
		
		$resource = "/";
		if ($this->isQBO())
		{
			$resource = "/resource/";
		}
		
		$url = $this->store->BaseURI . $resource . $objectName . "/v2/" . $this->store->realmId;
		if ($objectID)
		{
			$url .= "/" . $objectID;
			if ($this->isQBD() && $idDomain)
			{
				$url .= "?idDomain=" . $idDomain;
			}
		}
		return $url;
	}
	
	/**
	 * Run a query against the IntuitAnywhere API using our OAuth connection.
	 * @param string $objectName The object we are querying.
	 * @param string $objectID The id of the object we are querying if we are querying just one object (or NULL otherwise).
	 * @param string $method (default="GET"). HTTP method. GET/POST/DELETE
	 * @param string $params GET or POST parameters.
	 * @param string $body A POST body.
	 */
	public function query($objectName,$objectID=null,$method="GET",$params=null,$body=null)
	{
		$curl_opt = array(CURLOPT_ENCODING=>1,CURLOPT_TIMEOUT=>120,CURLOPT_CONNECTTIMEOUT=>20);

		if ($this->isQBO())
		{
			$content_type = "application/xml";
			if ($method==="POST" && $objectID===null && $body===null)
			{
				$content_type = "application/x-www-form-urlencoded";
			}
			$curl_opt[CURLOPT_HTTPHEADER]=array(
				"Content-Type: $content_type",
				"Host: qbo.intuit.com",
				"Accept-Encoding: gzip,deflate",
			);
		}
		else
		{
			// QBD
			$curl_opt[CURLOPT_HTTPHEADER]=array(
				"Content-Type: text/xml",
				"Host: services.intuit.com",
				"Accept-Encoding: gzip,deflate",
			);
			
		}
		
		$request = new OAuthRequester($this->_getQueryURL($objectName,$objectID),$method,$params,$body);
		$result = $request->doRequest(0,$curl_opt);
		
		if ($result['code'] == 200)
		{
			return $result['body'];
		}
		
		// check to see if they sent us a redirect, not sure why they do this
		if ($result['code'] == 302)
		{
			if ($result['headers']['location'])
			{
				$request = new OAuthRequester($result['headers']['location'],$method,$params,$body);
				$result = $request->doRequest(0,$curl_opt);
				if ($result['code']==200)
				{
					return $result['body'];
				}
			}
		}

		$this->_handleError($result,"Query for $objectName failed.");
		return false;
	}
	
	/**
	 * Query Intuit to get information about the user.
	 * @return array Array of user information: "handle", "FirstName", "LastName", "EmailAddress", "ScreenName", "IsVerified"
	 */
	public function getCurrentUser()
	{
		$request = new OAuthRequester("https://appcenter.intuit.com/api/v1/user/current","GET");
		$result = $request->doRequest(0);
		
		if ($result['code'] != 200)
		{
			$this->_handleError($result,"Could not get current user.");
			return false;
		}
		
		$xml = new SimpleXMLElement($result['body']);
		
		$FirstName = (string)$xml->User->FirstName;
		$LastName = (string)$xml->User->LastName;
		$ScreenName = (string)$xml->User->ScreenName;
		$EmailAddress = (string)$xml->User->EmailAddress;
		
		$handle = $ScreenName;
		if (strlen(trim($handle))<=0)
		{
			$handle = $FirstName . " " . $LastName;
		}
		if (strlen(trim($handle))<=0)
		{
			$handle = $EmailAddress;
		}
		
		/*
		 <FirstName>John</FirstName>
		 <LastName>Doe</LastName>
		 <EmailAddress>JohnDoe@g88.net</EmailAddress>
		 <IsVerified>true</IsVerified>
		 */
		return array(
			"handle"=>$handle,
			"FirstName"=>$FirstName,
			"LastName"=>$LastName,
			"EmailAddress"=>$EmailAddress,
			"ScreenName"=>$ScreenName,
			"IsVerified"=>(string)$xml->User->IsVerified
		);
	}
	
	/**
	 * Get the HTML code for the BlueDot menu.
	 * @return string HTML for the BlueDot menu.
	 */
	public function getMenu()
	{
		$request = new OAuthRequester("https://appcenter.intuit.com/api/v1/Account/AppMenu","GET");
		$result = $request->doRequest(0);
		
		if ($result['code'] != 200)
		{
			$this->_handleError($result,"Could not get menu code.");
			return false;
		}
		
		return $result['body'];
	}
	
	/**
	 * Issue an OAuth disconnect to destroy the OAuth connection. Re-authorization will be neccessary after this point.
	 * @return boolean true=success, false=failed.
	 */
	public function disconnect()
	{
		$request = new OAuthRequester("https://appcenter.intuit.com/api/v1/Connection/Disconnect","GET");
		$result = $request->doRequest(0);
		
		if ($result['code'] != 200)
		{
			$this->_handleError($result,"Could not disconnect user.");
			return false;
		}
		
		return true;
	}
	
	/**
	 * Renew the OAuth connection to extend the TTL of the token.
	 * @return boolean true=success, false=failed.
	 */
	public function reconnect()
	{
		$request = new OAuthRequester("https://appcenter.intuit.com/api/v1/Connection/Reconnect","GET");
		$result = $request->doRequest(0);
		
		if ($result['code'] != 200)
		{
			$this->_handleError($result,"Could not disconnect user.");
			return false;
		}
		
		$xml = new SimpleXMLElement($result['body']);
		
		$OAuthToken = (string)$xml->OAuthToken;
		$OAuthTokenSecret = (string)$xml->OAuthTokenSecret;
		
		// hack to install the new tokens for oauth
		$oauth_store = OAuthStore::instance();
		$oauth_store->addServerToken(null, 'access', $OAuthToken, $OAuthTokenSecret, null, null);
		
		return true;
	}
}

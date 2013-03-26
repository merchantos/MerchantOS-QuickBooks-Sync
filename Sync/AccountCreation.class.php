<?php
require_once("lib/Validation.class.php");
require_once("MerchantOS/Account.class.php");
require_once("MerchantOS/SystemAPIKey.class.php");
require_once("MerchantOS/SystemAPIKeyAccess.class.php");
require_once("MerchantOS/Shop.class.php");
require_once("IntuitAnywhere/CompanyMetaData.class.php");

/**
 * Sync_AccountCreation: Create an account within MerchantOS and setup a sync account here to go with it.
 *
 * @package  Sync
 * @author   Justin Laing <justin@merchantos.com>
 */
class Sync_AccountCreation
{
	/**
	 * @var Sync_Database
	 */
	protected $_db;
	/**
	 * @var IntuitAnywhere
	 */
	protected $_ia;
	/**
	 * @var SessionAccess
	 */
	protected $_login_session;
	/**
	 * @var SessionAccess
	 */
	protected $_qb_session;
	/**
	 * @var SessionAccess
	 */
	protected $_mos_session;
	/**
	 * @var SessionAccess
	 */
	protected $_oauth_session;
	
	/**
	 * Get an instance of this class setup so we can use it.
	 * @param Sync_Database $db Database access wrapper object.
	 * @param IntuitAnywhere $ianywhere Instance of IntuitAnywhere that is ready to use to access IntuitAnywhere API
	 * @param SessionAccess $loginSessionAccess Get session access created from new SessionAccess("login") typically
	 * @param SessionAccess $qbSessionAccess Get session access created from new SessionAccess("qb") typically
	 * @param SessionAccess $merchantosSessionAcccess Get session access created from new SessionAccess("merchantos") typically
	 * @param SessionAccess $oauthSessionAccess Get session access created from new SessionAccess("oauth") typically
	 * @return Sync_AccountCreation The instance.
	 */
	public function __construct($db,$ianywhere,$loginSessionAccess,$qbSessionAccess,$merchantosSessionAcccess,$oauthSessionAccess)
	{
		$this->_db = $db;
		$this->_ia = $ianywhere;
		$this->_login_session = $loginSessionAccess;
		$this->_qb_session = $qbSessionAccess;
		$this->_mos_session = $merchantosSessionAcccess;
		$this->_oauth_session = $oauthSessionAccess;
	}
	
	/**
	 * Create a MOS account and return an SystemCustomer object so we can create an API key on it
	 * @param string $email The email address for the new account owner login
	 * @return MerchantOS_SystemCustomer The data object for the created MOS account
	 */
	public function createMOSAccount()
	{
		/**
		 * Get vars we set on OpenID return
		 * 
		 */
		$openid = $this->_login_session->account_creation_openid;
		$firstName = $this->_login_session->account_creation_first_name;
		$lastName = $this->_login_session->account_creation_last_name;
		
		/**
		 * Get email user entered for account creation (defaulted to email from OpenID)
		 * 
		 */
		$email = $this->_login_session->account_creation_email;
		
		/**
		 * Create a random password for the user, they can use the Intuit login button to login
		 * 
		 */
		$password = md5(mt_rand() . "!54lt'n'p3pp3r&");
		
		/**
		 * Validate the email address we now have
		 * 
		 */
		if (!lib_Validation::ValidateAddress($email))
		{
			throw new Exception("$email email address is invalid.");
		}
		
		/**
		 * get company meta data for name/phone/address
		 *
		 */
		$company_meta_data = $this->_getCompanyMetaData();
		
		$shop_name = $company_meta_data->CompanyName;
		$phone = $company_meta_data->getCompanyPhone();
		$address = $company_meta_data->getCompanyAddress();
	
		/**
		 * fix phone if it is not valid
		 *
		 */
		if (empty($phone) or strlen(preg_replace('/\D/i', '', $phone)) < 10)
		{
			$phone = str_pad($phone,10,"0",STR_PAD_LEFT);
		}
		
		/**
		 * Create account
		 * 
		 */
		$account = $this->_createMerchantOSAccount($shop_name,$email,$firstName,$lastName,$phone,$password);
		
		if (!isset($account->systemCustomerID))
		{
			throw new Exception("Could not create MerchantOS account: " . (string)$account->message);
		}
		$systemCustomerID = (integer)$account->systemCustomerID;
		$systemUserID = (integer)$account->SystemUsers->SystemUser->systemUserID;
		$accountRedirect = (string)$account->redirect;
		
		/**
		 * Create Customer API Key
		 * 
		 */
		$apiKey = $this->_createMOSAPIKey($systemCustomerID,$systemUserID);
		
		/**
		 * Associate the OpenID with the new user login/account
		 * 
		 */
		$this->_associateMOSOpenID($systemUserID,$openid);
		
		/**
		 * Update Shop name to value of $shop_name
		 *
		 */
		$this->_updateMOSShop($apiKey,$systemCustomerID,$shop_name,$address);
		
		/**
		 * Setup our sync account and session
		 *
		 */
		$this->_createQBSyncAccount($systemCustomerID,$apiKey,$accountRedirect);
		
		/**
		 * Return the redirect we'll use to send the user on to MOS login
		 */
		return $accountRedirect . "?form_name=login&login_name=" . urlencode($email) . "&login_password=" . urlencode($password) . "&redirect_after_login=" . urlencode("openid.php?form_name=intuit&return=1");
	}
	
	/**
	 * after we've created our account we should have an API key, we can do the stuff normally done in session.inc.php
	 * @param integer $mos_api_account_num The account number for the newly created mos account
	 * @param string $mos_api_key The API key for the newly created mos account
	 * @param string $mos_return_url Where should we send the user when they return to MOS. This should be grabbed form the SystemAccount record.
	 */
	protected function _createQBSyncAccount($systemCustomerID,$apiKey,$accountRedirect)
	{
		$accountRedirect = str_replace("register.php","openid.php",$accountRedirect);
		if (stripos($accountRedirect,"?")!==false)
		{
			$accountRedirect .= "&form_name=intuit&return=1";
		}
		else
		{
			$accountRedirect .= "?form_name=intuit&return=1";
		}
		
		// setup credentials for pulling from MOS API
		$this->_mos_session->api_key = $apiKey;
		$this->_mos_session->return_url = $accountRedirect;
		$this->_mos_session->api_account = $systemCustomerID;
		
		// create our QB Sync account
		$this->_login_session->account_id = $this->_db->writeAccount($this->_mos_session->api_key);
		
		// save our OAuth to the DB
		$oauth_array = $this->_oauth_session->getArray();
		$qb_array = $this->_qb_session->getArray();
		
		// when should we reconnect/renew another access token?
		$renew = time() + (60*60*24*30*4); // 4 months/120 days from now, to be safe (tokens last 6 months).
	
		$this->_db->writeOAuth($this->_login_session->account_id,array("oauth"=>$oauth_array,"qb"=>$qb_array,"renew"=>$renew));
	}
	
	protected function _getCompanyMetaData()
	{
		// grab the company data from QB
		$ia_companies = $this->_getIntuitAnywhereCompanies();
		if (count($ia_companies)!=1)
		{
			throw new Exception("Could not load QuickBooks company data for account sign up.");
		}
		return $ia_companies[0];
	}
	
	protected function _updateMOSShop($apiKey,$systemCustomerID,$shop_name,$address)
	{
		$shops = $this->_listAllMerchantOSShops($apiKey,$systemCustomerID);
	
		// Update first  shop with $shop_name
		$shop_updates = array("name"=>$shop_name);
		if ($address)
		{
			$shop_updates['address'] = $address;
		}
		
		$this->_updateMerchantOSShops($apiKey,$systemCustomerID,$shops[0]['shopID'],$shop_updates);
	}
	
	protected function _createMOSAPIKey($systemCustomerID,$systemUserID)
	{
		// READY TO PUT THIS IN NOW!
		// use $systemCustomerID $systemUserID with MOS_API_CLIENT_ID to create the key below
		// make a SystemAPIKey -> create
		$api_key = $this->_createMerchantOSSystemAPIKey($systemCustomerID,$systemUserID);
		if (!isset($api_key->apiKey))
		{
			throw new Exception("Could not create MerchantOS API key: " . (string)$api_key->message);
		}
		$apiKey = (string)$api_key->apiKey;
		$systemAPIKeyID = (integer)$api_key->systemAPIKeyID;
		// make a SystemAPIKeyAccess for the key just made -> create
		$api_key_access = $this->_createMerchantOSSystemAPIKeyAccess($systemAPIKeyID);
		if (!isset($api_key_access->systemAPIKeyAccessID))
		{
			throw new Exception("Could not create MerchantOS API key access: " . (string)$api_key->message);
		}
		return $apiKey;
	}
	
	protected function _associateMOSOpenID($systemUserID,$openid)
	{
		$openid = $this->_associateMerchantOSOpenID($systemUserID,$openid);
		if (!isset($openid->systemOpenIDID))
		{
			throw new Exception("Could not associate OpenID with MerchantOS SystemUser: " . (string)$openid->message);
		}
	}
	
	/**
	 * Use MerchantOS_Account to create a new object on MOS API. Useful to override in mock object for testing.
	 * @return SimpleXMLElement
	 * @codeCoverageIgnore
	 */
	protected function _createMerchantOSAccount($shop_name,$email,$firstName,$lastName,$phone,$password)
	{
		$mos_account = new MerchantOS_Account(MOS_SYSTEM_API_KEY);
		return $mos_account->create($shop_name,$email,$firstName,$lastName,$phone,$password);
	}
	/**
	 * Use MerchantOS_Shop to list shops from the MOS API. Useful to override in mock object for testing.
	 * @return array Array of XML shop results
	 * @codeCoverageIgnore
	 */
	protected function _listAllMerchantOSShops($apiKey,$systemCustomerID)
	{
		$mos_shop = new MerchantOS_Shop($apiKey,$systemCustomerID);
		return $mos_shop->listAll();
	}
	/**
	 * Use MerchantOS_Shop to update shops in the MOS API. Useful to override in mock object for testing.
	 * @codeCoverageIgnore
	 */
	protected function _updateMerchantOSShops($apiKey,$systemCustomerID,$shopID,$shop_updates)
	{
		$mos_shop = new MerchantOS_Shop($apiKey,$systemCustomerID);
		$mos_shop->update($shopID,$shop_updates);
	}
	/**
	 * Use IntuitAnywhere_CompanyMetaData to query IntuitAnywhere API to get company  meta data. Use this function to override in tests.
	 * @return array Array of IntuitAnywhere_CompanyMetaData
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereCompanies()
	{
		$ia_company = new IntuitAnywhere_CompanyMetaData($this->_ia);
		return $ia_company->listAll();
	}
	/**
	 * Get a MerchantOS_SystemAPIKey object, useful for testing so a mock object can override
	 * @return SimpleXMLElement
	 * @codeCoverageIgnore
	 */
	protected function _createMerchantOSSystemAPIKey($systemCustomerID,$systemUserID)
	{
		$mos_api_key = new MerchantOS_SystemAPIKey(MOS_SYSTEM_API_KEY);
		return $mos_api_key->create($systemCustomerID,$systemUserID,MOS_API_CLIENT_ID);
	}
	/**
	 * Use MerchantOS_SystemAPIKeyAccess to create a new object on MOS API. Useful to override in mock object for testing.
	 * @return SimpleXMLElement
	 * @codeCoverageIgnore
	 */
	protected function _createMerchantOSSystemAPIKeyAccess($systemAPIKeyID)
	{
		$mos_api_key_access = new MerchantOS_SystemAPIKeyAccess(MOS_SYSTEM_API_KEY);
		return $mos_api_key_access->create($systemAPIKeyID);
	}
	/**
	 * Use MerchantOS_SystemOpenID to associate a new openid on MOS API. Useful to override in mock object for testing.
	 * @return SimpleXMLElement
	 * @codeCoverageIgnore
	 */
	protected function _associateMerchantOSOpenID($systemUserID,$openid)
	{
		$mos_openid = new MerchantOS_SystemOpenID(MOS_SYSTEM_API_KEY);
		return $mos_openid->create($systemUserID,$openid);
	}
}

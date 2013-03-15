<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("database.inc.php");
require_once("lib/Validation.class.php");

require_once("MerchantOS/Account.class.php");
require_once("MerchantOS/SystemAPIKey.class.php");
require_once("MerchantOS/SystemAPIKeyAccess.class.php");
require_once("MerchantOS/Shop.class.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");
require_once("IntuitAnywhere/CompanyMetaData.class.php");

$login_sess_access = new SessionAccess("login");

if (!$login_sess_access->account_creation || !$login_sess_access->account_creation_email)
{
	throw new Exception("Could not create account.");
}

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

// setup oauth but don't do any user authorization (that should have been done in oauth.php before we got here)
$ianywhere = new IntuitAnywhere($qb_sess_access);
$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = don't do auth

if (!$ianywhere->isUserAuthorized())
{
	throw new Exception("Could not establish OAuth connection to Intuit.");
}

try
{
	$accountRedirectURL = _createMOSAccount($ianywhere);
}
catch (Exception $e)
{
	// disconnect so we don't pay for this connection
	$ianywhere->disconnect();
	// rethrow
	throw $e;
}

header("location: $accountRedirectURL");

/**
 * Create a MOS account and return an SystemCustomer object so we can create an API key on it
 * @param string $email The email address for the new account owner login
 * @return MerchantOS_SystemCustomer The data object for the created MOS account
 */
function _createMOSAccount($ianywhere)
{
	$login_sess_access = new SessionAccess("login");
	
	/**
	 * Get vars we set on OpenID return
	 * 
	 */
	$openid = $login_sess_access->account_creation_openid;
	$firstName = $login_sess_access->account_creation_first_name;
	$lastName = $login_sess_access->account_creation_last_name;
	
	/**
	 * Get email user entered for account creation (defaulted to email from OpenID)
	 * 
	 */
	$email = $login_sess_access->account_creation_email;
	
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
	$company_meta_data = _getCompanyMetaData($ianywhere);
	
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
    $mos_account = new MerchantOS_Account(MOS_SYSTEM_API_KEY);
    $account = $mos_account->create($shop_name,$email,$firstName,$lastName,$phone,$password);
	
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
	$apiKey = _createMOSAPIKey($systemCustomerID,$systemUserID);
	
	/**
	 * Associate the OpenID with the new user login/account
	 * 
	 */
	_associateMOSOpenID($systemUserID,$openid);
	
    /**
     * Update Shop name to value of $shop_name
     *
     */
	_updateMOSShop($apiKey,$systemCustomerID,$shop_name,$address);
    
    /**
     * Setup our sync account and session
     *
     */
	_createQBSyncAccount($systemCustomerID,$apiKey,$accountRedirect);
	
	/**
	 * Return the redirect we'll use to send the user on to MOS login
	 */
	return $accountRedirect . "?form_name=login&login_name=" . urlencode($email) . "&login_password=" . urlencode($password) . "&redirect_after_login=" . urlencode("openid.php?form_name=intuit&return=1");
}

function _getCompanyMetaData($ianywhere)
{
	// grab the company data from QB
	$ia_company = new IntuitAnywhere_CompanyMetaData($ianywhere);
	$ia_companies = $ia_company->listAll();
	if (count($ia_companies)!=1)
	{
		throw new Exception("Could not load QuickBooks company data for account sign up.");
	}
	return $ia_companies[0];
}

function _updateMOSShop($apiKey,$systemCustomerID,$shop_name,$address)
{
	$mos_shop = new MerchantOS_Shop($apiKey,$systemCustomerID);
    $shops = $mos_shop->listAll();

    // Update first  shop with $shop_name
	$shop_updates = array("name"=>$shop_name);
	if ($address)
	{
		$shop_updates['address'] = $address;
	}
    $mos_shop->update($shops[0]['shopID'],$shop_updates);
}

function _createMOSAPIKey($systemCustomerID,$systemUserID)
{
	// READY TO PUT THIS IN NOW!
	// use $systemCustomerID $systemUserID with MOS_API_CLIENT_ID to create the key below
	// make a SystemAPIKey -> create
	$mos_api_key = new MerchantOS_SystemAPIKey(MOS_SYSTEM_API_KEY);
	$api_key = $mos_api_key->create($systemCustomerID,$systemUserID,MOS_API_CLIENT_ID);
	if (!isset($api_key->apiKey))
	{
		throw new Exception("Could not create MerchantOS API key: " . (string)$api_key->message);
	}
	$apiKey = (string)$api_key->apiKey;
	$systemAPIKeyID = (integer)$api_key->systemAPIKeyID;
	// make a SystemAPIKeyAccess for the key just made -> create
	$mos_api_key_access = new MerchantOS_SystemAPIKeyAccess(MOS_SYSTEM_API_KEY);
	$api_key_access = $mos_api_key_access->create($systemAPIKeyID);
	if (!isset($api_key_access->systemAPIKeyAccessID))
	{
		throw new Exception("Could not create MerchantOS API key access: " . (string)$api_key->message);
	}
	return $apiKey;
}

function _associateMOSOpenID($systemUserID,$openid)
{
	$mos_openid = new MerchantOS_SystemOpenID(MOS_SYSTEM_API_KEY);
	$openid = $mos_openid->create($systemUserID,$openid);
	if (!isset($openid->systemOpenIDID))
	{
		throw new Exception("Could not associate OpenID with MerchantOS SystemUser: " . (string)$openid->message);
	}
}

/**
 * after we've created our account we should have an API key, we can do the stuff normally done in session.inc.php
 * @param integer $mos_api_account_num The account number for the newly created mos account
 * @param string $mos_api_key The API key for the newly created mos account
 * @param string $mos_return_url Where should we send the user when they return to MOS. This should be grabbed form the SystemAccount record.
 */
function _createQBSyncAccount($systemCustomerID,$apiKey,$accountRedirect)
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
	
	$qb_sess_access = new SessionAccess("qb");
	$merchantos_sess_access = new SessionAccess("merchantos");
	$login_sess_access = new SessionAccess("login");
	$oauth_sess_access = new SessionAccess("oauth");
	
	// setup credentials for pulling from MOS API
	$merchantos_sess_access->api_key = $apiKey;
	$merchantos_sess_access->return_url = $accountRedirect;
	$merchantos_sess_access->api_account = $systemCustomerID;
	
	// create our QB Sync account
	$login_sess_access->account_id = mosqb_database::writeAccount($merchantos_sess_access->api_key);
	
	// save our OAuth to the DB
	$oauth_array = $oauth_sess_access->getArray();
	$qb_array = $qb_sess_access->getArray();
	
	// when should we reconnect/renew another access token?
	$renew = time() + (60*60*24*30*4); // 4 months/120 days from now, to be safe (tokens last 6 months).

	mosqb_database::writeOAuth($login_sess_access->account_id,array("oauth"=>$oauth_array,"qb"=>$qb_array,"renew"=>$renew));
}

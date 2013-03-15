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
	
$email = $_GET['email'];

$accountRedirectURL = _createMOSAccount($email);

header("location $accountRedirectURL");

function _getCompanyMetaData()
{
	$login_sess_access = new SessionAccess("login");
	
	$oauth_sess_access = new SessionAccess("oauth");
	$login_sess_access = new SessionAccess("login");
	
	$qb_sess_access = new SessionAccess("qb");
	
	// setup oauth but don't do any user authorization (that should have been done in oauth.php before we got here)
	$ianywhere = new IntuitAnywhere($qb_sess_access);
	$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = don't do auth
	
	$email = false;
	
	// we already filled CurrentUser in on oauth.php, use it here as a possible login email
	$user = $qb_sess_access->CurrentUser;
	
	// see if $user is an email address, if it is use it as the deafult login name
	if (lib_Validation::ValidateAddress($user['EmailAddress']))
	{
		$email = $user['EmailAddress'];
	}
	
	// grab the company data from QB
	$ia_company = new IntuitAnywhere_CompanyMetaData($ianywhere);
	$ia_companies = $ia_company->listAll();
	if (count($ia_companies)!=1)
	{
		throw new Exception("Could not load QuickBooks company data for account sign up.");
	}
	$ia_company = $ia_companies[0];
}

/**
 * Create a MOS account and return an SystemCustomer object so we can create an API key on it
 * @param string $email The email address for the new account owner login
 * @return MerchantOS_SystemCustomer The data object for the created MOS account
 */
function _createMOSAccount($email)
{
	throw new Exception("Test!");

	$login_sess_access = new SessionAccess("login");
	
	$openid = $login_sess_access->createaccount_openid;
	$firstName = $login_sess_access->createaccount_first_name;
	$lastName = $login_sess_access->createaccount_last_name;
	
	$password = md5(mt_rand() . "!54lt'n'p3pp3r&");
	
    // @todo - we're probably going to need some more stringent validation here (duh)
	if (!lib_Validation::ValidateAddress($email))
	{
		throw new Exception("$email email address is invalid.");
	}
	// Make sure the password is submitted and valid length
	if (strlen($password) < 5)
	{
		throw new Exception('Password must be at least 6 characters');
	}
	
	// this will create a Intuit Connection that we will pay for!
	$company_meta_data = _getCompanyMetaData();
	
	$shop_name = $company_meta_data->CompanyName;
	$phone = $company_meta_data->getCompanyPhone();
	$address = $company_meta_data->getCompanyAddress();

	// Make sure phone is submitted and valid
	if (empty($phone) or strlen(preg_replace('/\D/i', '', $phone)) < 10)
	{
		$phone = str_pad($phone,10,"0",STR_PAD_LEFT);
	}
	
    /**
 	 * Create account
 	 */
    $mos_account = new MerchantOS_Account(MOS_SYSTEM_API_KEY);
    $account = $mos_account->create($shop_name,$email,$firstName,$lastName,$phone,$password);
	
	if (!isset($account->systemCustomerID))
	{
		/**
		 * @todo Disconnect Intuit Connection
		 */
		throw new Exception("Could not create MerchantOS account: " . (string)$account->message);
	}
	$systemCustomerID = (integer)$account->systemCustomerID;
	$systemUserID = (integer)$account->SystemUsers->SystemUser->systemUserID;
	
    /**
  	 * Create Customer API Key
  	 * We need a control that can create an API key without user interaction, or the Account create method needs to be able to do this optionally (maybe best option)
  	 */
	// READY TO PUT THIS IN NOW!
	// use $systemCustomerID $systemUserID with MOS_API_CLIENT_ID to create the key below
	// make a SystemAPIKey -> create
	$mos_api_key = new MerchantOS_SystemAPIKey(MOS_SYSTEM_API_KEY);
	$api_key = $mos_api_key->create($systemCustomerID,$systemUserID,MOS_API_CLIENT_ID);
	if (!isset($api_key->apiKey))
	{
		/**
		 * @todo Disconnect Intuit Connection
		 */
		throw new Exception("Could not create MerchantOS API key: " . (string)$api_key->message);
	}
	$apiKey = (string)$api_key->apiKey;
	$systemAPIKeyID = (integer)$api_key->systemAPIKeyID;
	// make a SystemAPIKeyAccess for the key just made -> create
	$mos_api_key_access = new MerchantOS_SystemAPIKeyAccess(MOS_SYSTEM_API_KEY);
	$api_key_access = $mos_api_key_access->create($systemAPIKeyID);
	if (!isset($api_key_access->systemAPIKeyAccessID))
	{
		/**
		 * @todo Disconnect Intuit Connection
		 */
		throw new Exception("Could not create MerchantOS API key access: " . (string)$api_key->message);
	}
	
    /**
     * Setup customer-level API access
     *
     **/ 
	$merchantos_sess_access = new SessionAccess("merchantos");
	$merchantos_sess_access->api_key = $apiKey;
	$merchantos_sess_access->api_account = $systemCustomerID;
	
	$mos_return_url = (string)$account->redirect;
	
    /**
     * Update Shop name to value of $shop_name
     *
     **/ 
    $mos_shop = new MerchantOS_Shop($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
    $shops = $mos_shop->listAll();

    // Update first  shop with $shop_name
	$shop_updates = array("name"=>$shop_name);
	if ($address)
	{
		$shop_updates['address'] = $address;
	}
    $mos_shop->update($shops[0]['shopID'],$shop_updates);
	
	_createQBSyncAccount($merchantos_sess_access->api_account,$merchantos_sess_access->api_key,$mos_return_url);
	
	return $mos_return_url . "?form_name=login&login_name=" . urlencode($email) . "&login_password=" . urlencode($password) . "&redirect_after_login=" . urlencode("openid.php?form_name=intuit&return=1");
}

/**
 * after we've created our account we should have an API key, we can do the stuff normally done in session.inc.php
 * @param integer $mos_api_account_num The account number for the newly created mos account
 * @param string $mos_api_key The API key for the newly created mos account
 * @param string $mos_return_url Where should we send the user when they return to MOS. This should be grabbed form the SystemAccount record.
 */
function _createQBSyncAccount($mos_api_account_num,$mos_api_key,$mos_return_url)
{
	$mos_return_url = str_replace("register.php","openid.php",$mos_return_url);
	if (stripos($mos_return_url,"?")!==false)
	{
		$mos_return_url .= "&form_name=intuit&return=1";
	}
	else
	{
		$mos_return_url .= "?form_name=intuit&return=1";
	}
	
	$qb_sess_access = new SessionAccess("qb");
	$merchantos_sess_access = new SessionAccess("merchantos");
	$login_sess_access = new SessionAccess("login");
	$oauth_sess_access = new SessionAccess("oauth");
	
	// setup credentials for pulling from MOS API
	$merchantos_sess_access->api_key = $mos_api_key;
	$merchantos_sess_access->return_url = $mos_return_url;
	$merchantos_sess_access->api_account = $mos_api_account_num;
	
	// create our QB Sync account
	$login_sess_access->account_id = mosqb_database::writeAccount($merchantos_sess_access->api_key);
	
	// save our OAuth to the DB
	$oauth_array = $oauth_sess_access->getArray();
	$qb_array = $qb_sess_access->getArray();
	
	// when should we reconnect/renew another access token?
	$renew = time() + (60*60*24*30*4); // 4 months/120 days from now, to be safe (tokens last 6 months).

	mosqb_database::writeOAuth($login_sess_access->account_id,array("oauth"=>$oauth_array,"qb"=>$qb_array,"renew"=>$renew));
}

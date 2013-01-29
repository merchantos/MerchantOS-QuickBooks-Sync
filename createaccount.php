<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");
require_once("database.inc.php");
require_once("view.inc.php");

require_once("lib/Validation.class.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");
require_once("IntuitAnywhere/CompanyMetaData.class.php");

$oauth_sess_access = new SessionAccess("oauth");
$login_sess_access = new SessionAccess("login");

if (isset($login_sess_access->account_id) && $login_sess_access->account_id>0)
{
	// this user is already logged in, they shouldn't be here
	header("location: ./");
	exit;
}
if (!isset($login_sess_access->account_creation) || !$login_sess_access->account_creation)
{
	// user did not get here through incoming.php -> oauth.php
	// send them to the merchantos signup page
	header("location: http://merchantos.com/signup/");
	exit;
}

function _displaySignupForm()
{
	$qb_sess_access = new SessionAccess("qb");

	// setup oauth but don't do any user authorization (that should have been done in oauth.php before we got here)
	$ianywhere = new IntuitAnywhere($qb_sess_access);
	$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = don't do auth
	
	$email = false;
	
	// we already filled CurrentUser in on oauth.php, use it here as a possible login email
	$user = $qb_sess_access->CurrentUser = $user;
	// see if $user is an email address, if it is use it as the deafult login name
	if (helpers_Validation::ValidateAddress($user))
	{
		$email = $user;
	}
	
	// grab the company data from QB
	$ia_company = new IntuitAnywhere_CompanyMetaData($ianywhere);
	$ia_companies = $ia_company->listAll();
	if (count($ia_companies)!=1)
	{
		throw new Exception("Could not load QuickBooks company data for account sign up.");
	}
	$ia_company = $ia_companies[0];
	
	$shop_name = $ia_company->CompanyName;
	if (!$email)
	{
		$email = $ia_company->getCompanyEmail();
	}
	$phone = $ia_company->getCompanyPhone();
	$address = $ia_company->getCompanyAddress();
	
	render_view('createaccount', $locals = array('email' => $email, 'shop_name' => $shop_name, 'phone' => $phone));
	return true;
}

/**
 * Create a MOS account and return an SystemCustomer object so we can create an API key on it
 * @param string $email The email address for the new account owner login
 * @param string $password The password for the account owner login
 * @param string $phone The phone for the business
 * @param string $address1 The first line of the address for the business
 * @param string $address2 The second line of the address for the business
 * @param string $city The city for the business address
 * @param string $state The state for the business address
 * @param string $zip The zip code / postal code for the business address
 * @return MerchantOS_SystemCustomer The data object for the created MOS account
 */
function _createMOSAccount($email,$password,$shop_name,$phone)
{
    // @todo - we're probably going to need some more stringent validation here (duh)
	if (!helpers_ValidateAddress::ValidateAddress($email))
	{
		throw new Exception("$email email address is invalid.");
	}
	// Make sure the password is submitted and valid length
	if (strlen($password) < 5)
	{
		$errors[1] = 'Password must be at least 6 characters';
	}
	// Make sure phone is submitted and valid
	if ($phone == '' OR strlen(preg_replace('/\D/i', '', $phone)) < 9)
	{
		$errors[2] = 'Phone must be at least 10 digits';
	}
	
    /**
 	 * Create account
 	 */
    $mos_account = new MerchantOS_Account(MOS_API_KEY);
    $account = $mos_account->create($shop_name,$email,$phone,$password);
	
    /**
  	 * Create Customer API Key
  	 * @todo after account creation create an API key
  	 * We need a control that can create an API key without user interaction, or the Account create method needs to be able to do this optionally (maybe best option)
  	 */
	$mos_api_key = null;


    /**
     * Setup customer-level API access
     *
     **/ 
	$merchantos_sess_access = new SessionAccess("merchantos");
	$merchantos_sess_access->api_key = $mos_api_key;
	$merchantos_sess_access->api_account = (integer)$account->SystemCustomerID;
	
	$mos_return_url = (string)$account->redirect;
	
    /**
     * Update Shop name to value of $shop_name
     *
     **/ 
    $mos_shop = new MerchantOS_Shop($merchantos_sess_access->api_key,$merchantos_sess_access->api_account);
    $shops = $mos_shop->listAll();

    // Update first  shop with $shop_name
    $mos_shop->updateShopName($shops[0]['shopID'],$shop_name);
	
	_createQBSyncAccount($merchantos_sess_access->api_account,$merchantos_sess_access->api_key,$mos_return_url);
}

/**
 * after we've created our account we should have an API key, we can do the stuff normally done in session.inc.php
 * @param integer $mos_api_account_num The account number for the newly created mos account
 * @param string $mos_api_key The API key for the newly created mos account
 * @param string $mos_return_url Where should we send the user when they return to MOS. This should be grabbed form the SystemAccount record.
 */
function _createQBSyncAccount($mos_api_account_num,$mos_api_key,$mos_return_url)
{
	$qb_sess_access = new SessionAccess("qb");
	$merchantos_sess_access = new SessionAccess("merchantos");
	$login_sess_access = new SessionAccess("login");
	
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

header("location: ./");

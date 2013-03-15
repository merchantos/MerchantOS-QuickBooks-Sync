<?php

// initialize the session and handle any session related get params

// we are using memcache to store our sessions
require_once("lib/MemcacheSession.class.php");
require_once("lib/SessionAccess.class.php");

class lib_Session
{
	/**
	 * Start the session
	 * @param Sync_Database $db Database connection to read account info from if the session is new
	 * @param boolean $needkey Do we require a key to start up a new session ($_GET['key'])
	 */
	public static function init($db,$needkey=true)
	{
		MemcacheSession::Init(60); // this registers the sesion handlers, 60 = minutes till session expire
		
		if (!isset($_REQUEST[session_name()]))
		{
			if (self::_isKeySet())
			{
				session_id(self::_getKey());
				session_start();
			}
			else
			{
				if ($needkey)
				{
					throw new Exception("This application must be accessed through MerchantOS -> Admin -> Setup QuickBooks Sync.");
					exit;
				}
				else
				{
					session_start();
				}
			}
		}
		else
		{
			session_start();
		}
		
		if (self::_isKeySet())
		{
			self::_initMOSKey(self::_getKey());
		}
	}
	
	protected static function _isKeySet()
	{
		return isset($_GET['key']);
	}
	protected static function _getKey()
	{
		return $_GET['key'];
	}
	/**
	 * @return Sync_Database
	 */
	protected static function _getDB()
	{
		require_once("Sync/Database.class.php");
		return new Sync_Database();
	}
	
	public static function reset()
	{
		$_SESSION = array();
	}
	
	protected static function _initMOSKey($key)
	{
		$merchantos_sess_access = new SessionAccess("merchantos");
		$login_sess_access = new SessionAccess("login");
		
		// this is where we will eventually either create a new account or login based on a login credential of $_POST['key']
		$merchantos_sess_access->api_key = $_GET['key'];
		if (isset($_GET['return_url']))
		{
			$merchantos_sess_access->return_url = $_GET['return_url'];
		}
		if (isset($_GET['account']))
		{
			$merchantos_sess_access->api_account = $_GET['account'];
		}
		
		$login_sess_access->account_id = $db->writeAccount($merchantos_sess_access->api_key);
		
		// load our oauth and qb settings from db if it exists
		$oauth_qb_arrays = $db->readOAuth($login_sess_access->account_id);
		
		if (isset($oauth_qb_arrays['oauth']) && isset($oauth_qb_arrays['qb']) && isset($oauth_qb_arrays['renew']))
		{
			self::_loadOAuth($oauth_qb_arrays,$login_sess_access);
		}
	}
	
	protected static function _loadOAuth($oauth_qb_arrays,$login_sess_access)
	{
		$db = self::_getDB();
		
		$oauth_sess_access = new SessionAccess("oauth");
		$oauth_sess_access->loadArray($oauth_qb_arrays['oauth']);
		
		$qb_sess_access = new SessionAccess("qb");
		$qb_sess_access->loadArray($oauth_qb_arrays['qb']);
		
		// load our sync settings
		$setup_sess_access = new SessionAccess("setup");
		$settings = $db->readSyncSetup($login_sess_access->account_id);
		$setup_sess_access->loadArray($settings);
		
		if ($oauth_qb_arrays['renew'] <= time())
		{
			self::_renewIntuitOAuth($db,$oauth_sess_access,$qb_sess_access,$login_sess_access);
		}
	}
	
	protected static function _renewIntuitOAuth($db,$oauth_sess_access,$qb_sess_access,$login_sess_access)
	{
		// time to reconnect/renew
		require_once("IntuitAnywhere/IntuitAnywhere.class.php");
		$ianywhere = new IntuitAnywhere($qb_sess_access);			
		if ($ianywhere->isUserAuthorized())
		{
			GLOBAL $_OAUTH_INTUIT_CONFIG;
			$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization
			$ianywhere->reconnect();
			
			// now we need to save our new key to the db
			$renew = time() + (60*60*24*30*4); // 4 months/120 days from now, to be safe (tokens last 6 months).
			$oauth_array = $oauth_sess_access->getArray();
			$qb_array = $qb_sess_access->getArray();
			$db->writeOAuth($login_sess_access->account_id,array("oauth"=>$oauth_array,"qb"=>$qb_array,"renew"=>$renew));
		}
	}
}

<?php

// initialize the session and handle any session related get params

// we are using memcache to store our sessions
require_once("lib/MemcacheSession.class.php");
require_once("lib/SessionAccess.class.php");
require_once("IntuitAnywhere/IntuitAnywhere.class.php");

class lib_Session
{
	/**
	 * @var Sync_Database
	 */
	protected $_db;
	
	/**
	 * @var Array of SessionAccess instances indexed by type
	 */
	protected $_sessionAccess;
	
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _getSessionAccess($type)
	{
		if (!isset($this->_sessionAccess[$type]))
		{
			$this->_sessionAccess[$type] = new SessionAccess($type);
		}
		return $this->_sessionAccess[$type];
	}
	
	/**
	 * Create an instance of this session initializer
	 * @param Sync_Database $db The database connection to use to init the session from if it's a new session
	 */
	public function __construct($db)
	{
		$this->_db = $db;
		$this->_sessionAccess = array();
	}
	
	/**
	 * Start the session
	 * @param boolean $needkey Do we require a key to start up a new session ($_GET['key'])
	 */
	public function init($needkey=true)
	{
		$this->_setupMemcacheSession();
		
		if (!$this->_isSessionCookieSet())
		{
			if ($this->_isKeySet())
			{
				$this->_setSessionID($this->_getKey());
				$this->_sessionStart();
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
					$this->_sessionStart();
				}
			}
		}
		else
		{
			$this->_sessionStart();
		}
		
		if ($this->_isKeySet())
		{
			$this->_initMOSKey($this->_getKey());
		}
	}
	
	protected function _initMOSKey($key)
	{
		$merchantos_sess_access = $this->_getSessionAccess('merchantos');
		$login_sess_access = $this->_getSessionAccess('login');
		
		// this is where we will eventually either create a new account or login based on a login credential of $_POST['key']
		$merchantos_sess_access->api_key = $key;
		if ($this->_isReturnURLSet())
		{
			$merchantos_sess_access->return_url = $this->_getReturnURL();
		}
		if ($this->_isAccountNumberSet())
		{
			$merchantos_sess_access->api_account = $this->_getAccountNumber();
		}
		
		$login_sess_access->account_id = $this->_db->writeAccount($merchantos_sess_access->api_key);
		
		// load our oauth and qb settings from db if it exists
		$oauth_qb_arrays = $this->_db->readOAuth($login_sess_access->account_id);
		
		if (!isset($oauth_qb_arrays['oauth']) || !isset($oauth_qb_arrays['qb']) || !isset($oauth_qb_arrays['renew']))
		{
			throw new Exception("OAuth connection to Intuit was not initialized.");
		}
		$this->_loadOAuth($oauth_qb_arrays);
	}
	
	protected function _loadOAuth($oauth_qb_arrays)
	{
		$oauth_sess_access = $this->_getSessionAccess("oauth");
		$login_sess_access = $this->_getSessionAccess("login");
		
		$oauth_sess_access->loadArray($oauth_qb_arrays['oauth']);
		
		$qb_sess_access = $this->_getSessionAccess("qb");
		$qb_sess_access->loadArray($oauth_qb_arrays['qb']);
		
		// load our sync settings
		$setup_sess_access = $this->_getSessionAccess("setup");
		$settings = $this->_db->readSyncSetup($login_sess_access->account_id);
		$setup_sess_access->loadArray($settings);
		
		if ($oauth_qb_arrays['renew'] <= time())
		{
			$this->_renewIntuitOAuth();
		}
	}
	
	protected function _renewIntuitOAuth()
	{
		$oauth_sess_access = $this->_getSessionAccess("oauth");
		$login_sess_access = $this->_getSessionAccess("login");
		$qb_sess_access = $this->_getSessionAccess("qb");
		
		// time to reconnect/renew
		$ianywhere = $this->_getIntuitAnywhere();
		if ($ianywhere->isUserAuthorized())
		{
			GLOBAL $_OAUTH_INTUIT_CONFIG;
			$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,false); // false = not interactive, fail if OAuth needs authorization
			$ianywhere->reconnect();
			
			// now we need to save our new key to the db
			$renew = time() + (60*60*24*30*4); // 4 months/120 days from now, to be safe (tokens last 6 months).
			$oauth_array = $oauth_sess_access->getArray();
			$qb_array = $qb_sess_access->getArray();
			$this->_db->writeOAuth($login_sess_access->account_id,array("oauth"=>$oauth_array,"qb"=>$qb_array,"renew"=>$renew));
		}
	}
	
	
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _setSessionID($id)
	{
		session_id($id);
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _sessionStart()
	{
		session_start();
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	public function reset()
	{
		$_SESSION = array();
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhere()
	{
		$qb_sess_access = $this->_getSessionAccess("qb");
		return new IntuitAnywhere($qb_sess_access);	
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _setupMemcacheSession()
	{
		$mem_sess = new MemcacheSession(60); // 60 minutes till session expire
		$mem_sess->register();
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _isSessionCookieSet()
	{
		return isset($_REQUEST[session_name()]);
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _isKeySet()
	{
		return isset($_GET['key']);
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _getKey()
	{
		return $_GET['key'];
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _getReturnURL()
	{
		return $_GET['return_url'];
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _getAccountNumber()
	{
		return $_GET['account'];
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _isReturnURLSet()
	{
		return isset($_GET['return_url']);
	}
	/**
	 * Override in mock object for unit testing
	 * @codeCoverageIgnore
	 */
	protected function _isAccountNumberSet()
	{
		return isset($_GET['account']);
	}
}

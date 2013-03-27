<?php
/**
 * Memcache singleton wrapper class.
 * Memcache access for MerchantOS system.
 *
 * Improved commenting and structure by Nate with help from php oop patterns.
 *
 * @package wrappers
 * @author Justin Laing
 * @author Nathan Horter <nate@merchantos.com>
 * @link http://php.net/manual/en/language.oop5.patterns.php
 * @codeCoverageIgnore
 **/
class MemcacheHelper
{
	/**
	 * The php memcache object used to interact with the memcache server
	 * @var Memcache
	 */
	private $memcache;
	
	/**
	 * Holds the instance of the class.
	 * @var MemcacheHelper
	 */
	private static $_singleton;
	
	/**
	 * Whether we are using the memcache or memcached lib
	 * @var boolean
	 */
	private $_use_memcached = false;
	
	/**
	 * An array of the servers being used
	 * @var array
	 */
	private $_servers = array();
	
	/**
	 * Private constructor prevents direct creation of object
	 */
	private function __construct()
	{
	}
	
	/**
	 * You can't clone a singleton
	 */
	public function __clone()
	{
		throw new Exception('Clone is not allowed!');
	}
	
	/**
	* Get the singleton MemcacheHelper object
	*
	* @return MemcacheHelper
	*/
	public static function getSingleton()
	{
		if (!isset(self::$_singleton))
		{
			self::$_singleton = new MemcacheHelper();
			self::$_singleton->_initMemcache();
		}
		return self::$_singleton;
	}
	
	/**
	 * Creates a new memcache connection
	 * @return boolean
	 */
	private function _initMemcache()
	{
		// Get the loaded extensions to see which memcache library we are using
		$loaded_extensions = get_loaded_extensions();
		// If we have memcached available use it.
		if (in_array('memcached', $loaded_extensions))
		{
			$this->_use_memcached = true;
		}
		// Use the correct memcache library
		if ($this->_use_memcached)
		{
			$this->memcache = new Memcached();
			// @link http://us3.php.net/manual/en/memcached.constants.php
			// Enables or disables compatibility with libketama-like behavior. When enabled, the item key hashing algorithm is set to MD5 and distribution is set to be weighted consistent hashing distribution.
			$this->memcache->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
			// Check that we don't have any servers already, servers can persist
			$server_list = $this->memcache->getServerList();
			if ($server_list === false or count($server_list) <= 0)
			{
				$server = array(
					array('memcache1', 11211),
					array('memcache2', 11211)
					);
				// make a server list compatible with the output from getServerList above.
				$server_list = array(
					array('host' => 'memcache1', 'port' => 11211),
					array('host' => 'memcache2', 'port' => 11211)
					);
				$server_status = $this->memcache->addServers($server);
			}
			// Store the server list so we can check it later if necessary
			$this->_servers = $server_list;
		}
		else
		{
			$this->memcache = new Memcache();
			$memcache1_status = $this->memcache->addServer('memcache1', 11211);
			$memcache2_status = $this->memcache->addServer('memcache2', 11211);
			$server_status = ($memcache1_status and $memcache2_status);
		}
		
		
		return $server_status;
	}
	
	/**
	 * Store data on the memcache server only if the key is not already set.
	 * @param string $key The key to store the data under.
	 * @param mixed $data Use string in most cases, can take any serializeable type.
	 * @param integer $expire_minutes The number of minutes before the cached data will expire.
	 * @return boolean
	 */
	public function add($key, $data, $expire_minutes)
	{
		if ($this->_use_memcached)
		{
			// key, data, expiration in seconds
			return $this->memcache->add($key, $data, $expire_minutes * 60);
		}
		// key, data, flags (compression), expiration in seconds
		return $this->memcache->add($key, $data, 0, $expire_minutes * 60);
	}
	
	/**
	 * Decrements the value of the passed in key by the passed in value. Returns the new value or false on failure
	 * @param string $key The key to decrement
	 * @param integer $value The amount to decrement by, if not passed $value is one.
	 * @return integer | false
	 * @link http://us3.php.net/manual/en/memcache.decrement.php
	 * @link http://us3.php.net/manual/en/memcached.decrement.php
	 */
	public function decrement($key, $value = 1)
	{
		return $this->memcache->decrement($key, $value);
	}
	
	/**
	 * Deletes the passed key from the memcache server
	 * @param string $key The key to delete from the server.
	 * @param integer $time The amount of time the server will wait to delete the key in seconds.
	 * @return boolean
	 */
	public function delete($key, $time = 0)
	{
		if ($this->_use_memcached)
		{
			return $this->memcache->delete($key, $time);
		}
		return $this->memcache->delete($key);
	}
	
	/**
	 * Tries to find the passed key on any memcache server
	 * @param string $key The key to find.
	 * @return mixed | false
	 */
	public function find($key)
	{
		// Try and find the key normally
		$value = $this->get($key);
		// If we found a value then return it
		if ($value !== false)
		{
			return $value;
		}
		// If we're not using memcached this is the end of the line.
		if (!$this->_use_memcached)
		{
			return false;
		}
		// We're going to use the key as the server name to see where it goes normally
		$key_server = $this->getServerByKey($key);
		// Check all of the other servers
		foreach ($this->_servers as $server)
		{
			// If this is the sever we would normally look for the key on, then skip it, we checked it above.
			if ($server['host'] == $key_server['host'])
			{
				continue;
			}
			// We need to generate a key to match the server we are looking for
			$server_match = false;
			while($server_match === false)
			{
				// Any old random number will do
				$server_key = mt_rand();
				// What server does this key map to?
				$key_server = $this->getServerByKey($server_key);
				// These are the droids we are looking for
				if ($server['host'] == $key_server['host'])
				{
					$server_match = true;
				}
			}
			// Check if the key exists on this server
			$value = $this->getByKey($server_key, $key);
			// Yay! The empire strikes back!
			if ($value !== false)
			{
				return $value;
			}
		}
		// These aren't the droids we're looking for, move along.
		return false;
	}
	
	/**
	 * Get a value for a given key from memcache
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->memcache->get($key);
	}
	
	/**
	 * Reads the data stored under the passed in key.
	 * @param string $server_name The name of the server to get the key from
	 * @param string $key The key to look up.
	 * @return mixed
	 */
	public function getByKey($server_name, $key)
	{
		if (!$this->_use_memcached)
		{
			return false;
		}
		return $this->memcache->getByKey($server_name, $key);
	}
	
	/**
	 * Returns the Extended statistics for all connected memcache servers
	 * @return array
	 */
	public function getExtendedStats()
	{
		return $this->getStats();
	}
	
	/**
	 * Returns a string representation of the memcache code returned.
	 * @return string
	 */
	public function getResultCode()
	{
		if (!$this->_use_memcached)
		{
			return false;
		}
		$memcache_code = $this->memcache->getResultCode();
		switch ($memcache_code)
		{
		case Memcached::RES_SUCCESS:
			$memcache_result = 'Success';
			break;
		case Memcached::RES_FAILURE:
			$memcache_result = 'Failure';
			break;
		case Memcached::RES_HOST_LOOKUP_FAILURE:
			$memcache_result = 'DNS lookup failed.';
			break;
		case Memcached::RES_UNKNOWN_READ_FAILURE:
			$memcache_result = 'Failed to read network data.';
			break;
		case Memcached::RES_PROTOCOL_ERROR:
			$memcache_result = 'Bad command in memcached protocol.';
			break;
		case Memcached::RES_CLIENT_ERROR:
			$memcache_result = 'Error on the client side.';
			break;
		case Memcached::RES_SERVER_ERROR:
			$memcache_result = 'Error on the server side.';
			break;
		case Memcached::RES_WRITE_FAILURE:
			$memcache_result = 'Failed to write network data.';
			break;
		case Memcached::RES_DATA_EXISTS:
			$memcache_result = 'Failed to do compare-and-swap: item you are trying to store has been modified since you last fetched it.';
			break;
		case Memcached::RES_NOTSTORED:
			$memcache_result = 'Item was not stored: but not because of an error. This normally means that either the condition for an "add" or a "replace" command wasn\'t met, or that the item is in a delete queue.';
			break;
		case Memcached::RES_NOTFOUND:
			$memcache_result = 'Item with this key was not found';
			break;
		case Memcached::RES_PARTIAL_READ:
			$memcache_result = 'Partial network data read error.';
			break;
		case Memcached::RES_SOME_ERRORS:
			$memcache_result = 'Some errors occurred during multi-get.';
			break;
		case Memcached::RES_NO_SERVERS:
			$memcache_result = 'Server list is empty.';
			break;
		case Memcached::RES_END:
			$memcache_result = 'End of result set.';
			break;
		case Memcached::RES_ERRNO:
			$memcache_result = 'System error.';
			break;
		case Memcached::RES_BUFFERED:
			$memcache_result = 'The operation was buffered.';
			break;
		case Memcached::RES_TIMEOUT:
			$memcache_result = 'The operation timed out.';
			break;
		case Memcached::RES_BAD_KEY_PROVIDED:
			$memcache_result = 'Bad Key.';
			break;
		case Memcached::RES_CONNECTION_SOCKET_CREATE_FAILURE:
			$memcache_result = 'Failed to create network socket.';
			break;
		case Memcached::RES_PAYLOAD_FAILURE:
			$memcache_result = 'Payload failure: could not compress/decompress or serialize/unserialize the value.';
			break;
		default:
			$memcache_result = 'Unknown Code: '.$memcache_code;
		}
		
		return $memcache_result;
	}
	
	/**
	 * Returns the server that would be selected by using the passed server_name
	 * @param string $server_name The server name that a key would be get or set from
	 * @return array
	 */
	public function getServerByKey($server_name)
	{
		if (!$this->_use_memcached)
		{
			return false;
		}
		return $this->memcache->getServerByKey($server_name);
	}
	
	/**
	 * Returns the Extended statistics for all connected memcache servers
	 * @return array
	 */
	public function getStats()
	{
		if ($this->_use_memcached)
		{
			return $this->memcache->getStats();
		}
		return $this->memcache->getExtendedStats();
	}
	
	/**
	 * Increments the value of the passed in key by the passed in value. Returns the new value or false on failure
	 * @param string $key The key to increment
	 * @param integer $value The amount to increment by, if not passed $value is one.
	 * @return integer | false
	 * @link http://us3.php.net/manual/en/memcache.increment.php
	 * @link http://us3.php.net/manual/en/memcached.increment.php
	 */
	public function increment($key, $value = 1)
	{
		return $this->memcache->increment($key, $value);
	}
	
	/**
	 * Replace data on the memcache server
	 * @param string $key The key to store the data under.
	 * @param mixed $data Use string in most cases, can take any serializeable type.
	 * @param integer $expire_minutes The number of minutes before the cached data will expire.
	 * @return boolean
	 */
	public function replace($key, $data, $expire_minutes)
	{
		if ($this->_use_memcached)
		{
			// key, data, expiration in seconds
			return $this->memcache->replace($key, $data, $expire_minutes * 60);
		}
		// key, data, flags (compression), expiration in seconds
		return $this->memcache->replace($key, $data, 0, $expire_minutes * 60);
	}
	
	/**
	 * Replace data on the memcache server, if it doesn't already exist then write it.
	 * @param string $key The key to store the data under.
	 * @param mixed $data Use string in most cases, can take any serializeable type.
	 * @param integer $expire_minutes The number of minutes before the cached data will expire.
	 * @return boolean
	 */
	public function replaceSet($key, $data, $expire_minutes)
	{
		$result = $this->replace($key, $data, $expire_minutes);
		
		if ($result === false)
		{
			$status = $this->set($key, $data, $expire_minutes);
		}
		
		return $status;
	}
	
	/**
	 * Store data on the memcache server
	 * @param string $key The key to store the data under.
	 * @param mixed $data Use string in most cases, can take any serializeable type.
	 * @param integer $expire_minutes The number of minutes before the cached data will expire.
	 * @return boolean
	 */
	public function set($key, $data, $expire_minutes)
	{
		if ($this->_use_memcached)
		{
			// key, data, expiration in seconds
			return $this->memcache->set($key, $data, $expire_minutes * 60);
		}
		// key, data, flags (compression), expiration in seconds
		return $this->memcache->set($key, $data, 0, $expire_minutes * 60);
	}
	
	/**
	 * Store data on a specific memcache server
	 * @param string $key The key to store the data under.
	 * @param mixed $data Use string in most cases, can take any serializeable type.
	 * @param integer $expire_minutes The number of minutes before the cached data will expire.
	 * @param string $server_name The name of the server to set the key on.
	 * @return boolean
	 */
	public function setByKey($key, $data, $expire_minutes, $server_name)
	{
		if (!$this->_use_memcached)
		{
			return false;
		}
		// server name, key, data, flags (compression), expiration in seconds
		return $this->memcache->setByKey($server_name, $key, $data, $expire_minutes * 60);
	}
	
	/**
	 * Deletes the passed key from the memcache server
	 * @param string $key The key to delete from the server.
	 * @return boolean
	 */
	public function deleteMemcache($key)
	{
		return $this->delete($key);
	}
	
	/**
	 * Reads the data stored under the passed in key.
	 * @param string $key The key to look up.
	 * @return mixed
	 */
	public function readMemcache($key)
	{
		return $this->get($key);
	}
	
	/**
	 * Store data on the memcache server
	 * @param string $key The key to store the data under.
	 * @param mixed $data Use string in most cases, can take any serializeable type.
	 * @param integer $expire_minutes The number of minutes before the cached data will expire.
	 * @return boolean
	 */
	public function writeMemcache($key, $data, $expire_minutes)
	{
		return $this->set($key, $data, $expire_minutes);
	}
}

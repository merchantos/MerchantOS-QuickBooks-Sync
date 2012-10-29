<?php
/**
 * Make sure the database is setup correclty
 */

class mosqb_database {
	protected static $pdo;
	
	public static function readAccount($api_key)
	{
		$table = "account INDEXED BY api_key";
		$sql = "SELECT account_id FROM $table WHERE api_key = \"$api_key\"";
		$pdoresult = self::$pdo->query($sql);
		$rows = $pdoresult->fetchAll();
		if (count($rows)!==1)
		{
			return null;
		}
		return $rows[0]['account_id'];
	}
	
	public static function writeAccount($api_key)
	{
		$sql = "INSERT OR IGNORE INTO account (api_key) VALUES (\"$api_key\")";
		self::$pdo->query($sql);
		
		return $this->readAccount($api_key);
	}
	
	public static function readOAuth($account_id)
	{
		$account_id = (integer)$account_id;
		$table = "oauth INDEXED BY account_id";
		$sql = "SELECT phpserialized FROM $table WHERE account_id = $account_id";
		$pdoresult = self::$pdo->query($sql);
		$rows = $pdoresult->fetchAll();
		if (count($rows)!==1)
		{
			return array();
		}
		return unserialize($rows[0]['phpserialized']);
	}
	
	public static function writeOAuth($account_id,$oauth_data_array)
	{
		$account_id = (integer)$account_id;
		$oauth_serialized = serialize($oauth_data_array);
		$sql = "INSERT OR REPLACE INTO account (account_id,phpserialized) VALUES ($account_id,\"$oauth_serialized\")";
		self::$pdo->query($sql);
	}
	
	public static function readSyncSetup($account_id)
	{
		$table = "sync_setup INDEXED BY account_id_name";
		$sql = "SELECT name,value FROM $table WHERE account_id = $account_id";
		$pdoresult = self::$pdo->query($sql);
		if ($pdoresult->rowCount()===0)
		{
			return array();
		}
		$setup_values = array();
		foreach ($pdoresult as $row)
		{
			$name = $row['name'];
			$value = $row['value'];
			$setup_values[$name] = $value;
		}
		return $setup_values;
	}
	
	public static function writeSyncSetup($account_id,$setup_values)
	{
		$account_id = (integer)$account_id;
		$values = array();
		foreach ($setup_values as $name=>$value)
		{
			$values[] = "($account_id,\"$name\",\"$value\")";
		}
		$sql = "INSERT OR REPLACE INTO sync_setup (account_id,name,value) VALUES " . join(",",$values);
		self::$pdo->query($sql);
	}
	
	public static function readAccountLog($account_id,$start_time,$end_time=null)
	{
		if (!$end_time)
		{
			$end_time = time();
		}
		$start_time = (integer)$start_time;
		$end_time = (integer)$end_time;
		$table = "account_log INDEXED BY account_insert_time";
		$sql = "SELECT insert_time,phpserialized FROM $table WHERE account_id = $account_id AND insert_time BETWEEN $start_time AND $end_time";
		$pdoresult = self::$pdo->query($sql);
		if ($pdoresult->rowCount()===0)
		{
			return array();
		}
		$log_entries = array();
		foreach ($pdoresult as $row)
		{
			$insert_time = $row['insert_time'];
			$phpserialized = $row['phpserialized'];
			$log_entries[$insert_time] = unserialize($phpserialized);
		}
		return $log_entries;
	}
	
	public static function writeAccountLogEntries($account_id,$log_entries)
	{
		$account_id = (integer)$account_id;
		$values = array();
		$time = time();
		foreach ($log_entries as $entry)
		{
			$entry_serialized = serialize($entry);
			$values[] = "($account_id,$time,\"$entry_serialized\")";
		}
		$sql = "INSERT OR REPLACE INTO sync_setup (account_id,insert_time,phpserialized) VALUES " . join(",",$values);
		self::$pdo->query($sql);
	}
	
	public static function init()
	{
		if (isset(self::$pdo))
		{
			return;
		}
		self::$pdo = new PDO("sqlite:".MOS_QB_SYNC_DATABASE);
	}

	public static function checkSetup()
	{
		// see if our tables are here
		$sql = "SELECT * FROM sqlite_master WHERE type='table'";
		$pdoresult = self::$pdo->query($sql);
		if (!$pdoresult)
		{
			// we are not setup
			self::_doAllSetup();
			return true;
		}
		$already_setup_tables = array();
		foreach ($pdoresult as $row)
		{
			$already_setup_tables[] = $row['name'];
		}
		if (array_search("account",$already_setup_tables)===false)
		{
			self::_doSetupAccount();
		}
		if (array_search("oauth",$already_setup_tables)===false)
		{
			self::_doSetupOAuth();
		}
		if (array_search("sync_setup",$already_setup_tables)===false)
		{
			self::_doSetupSyncSetup();
		}
		if (array_search("account_log",$already_setup_tables)===false)
		{
			self::_doSetupAccountLog();
		}
		return true;
	}
	
	protected static function _doAllSetup()
	{
		self::_doSetupAccount();
		self::_doSetupOAuth();
		self::_doSetupSyncSetup();
		self::_doSetupLog();
	}
	
	protected static function _doSetupAccount()
	{
		$sql = "CREATE TABLE account(account_id INTEGER PRIMARY KEY NOT NULL, api_key TEXT NOT NULL)";
		self::$pdo->query($sql);
		$sql = "CREATE UNIQUE INDEX api_key ON account (api_key)";
		self::$pdo->query($sql);
	}
	
	protected static function _doSetupOAuth()
	{
		$sql = "CREATE TABLE oauth(oauth_id INTEGER PRIMARY KEY NOT NULL, account_id INTEGER NOT NULL, phpserialized TEXT NOT NULL)";
		self::$pdo->query($sql);
		$sql = "CREATE UNIQUE INDEX account_id ON oauth (account_id)";
		self::$pdo->query($sql);
	}
	
	protected static function _doSetupSyncSetup()
	{
		$sql = "CREATE TABLE sync_setup(sync_setup_id INTEGER PRIMARY KEY NOT NULL, account_id INTEGER NOT NULL, name TEXT NOT NULL, value TEXT NOT NULL)";
		self::$pdo->query($sql);
		$sql = "CREATE UNIQUE INDEX account_id_name ON sync_setup (account_id, name)";
		self::$pdo->query($sql);
	}
	
	protected static function _doSetupAccountLog()
	{
		$sql = "CREATE TABLE account_log(account_log_id INTEGER PRIMARY KEY NOT NULL, account_id INTEGER NOT NULL, insert_time INTEGER NOT NULL, phpserialized TEXT NOT NULL)";
		self::$pdo->query($sql);
		$sql = "CREATE INDEX account_insert_time ON account_log (account_id ASC, insert_time DESC)";
		self::$pdo->query($sql);
	}
}

mosqb_database::init();

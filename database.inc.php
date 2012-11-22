<?php
/**
 * Database access for this sync app.
 */
class mosqb_database {
	/**
	 * @var PDO Abstracted database access interface
	 */
	protected static $pdo;
	
	/**
	 * See if this $api_key matches any accounts.
	 * @param string $api_key The api_key login credential to look for.
	 * @return null|integer The account id of the found account. Null if no account was found that matched the $api_key.
	 */
	public static function readAccount($api_key)
	{
		$api_key = SQLite3::escapeString($api_key);
		$sql = "SELECT account_id FROM account WHERE api_key = '$api_key'";
		$pdoresult = self::$pdo->query($sql);
		if (!$pdoresult)
		{
			return null;
		}
		$rows = $pdoresult->fetchAll();
		if (count($rows)!==1)
		{
			return null;
		}
		return $rows[0]['account_id'];
	}
	
	/**
	 * Create a new account based using $api_key as the login credential.
	 * @param string $api_key The api_key login credential for this new account.
	 * @return integer The account id of the newly created account.
	 */
	public static function writeAccount($api_key)
	{
		$api_key = SQLite3::escapeString($api_key);
		$sql = "INSERT OR IGNORE INTO account (api_key) VALUES ('$api_key')";
		self::$pdo->query($sql);
		
		return self::readAccount($api_key);
	}
	
	/**
	 * Write a list of settings for this account.
	 * @param integer $account_id The id of the account
	 * @return array Unserialized array of oauth data.
	 */
	public static function readOAuth($account_id)
	{
		$account_id = (integer)$account_id;
		$sql = "SELECT phpserialized FROM oauth WHERE account_id = $account_id";
		$pdoresult = self::$pdo->query($sql);
		if (!$pdoresult)
		{
			return array();
		}
		$rows = $pdoresult->fetchAll();
		if (count($rows)!==1)
		{
			return array();
		}
		return unserialize($rows[0]['phpserialized']);
	}
	
	/**
	 * Write a list of settings for this account.
	 * @param integer $account_id The id of the account
	 * @param array $oauth_data_array Serializable array of oauth data.
	 */
	public static function writeOAuth($account_id,$oauth_data_array)
	{
		$account_id = (integer)$account_id;
		$oauth_serialized = serialize($oauth_data_array);
		$stmt = self::$pdo->prepare("INSERT OR REPLACE INTO oauth (account_id,phpserialized) VALUES (:account_id, :oauth)");
		$stmt->execute(array(":account_id"=>$account_id,":oauth"=>$oauth_serialized));
	}
	
	/**
	 * Delete all OAuth info for this account.
	 * @param integer $account_id The id of the account
	 */
	public static function deleteOAuth($account_id)
	{
		$account_id = (integer)$account_id;
		$stmt = self::$pdo->prepare("DELETE FROM oauth WHERE account_id=:account_id");
		$stmt->execute(array(":account_id"=>$account_id));
	}
	
	/**
	 * Write a list of settings for this account.
	 * @param integer $account_id The id of the account
	 * @return array An array of setup values in form:
	 * array(
	 *  "<setting name>" => <setting value>|mixed (any serializable value)
	 * )
	 */
	public static function readSyncSetup($account_id)
	{
		$sql = "SELECT name,value FROM sync_setup WHERE account_id = $account_id";
		$pdoresult = self::$pdo->query($sql);
		if (!$pdoresult)
		{
			return array();
		}
		$rows = $pdoresult->fetchAll();
		if (count($rows)===0)
		{
			return array();
		}
		$setup_values = array();
		foreach ($rows as $row)
		{
			$name = $row['name'];
			$value = $row['value'];
			if (stripos($name,"s:")===0)
			{
				$name = substr($name,2);
				$value = unserialize($value);
			}
			$setup_values[$name] = $value;
		}
		return $setup_values;
	}
	
	/**
	 * Write a list of settings for this account.
	 * @param integer $account_id The id of the account
	 * @param array $setup_values An array of setup values in form:
	 * array(
	 *  "<setting name>" => "<setting value>"
	 * )
	 */
	public static function writeSyncSetup($account_id,$setup_values)
	{
		$account_id = (integer)$account_id;
		self::$pdo->exec("BEGIN TRANSACTION");
		$stmt = self::$pdo->prepare("INSERT OR REPLACE INTO sync_setup (account_id,name,value) VALUES (:account_id, :name, :value)");
		foreach ($setup_values as $name=>$value)
		{
			if (is_array($value) || is_object($value))
			{
				$name = "s:$name";
				$value = serialize($value);
			}
			$stmt->execute(array(":account_id"=>$account_id,":name"=>$name,":value"=>$value));
		}
		self::$pdo->exec("END TRANSACTION");
	}
	
	/**
	 * Delete all settings for this account.
	 * @param integer $account_id The id of the account
	 */
	public static function deleteSyncSetup($account_id)
	{
		$account_id = (integer)$account_id;
		$stmt = self::$pdo->prepare("DELETE FROM sync_setup WHERE account_id=:account_id");
		$stmt->execute(array(":account_id"=>$account_id));
	}
	
	/**
	 * Get the most recent day of successfully synced data (data_date) from the log.
	 * @param integer $account_id The id of the account to check log messages for
	 * @return DateTime The date of the last successful sync.
	 */
	public static function getLastSuccessfulDataDate($account_id)
	{
		$account_id = (integer)$account_id;
		$sql = "SELECT data_date FROM account_log WHERE account_id = $account_id AND success=1 ORDER BY data_date DESC LIMIT 1";
		$pdoresult = self::$pdo->query($sql);
		if (!$pdoresult)
		{
			return null;
		}
		$rows = $pdoresult->fetchAll();
		if (count($rows)===0)
		{
			return null;
		}
		$date = date('c',$rows[0]['data_date']);
		$date = new DateTime($date);
		return $date;
	}
	
	/**
	 * Check if the log indicates there was a successful sync of data for the date range specified specified. Everything between $start and $end inclusive will be checked to see if there is a successful sync.
	 * @param integer $account_id The id of the account to check log messages for
	 * @param DateTime $start Start of the date range to check.
	 * @param DateTime $end End of the date range to check.
	 * @return boolean Did we have a successful sync durring date range?
	 */
	public static function hasSyncSuccessDurring($account_id,$start,$end)
	{
		$start = (integer)$start->format("U");
		$end = (integer)$end->format("U");
		$account_id = (integer)$account_id;
		$sql = "SELECT count(*) as num FROM account_log WHERE account_id = $account_id AND success=1 AND data_date >= $start AND data_date <= $end";
		$pdoresult = self::$pdo->query($sql);
		if (!$pdoresult)
		{
			return false;
		}
		$rows = $pdoresult->fetchAll();
		if (count($rows)===0)
		{
			return false;
		}
		if ($rows[0]['num']>0)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Read log messages (or alerts) from the database for an account.
	 * @param integer $account_id The id of the account to read log messages for
	 * @param integer $offset (default=0) Offset for the query starting from 0. Allows for pagination with limit.
	 * @param integer $limit (default=20) Limit number of records to return.
	 * @param boolean $alerts_only (default=false) Should only alerts be listed, or all log entries (only alert queries will return log entries with no data_date)
	 * @return array An array of log entries of form:
	 * array(
	 *  'account_log_id'=>internal id of this log entry.
	 *  'msg'=>The message for this log entry.
	 *  'data_date'=>Date the data synced for this log entry pertains to. Not included for non day related alerts.
	 *  'success'=>0/1 was the sync successful for this day.
	 *  'insert_time'=>timestamp for when this log entry was created.
	 *  'alert'=>0/1 is this an alert.
	 * )
	 */
	public static function readAccountLog($account_id,$offset=0,$limit=20,$alerts_only=false)
	{
		$offset = (integer)$offset;
		$limit = (integer)$limit;
		$account_id = (integer)$account_id;
		$extra_where = "";
		if ($alerts_only)
		{
			$extra_where = "AND alert=1";
		}
		else
		{
			// don't show alerts without data_date in normal history, they are just errors to display
			$extra_where = "AND data_date!=0";
		}
		$sql = "SELECT account_log_id,insert_time,data_date,success,msg,alert FROM account_log WHERE account_id = $account_id $extra_where ORDER BY insert_time DESC LIMIT $offset,$limit";
		$pdoresult = self::$pdo->query($sql);
		if (!$pdoresult)
		{
			return array();
		}
		$rows = $pdoresult->fetchAll();
		if (count($rows)===0)
		{
			return array();
		}
		$log_entries = array();
		foreach ($rows as $row)
		{
			$log_entry['account_log_id'] = $row['account_log_id'];
			$log_entry['msg'] = $row['msg'];
			if ($row['data_date']!=0)
			{
				$date = date('c',$row['data_date']);
				$date = new DateTime($date);
				$log_entry['data_date'] = $date->format("m/d/Y");
			}
			$log_entry['success'] = $row['success'];
			$log_entry['insert_time'] = $row['insert_time'];
			$log_entry['alert'] = $row['alert'];
			$log_entries[] = $log_entry;
		}
		return $log_entries;
	}
	
	/**
	 * Write a list of log entries to the database.
	 * @param integer $account_id The id of the account to write these log entries for
	 * @param array $log_entries Array of log entries with:
	 * array(
	 * 	"msg"=>"The message to write",
	 * 	"success"=>boolean: did the sync of the data_date succeed,
	 * 	"alert"=>boolean: should this be displayed as an alert,
	 * 	"data_date"=>m/d/Y for the day of data that was synced. Do not include data_date (or set = null) if this is an alert that does not pertain to a particular day of data.
	 * )
	 */
	public static function writeAccountLogEntries($account_id,$log_entries)
	{
		$account_id = (integer)$account_id;
		$time = time();
		self::$pdo->exec("BEGIN TRANSACTION");
		$stmt = self::$pdo->prepare("INSERT OR REPLACE INTO account_log (account_id,insert_time,data_date,success,msg,alert) VALUES (:account_id, :insert_time, :data_date, :success, :msg, :alert)");
		foreach ($log_entries as $entry)
		{
			$msg = $entry['msg'];
			$success = (integer)$entry['success'];
			$alert = (integer)$entry['alert'];
			if (isset($entry['date']))
			{
				$data_date = DateTime::createFromFormat("m/d/Y",$entry['date']);
				$data_date = (integer)$data_date->format("U");
			}
			else
			{
				$data_date = 0;
			}
			$stmt->execute(array(":account_id"=>$account_id,":insert_time"=>$time,":msg"=>$msg,":data_date"=>$data_date,":success"=>$success,":alert"=>$alert));
		}
		self::$pdo->exec("END TRANSACTION");
	}
	
	/**
	 * Mark a log message as not an alert. Removes a log entry from the alerts list.
	 * @param integer $account_id The id of the account the log entry belongs to
	 * @param integer $account_log_id The id of the alert log entry to dismiss
	 */
	public static function dismissAlert($account_id,$account_log_id)
	{
		$account_log_id = (integer)$account_log_id;
		$account_id = (integer)$account_id;
		$sql = "UPDATE account_log SET alert=0 WHERE account_log_id = $account_log_id AND account_id = $account_id";
		self::$pdo->query($sql);
	}
	
	/**
	 * Delete a log entry.
	 * @param integer $account_id The id of the account the log entry belongs to
	 * @param integer $account_log_id The id of the log entry to delete
	 */
	public static function deleteAccountLogEntry($account_id,$account_log_id)
	{
		$account_log_id = (integer)$account_log_id;
		$account_id = (integer)$account_id;
		$sql = "DELETE FROM account_log WHERE account_log_id = $account_log_id AND account_id = $account_id";
		self::$pdo->query($sql);
	}
	
	/**
	 * Creates the static singleton for this class that the static methods will use. Called at the bottom of this file so don't need to worry about this.
	 */
	public static function init()
	{
		if (isset(self::$pdo))
		{
			return;
		}
		self::$pdo = new PDO("sqlite:".MOS_QB_SYNC_DATABASE);
	}

	/**
	 * Create the database tables if they do not exist. Just used to intially create the database for this sync app.
	 * @return boolean Success
	 */
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
		$sql = "CREATE TABLE account_log(account_log_id INTEGER PRIMARY KEY NOT NULL, account_id INTEGER NOT NULL, insert_time INTEGER NOT NULL, data_date INTEGER NOT NULL, success INTEGER NOT NULL, alert INTEGER NOT NULL, msg TEXT NOT NULL)";
		self::$pdo->query($sql);
		$sql = "CREATE INDEX account_insert_time ON account_log (account_id ASC, data_date DESC)";
		self::$pdo->query($sql);
	}
}

mosqb_database::init();

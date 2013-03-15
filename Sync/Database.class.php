<?php
/**
 * Database access for this sync app.
 */
class Sync_Database {
	/**
	 * @var PDO Abstracted database access interface
	 */
	protected $_pdo;
	
	/**
	 * Construct a database wrapper object for use
	 */
	public function __construct()
	{
		if (isset($this->_pdo))
		{
			return;
		}
		//$this->_pdo = new PDO("sqlite:".MOS_QB_SYNC_DATABASE);
		$dsn = "mysql:host=" . MOS_QB_SYNC_DATABASE_HOST . ";dbname=" . MOS_QB_SYNC_DATABASE_NAME;
		$username = MOS_QB_SYNC_DATABASE_USERNAME;
		$password = MOS_QB_SYNC_DATABASE_PASSWORD;
		$options = array();
		$this->_pdo = new PDO($dsn, $username, $password, $options);
		
		$this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * See if this $api_key matches any accounts.
	 * @param string $api_key The api_key login credential to look for.
	 * @return null|integer The account id of the found account. Null if no account was found that matched the $api_key.
	 */
	public function readAccount($api_key)
	{
		$stmt = $this->_pdo->prepare("SELECT account_id FROM account WHERE api_key = :api_key");
		if (!$stmt->execute(array("api_key"=>$api_key)))
		{
			return null;
		}
		$rows = $stmt->fetchAll();
		if (count($rows)!==1)
		{
			return null;
		}
		return $rows[0]['account_id'];
	}
	
	/**
	 * Get a merchantos API key for the given account id
	 * @param integer $id The account to load the apikey from
	 * @return null|string The account api key. Null if no account was found.
	 */
	public function getAPIKeyFromAccountID($id)
	{
		$stmt = $this->_pdo->prepare("SELECT api_key FROM account WHERE account_id = :account_id");
		if (!$stmt->execute(array("account_id"=>$id)))
		{
			return null;
		}
		$rows = $stmt->fetchAll();
		if (count($rows)!==1)
		{
			return null;
		}
		return $rows[0]['api_key'];
	}
	
	/**
	 * Create a new account based using $api_key as the login credential.
	 * @param string $api_key The api_key login credential for this new account.
	 * @return integer The account id of the newly created account.
	 */
	public function writeAccount($api_key)
	{
		$stmt = $this->_pdo->prepare("INSERT INTO account (api_key) VALUES (:api_key)");
		try
		{
			if (!$stmt->execute(array("api_key"=>$api_key)))
			{
				throw new Exception("Could not load or create account.");
			}
			return $this->_pdo->lastInsertId();
		}
		catch (Exception $e)
		{
			// check for duplicate entry
			$msg = $e->getMessage();
			if (stripos($msg,"1062 Duplicate entry")===false)
			{
				throw $e; // not duplicate, re throw
			}
		}
		// is duplicate so just read
		return $this->readAccount($api_key);
	}
	
	/**
	 * Write a list of settings for this account.
	 * @param integer $account_id The id of the account
	 * @return array Unserialized array of oauth data.
	 */
	public function readOAuth($account_id)
	{
		$account_id = (integer)$account_id;
		$sql = "SELECT phpserialized FROM oauth WHERE account_id = $account_id";
		$pdoresult = $this->_pdo->query($sql);
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
	public function writeOAuth($account_id,$oauth_data_array)
	{
		$account_id = (integer)$account_id;
		$oauth_serialized = serialize($oauth_data_array);
		$stmt = $this->_pdo->prepare("REPLACE INTO oauth (account_id,phpserialized) VALUES (:account_id, :oauth)");
		$stmt->execute(array(":account_id"=>$account_id,":oauth"=>$oauth_serialized));
	}
	
	/**
	 * Delete all OAuth info for this account.
	 * @param integer $account_id The id of the account
	 */
	public function deleteOAuth($account_id)
	{
		$account_id = (integer)$account_id;
		$stmt = $this->_pdo->prepare("DELETE FROM oauth WHERE account_id=:account_id");
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
	public function readSyncSetup($account_id)
	{
		$sql = "SELECT name,value FROM sync_setup WHERE account_id = $account_id";
		$pdoresult = $this->_pdo->query($sql);
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
	public function writeSyncSetup($account_id,$setup_values)
	{
		$account_id = (integer)$account_id;
		$stmt = $this->_pdo->prepare("REPLACE INTO sync_setup (account_id,name,value) VALUES (:account_id, :name, :value)");
		foreach ($setup_values as $name=>$value)
		{
			if (is_array($value) || is_object($value))
			{
				$name = "s:$name";
				$value = serialize($value);
			}
			$stmt->execute(array(":account_id"=>$account_id,":name"=>$name,":value"=>$value));
		}
	}
	
	/**
	 * Delete all settings for this account.
	 * @param integer $account_id The id of the account
	 */
	public function deleteSyncSetup($account_id)
	{
		$account_id = (integer)$account_id;
		$stmt = $this->_pdo->prepare("DELETE FROM sync_setup WHERE account_id=:account_id");
		$stmt->execute(array(":account_id"=>$account_id));
	}
	
	/**
	 * Get the most recent day of successfully synced data (data_date) from the log.
	 * @param string $type Filter the type of log message to one of: 'all', 'sales', 'cogs', 'orders', 'msg'
	 * @param integer $account_id The id of the account to check log messages for
	 * @return DateTime The date of the last successful sync.
	 */
	public function getLastSuccessfulDataDate($type,$account_id)
	{
		$account_id = (integer)$account_id;
		$extra_where = "";
		if ($type === 'sales' || $type === 'cogs' || $type === 'orders' || $type === 'msg')
		{
			$extra_where .= "AND type LIKE '$type'";
		}
		$sql = "SELECT data_date FROM account_log WHERE account_id = $account_id AND success=1 $extra_where ORDER BY data_date DESC LIMIT 1";
		$pdoresult = $this->_pdo->query($sql);
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
	 * @param string $type Filter the type of log message to one of: 'all', 'sales', 'cogs', 'orders', 'msg'
	 * @param integer $account_id The id of the account to check log messages for
	 * @param DateTime $start Start of the date range to check.
	 * @param DateTime $end End of the date range to check.
	 * @return boolean Did we have a successful sync durring date range?
	 */
	public function hasSyncSuccessDurring($type,$account_id,$start,$end)
	{
		$start = (integer)$start->format("U");
		$end = (integer)$end->format("U");
		$account_id = (integer)$account_id;
		$extra_where = "";
		if ($type === 'sales' || $type === 'cogs' || $type === 'orders' || $type === 'msg')
		{
			$extra_where .= "AND type LIKE '$type'";
		}
		$sql = "SELECT count(*) as num FROM account_log WHERE account_id = $account_id AND success=1 AND data_date >= $start AND data_date <= $end $extra_where";
		$pdoresult = $this->_pdo->query($sql);
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
	 * @param string $type Filter the type of log message to one of: 'all', 'sales', 'cogs', 'orders', 'msg'
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
	public function readAccountLog($type,$account_id,$offset=0,$limit=20,$alerts_only=false)
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
		if ($type === 'sales' || $type === 'cogs' || $type === 'orders' || $type === 'msg')
		{
			$extra_where .= " AND type LIKE '$type'";
		}
		$sql = "SELECT account_log_id,UNIX_TIMESTAMP(time_stamp) as insert_time,data_date,success,msg,alert,type FROM account_log WHERE account_id = $account_id $extra_where ORDER BY time_stamp DESC LIMIT $offset,$limit";
		$pdoresult = $this->_pdo->query($sql);
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
			else
			{
				$log_entry['data_date'] = false;
			}
			$log_entry['success'] = $row['success'];
			$log_entry['insert_time'] = $row['insert_time'];
			$log_entry['alert'] = $row['alert'];
			$log_entry['type'] = $row['type'];
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
	 * 	"type"=>one of: 'sales', 'cogs', 'orders', 'msg'
	 * )
	 */
	public function writeAccountLogEntries($account_id,$log_entries)
	{
		$account_id = (integer)$account_id;
		$stmt = $this->_pdo->prepare("INSERT INTO account_log (account_id,data_date,success,msg,alert,type) VALUES (:account_id, :data_date, :success, :msg, :alert, :type)");
		foreach ($log_entries as $entry)
		{
			$msg = $entry['msg'];
			$success = (integer)$entry['success'];
			$alert = (integer)$entry['alert'];
			$type = "msg";
			if ($entry['type'] === 'sales' || $entry['type'] === 'cogs' || $entry['type'] === 'orders')
			{
				$type = $entry['type'];
			}
			if (isset($entry['date']))
			{
				$data_date = DateTime::createFromFormat("m/d/Y",$entry['date']);
				$data_date = (integer)$data_date->format("U");
			}
			else
			{
				$data_date = 0;
			}
			$stmt->execute(array(":account_id"=>$account_id,":msg"=>$msg,":data_date"=>$data_date,":success"=>$success,":alert"=>$alert,":type"=>$type));
		}
	}
	
	/**
	 * Write a list of QuickBooks objects that have been created to the database.
	 * @param integer $account_id The id of the account to write these log entries for
	 * @param array $objects Array of objects with:
	 * array(
	 * 	"id"=>(Integer)The id of the object in QB.
	 * 	"type"=>"The object type in QB."
	 * )
	 */
	public function writeQBObjects($account_id,$qb_objects)
	{
		$account_id = (integer)$account_id;
		$stmt = $this->_pdo->prepare("REPLACE INTO qb_object (account_id,object_id,type) VALUES (:account_id, :object_id, :type)");
		foreach ($qb_objects as $qb_obj)
		{
			$type = $qb_obj['type'];
			$object_id = (integer)$qb_obj['id'];
			$stmt->execute(array(":account_id"=>$account_id,":object_id"=>$object_id,":type"=>$type));
		}
	}
	
	/**
	 * Delete's an entry for a QuickBooks Object.
	 * @param integer $account_id The id of the account the object belongs to
	 * @param integer $id The id of the object
	 * @param integer $type The type of the object
	 * @return boolean True if the object is deleted, false otherwise.
	 */
	public function deleteQBObject($account_id,$type,$id)
	{
		$id = (integer)$id;
		$account_id = (integer)$account_id;
		
		$stmt = $this->_pdo->prepare("DELETE FROM qb_object WHERE account_id = :account_id AND object_id = :id AND type = :type");
		if (!$stmt->execute(array("account_id"=>$account_id,"id"=>$id,"type"=>$type)))
		{
			return false;
		}
		if ($stmt->rowCount()!=1)
		{
			return false;
		}
		return true;
	}
	
	/**
	 * Read list of objects that have been created in QB.
	 * @param string $type Filter the type of objects 'all' will get any object.
	 * @param integer $account_id The id of the account to read log messages for
	 * @param integer $offset (default=0) Offset for the query starting from 0. Allows for pagination with limit.
	 * @param integer $limit (default=20) Limit number of records to return.
	 * @return array An array of object entries of form:
	 * array(
	 *  'type'=>internal id of this log entry.
	 *  'object_id'=>The message for this log entry.
	 *  'insert_time'=>timestamp for when this log entry was created.
	 * )
	 */
	public function readQBObjects($type,$account_id,$offset=0,$limit=20)
	{
		$offset = (integer)$offset;
		$limit = (integer)$limit;
		$account_id = (integer)$account_id;
		$extra_where = "";
		$binds = array();
		if ($type !== 'all')
		{
			$extra_where .= " AND type LIKE :type";
			$binds[':type'] = $type;
		}
		$stmt = $this->_pdo->prepare("SELECT object_id, UNIX_TIMESTAMP(time_stamp) as insert_time, type FROM qb_object WHERE account_id = $account_id $extra_where ORDER BY time_stamp DESC LIMIT $offset,$limit");
		if (!$stmt->execute($binds))
		{
			return array();
		}
		$rows = $stmt->fetchAll();
		if (count($rows)===0)
		{
			return array();
		}
		$objects = array();
		foreach ($rows as $row)
		{
			$objects[] = array(
				'id' => $row['object_id'],
				'insert_time' => $row['insert_time'],
				'type' => $row['type']
			);
		}
		return $objects;
	}
	
	/**
	 * Mark a log message as not an alert. Removes a log entry from the alerts list.
	 * @param integer $account_id The id of the account the log entry belongs to
	 * @param integer $account_log_id The id of the alert log entry to dismiss
	 */
	public function dismissAlert($account_id,$account_log_id)
	{
		$account_log_id = (integer)$account_log_id;
		$account_id = (integer)$account_id;
		$sql = "UPDATE account_log SET alert=0 WHERE account_log_id = $account_log_id AND account_id = $account_id";
		$this->_pdo->query($sql);
	}
	
	/**
	 * Delete a log entry.
	 * @param integer $account_id The id of the account the log entry belongs to
	 * @param integer $account_log_id The id of the log entry to delete
	 */
	public function deleteAccountLogEntry($account_id,$account_log_id)
	{
		$account_log_id = (integer)$account_log_id;
		$account_id = (integer)$account_id;
		$sql = "DELETE FROM account_log WHERE account_log_id = $account_log_id AND account_id = $account_id";
		$this->_pdo->query($sql);
	}
	
	/**
	 * Create the database tables if they do not exist. Just used to intially create the database for this sync app.
	 * @return boolean Success
	 */
	public function checkSetup()
	{
		// see if our tables are here
		$sql = "SHOW TABLES";
		$pdoresult = $this->_pdo->query($sql);
		if (!$pdoresult)
		{
			// we are not setup
			$this->_doAllSetup();
			return true;
		}
		$already_setup_tables = array();
		foreach ($pdoresult as $row)
		{
			$already_setup_tables[] = $row[0];
		}
		if (array_search("account",$already_setup_tables)===false)
		{
			$this->_doSetupAccount();
		}
		if (array_search("oauth",$already_setup_tables)===false)
		{
			$this->_doSetupOAuth();
		}
		if (array_search("sync_setup",$already_setup_tables)===false)
		{
			$this->_doSetupSyncSetup();
		}
		if (array_search("account_log",$already_setup_tables)===false)
		{
			$this->_doSetupAccountLog();
		}
		if (array_search("qb_object",$already_setup_tables)===false)
		{
			$this->_doSetupQBObject();
		}
		return true;
	}
	
	protected function _doAllSetup()
	{
		$this->_doSetupAccount();
		$this->_doSetupOAuth();
		$this->_doSetupSyncSetup();
		$this->_doSetupLog();
		$this->_doSetupQBObject();
	}
	
	protected function _doSetupAccount()
	{
		$fields = array(
			//fields
			"account_id"=>"primary", // automatically a key
			"api_key"=>"varchar",
			// keys
			"uniques"=>array("api_key"=>"api_key"), // each api_key is unique
		);
		$sql = $this->_getTableCreateSQL("account",$fields);
		$this->_pdo->query($sql);
	}
	
	protected function _doSetupOAuth()
	{
		$fields = array(
			//fields
			"oauth_id"=>"primary", // automatically a key
			"account_id"=>"integer", // don't need key here because we are doing unique below
			"phpserialized"=>"text",
			"uniques"=>array("account_id"=>"account_id"), // only one oauth per account
		);
		$sql = $this->_getTableCreateSQL("oauth",$fields);
		$this->_pdo->query($sql);
	}
	
	protected function _doSetupSyncSetup()
	{
		$fields = array(
			//fields
			"sync_setup_id"=>"primary", // automatically a key
			"account_id"=>"integer", // we don't need a key here because we have unique below
			"name"=>"varchar",
			"value"=>"text",
			// keys
			"uniques"=>array("account_id_name"=>array("account_id","name")), // only one setup variable per "name" per account
		);
		$sql = $this->_getTableCreateSQL("sync_setup",$fields);
		$this->_pdo->query($sql);
	}
	
	protected function _doSetupAccountLog()
	{
		$fields = array(
			//fields
			"account_log_id"=>"primary", // automatically a key
			"account_id"=>"foreign", // automatically a key
			"time_stamp"=>"timestamp",
			"data_date"=>"integer",
			"success"=>"boolean",
			"alert"=>"boolean",
			"type"=>"varchar",
			"msg"=>"text",
			// keys
			"keys"=>array("time_stamp"=>"time_stamp","data_date"=>"data_date")
		);
		$sql = $this->_getTableCreateSQL("account_log",$fields);
		$this->_pdo->query($sql);
	}
	
	protected function _doSetupQBObject()
	{
		$fields = array(
			//fields
			"qb_object_id"=>"primary", // automatically a key
			"account_id"=>"integer", // don't need key here because we have unique below
			"object_id"=>"integer", // don't need key here because we have unique below
			"time_stamp"=>"timestamp",
			"type"=>"varchar",
			// keys
			"uniques"=>array("account_type_id"=>array("account_id","type","object_id")), // only one object of a certain id and type per account
			"keys"=>array("time_stamp"=>"time_stamp")
		);
		$sql = $this->_getTableCreateSQL("qb_object",$fields);
		$this->_pdo->query($sql);
	}
	
	protected function _getTableCreateSQL($tablename,$fields)
	{
		$field_sqls = array();
		$key_sqls = array();
		foreach ($fields as $name=>$type)
		{
			if ($name == "keys")
			{
				foreach ($type as $keyname=>$columns)
				{
					if (is_array($columns))
					{
						$columns = join("`,`",$columns);
					}
					$key_sqls[] = "KEY `$keyname` (`$columns`)";
					break;
				}
				continue;
			}
			if ($name == "uniques")
			{
				foreach ($type as $keyname=>$columns)
				{
					if (is_array($columns))
					{
						$columns = join("`,`",$columns);
					}
					$key_sqls[] = "UNIQUE `$keyname` (`$columns`)";
					break;
				}
			}
			
			switch ($type)
			{
				case "primary":
					$field_sqls[] = "`$name` int(10) unsigned NOT NULL AUTO_INCREMENT";
					$key_sqls[] = "PRIMARY KEY (`$name`)";
					break;
				
				case "foreign":
					$key_sqls[] = "KEY `$name` (`$name`)";
				case "integer":
					$field_sqls[] = "`$name` int(10) unsigned NOT NULL";
					break;
				
				case "boolean":
					$field_sqls[] = "`$name` tinyint(1) unsigned NOT NULL";
					break;
				
				case "varchar":
					$field_sqls[] = "`$name` varchar(255) NOT NULL";
					break;
				
				case "text":
					$field_sqls[] = "`$name` text NOT NULL";
					break;
				
				case "timestamp":
					$field_sqls[] = "`$name` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
					break;
			}
		}
		
		$lines = array_merge($field_sqls,$key_sqls);
		
		return "CREATE TABLE `$tablename` (" . join(", ",$lines) . ") ENGINE=MyISAM";
	}
}

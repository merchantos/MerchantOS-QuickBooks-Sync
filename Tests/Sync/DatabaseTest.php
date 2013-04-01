<?php
require_once("config.inc.php");
require_once("Sync/Database.class.php");

/**
 * class mock_Sync_DatabaseForPDOFaking
 */
class mock_Sync_DatabaseForPDOFaking extends Sync_Database
{
	protected $_old_pdo;
	public function setPDO($pdo)
	{
		if (!isset($this->_old_pdo))
		{
			$this->_old_pdo = $this->_pdo;
		}
		$this->_pdo = $pdo;
	}
	public function restorePDO()
	{
		if (isset($this->_old_pdo))
		{
			$this->_pdo = $this->_old_pdo;
		}
	}
	public function getPDO()
	{
		return $this->_pdo;	
	}
}

/**
 * class mock_PDOStub
 */
class mock_PDOStub
{
	public function prepare($one)
	{
		return null;
	}
	public function lastInsertId()
	{
		return null;
	}
	public function query($one)
	{
		return null;
	}
}



/**
 * class Sync_DatabaseTest
 */
class Sync_DatabaseTest extends PHPUnit_Framework_TestCase
{
	protected static $_account_id;
	protected static $_db;
	
	public static function setUpBeforeClass()
	{
		self::$_db = new mock_Sync_DatabaseForPDOFaking();
		self::$_account_id = self::$_db->writeAccount("foo");
	}
	
	public static function tearDownAfterClass()
	{
		self::$_db->deleteAccount(self::$_account_id);
	}
	
	public function tearDown()
	{
		self::$_db->restorePDO();
	}
	
	public function testConstructSetupPDO()
	{
		self::$_db = new mock_Sync_DatabaseForPDOFaking();
		
		$pdo = self::$_db->getPDO();
		
		$result = $pdo->query("SELECT * FROM account WHERE 0");
		
		$this->assertTrue(is_object($result));
	}
	
	public function testWriteAccountWriteFailed()
	{
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->once())
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->once())
			->method("execute")
			->will($this->returnValue(false));
		
		self::$_db->setPDO($mock_pdo);
		
		$this->setExpectedException("Exception","Could not load or create account.");
		self::$_db->writeAccount("foo");
	}
	
	public function testWriteAccountWriteException()
	{
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->once())
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->once())
			->method("execute")
			->will($this->throwException(new Exception));
		
		self::$_db->setPDO($mock_pdo);
		
		$this->setExpectedException("Exception");
		self::$_db->writeAccount("foo");
	}
	
	public function testWriteAccountSuccess()
	{
		self::$_account_id = self::$_db->writeAccount("foo");
		
		$this->assertGreaterThan(0,self::$_account_id);
	}
	
	public function testWriteAccountDuplicate()
	{
		$duplicate_account_id = self::$_db->writeAccount("foo");
		
		$this->assertEquals(self::$_account_id,$duplicate_account_id);
	}
	
	public function testReadAccount()
	{
		$duplicate_account_id = self::$_db->readAccount("foo");
		
		$this->assertEquals(self::$_account_id,$duplicate_account_id);
		
		// test failures
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->exactly(2))
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->exactly(2))
			->method("execute")
			->will($this->onConsecutiveCalls(false,true));
		
		$mock_pdo_statement->expects($this->once())
			->method("fetchAll")
			->will($this->returnValue(array()));
		
		self::$_db->setPDO($mock_pdo);
		
		// execute fail
		$this->assertNull(self::$_db->readAccount("foo"));
		
		// fetch results = none
		$this->assertNull(self::$_db->readAccount("foo"));
	}
	
	public function getAPIKeyFromAccountID()
	{
		$key = self::$_db->getAPIKeyFromAccountID(self::$_account_id);
		
		$this->assertEquals("foo",$key);
		
		// test failures
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->exactly(2))
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->exactly(2))
			->method("execute")
			->will($this->onConsecutiveCalls(false,true));
		
		$mock_pdo_statement->expects($this->once())
			->method("fetchAll")
			->will($this->returnValue(array()));
		
		self::$_db->setPDO($mock_pdo);
		
		// execute fail
		$this->assertNull(self::$_db->getAPIKeyFromAccountID(self::$_account_id));
		
		// fetch results = none
		$this->assertNull(self::$_db->getAPIKeyFromAccountID(self::$_account_id));
	}
	
	public function testWriteReadDeleteOAuth()
	{
		$test_oauth_array = array("foo"=>"bar","bat"=>"baz");
		
		// test write
		self::$_db->writeOAuth(self::$_account_id,$test_oauth_array);
		
		// test read
		$read_oauth_array = self::$_db->readOAuth(self::$_account_id);
		
		$this->assertEquals($test_oauth_array,$read_oauth_array);
		
		// test delete
		self::$_db->deleteOAuth(self::$_account_id);
		
		$this->assertNull(self::$_db->readOAuth(self::$_account_id));
	}
	
	public function testReadOAuthFailures()
	{
		// test failures
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->exactly(2))
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->exactly(2))
			->method("execute")
			->will($this->onConsecutiveCalls(false,true));
		
		$mock_pdo_statement->expects($this->once())
			->method("fetchAll")
			->will($this->returnValue(array()));
		
		self::$_db->setPDO($mock_pdo);
		
		// execute fail
		$this->assertNull(self::$_db->readOAuth(self::$_account_id));
		
		// fetch results = none
		$this->assertNull(self::$_db->readOAuth(self::$_account_id));
	}
	
	public function testWriteReadDeleteSyncSetup()
	{
		$test_obj = new stdClass();
		$test_obj->batbaz = "qux";
		$test_sync_setup = array("foo"=>"bar","bat"=>"baz","qux"=>array("quux"),"foobar"=>$test_obj);
		
		// test write
		self::$_db->writeSyncSetup(self::$_account_id,$test_sync_setup);
		
		// test read
		$read_sync_setup = self::$_db->readSyncSetup(self::$_account_id);
		
		$this->assertEquals($test_sync_setup,$read_sync_setup);
		
		// test delete
		self::$_db->deleteSyncSetup(self::$_account_id);
		
		$this->assertEquals(array(),self::$_db->readSyncSetup(self::$_account_id));
	
	}
	
	public function testReadSyncSetupFailures()
	{
		// test failures
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->exactly(2))
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->exactly(2))
			->method("execute")
			->will($this->onConsecutiveCalls(false,true));
		
		$mock_pdo_statement->expects($this->once())
			->method("fetchAll")
			->will($this->returnValue(array()));
		
		self::$_db->setPDO($mock_pdo);
		
		// execute fail
		$this->assertEquals(array(),self::$_db->readSyncSetup(self::$_account_id));
		
		// fetch results = none
		$this->assertEquals(array(),self::$_db->readSyncSetup(self::$_account_id));
	}
	
	public function testWriteReadAccountLogEntries()
	{
		$test_log_entries = array(
			array(
				'type'=>'msg', //if ($entry['type'] === 'sales' || $entry['type'] === 'cogs' || $entry['type'] === 'orders')
				'msg'=>'hello world',
				'success'=>0,
				'alert'=>1,
				'date'=>'03/03/1979',
			),
			array(
				'type'=>'sales',
				'msg'=>'hello world sales',
				'success'=>1,
				'alert'=>0,
				'date'=>'03/04/1979',
			),
			array(
				'type'=>'cogs',
				'msg'=>'hello world cogs',
				'success'=>0,
				'alert'=>1,
				'date'=>'03/05/1979',
			),
			array(
				'type'=>'cogs',
				'msg'=>'hello world cogs 2',
				'success'=>1,
				'alert'=>1,
				'date'=>'03/06/1979',
			),
			array(
				'type'=>'orders',
				'msg'=>'hello world orders',
				'success'=>0,
				'alert'=>0,
				'date'=>'03/07/1979',
			),
			array(
				'msg'=>'hello world no date',
				'success'=>0,
				'alert'=>1
			),
		);
		
		// test write
		self::$_db->writeAccountLogEntries(self::$_account_id,$test_log_entries);
		
		// test read alerts
		$alerts = self::$_db->readAccountLog('all',self::$_account_id,0,20,true);
		// they come out in reverse order (by time stamp when modified) 
		$this->assertEquals('hello world',$alerts[3]['msg']);
		$this->assertEquals('03/03/1979',$alerts[3]['data_date']);
		$this->assertEquals('msg',$alerts[3]['type']);
		$this->assertEquals('0',$alerts[3]['success']);
		$this->assertEquals('hello world cogs',$alerts[2]['msg']);
		$this->assertEquals('03/05/1979',$alerts[2]['data_date']);
		$this->assertEquals('cogs',$alerts[2]['type']);
		$this->assertEquals('0',$alerts[2]['success']);
		$this->assertEquals('hello world cogs 2',$alerts[1]['msg']);
		$this->assertEquals('03/06/1979',$alerts[1]['data_date']);
		$this->assertEquals('cogs',$alerts[1]['type']);
		$this->assertEquals('1',$alerts[1]['success']);
		$this->assertEquals('hello world no date',$alerts[0]['msg']);
		$this->assertEquals(false,$alerts[0]['data_date']);
		$this->assertEquals('msg',$alerts[0]['type']);
		$this->assertEquals('0',$alerts[0]['success']);
		$this->assertEquals(4,count($alerts));
		
		// test read alerts cogs only
		$alerts = self::$_db->readAccountLog('cogs',self::$_account_id,0,20,true);
		$this->assertEquals('hello world cogs',$alerts[1]['msg']);
		$this->assertEquals('03/05/1979',$alerts[1]['data_date']);
		$this->assertEquals('cogs',$alerts[1]['type']);
		$this->assertEquals('0',$alerts[1]['success']);
		$this->assertEquals('hello world cogs 2',$alerts[0]['msg']);
		$this->assertEquals('03/06/1979',$alerts[0]['data_date']);
		$this->assertEquals('cogs',$alerts[0]['type']);
		$this->assertEquals('1',$alerts[0]['success']);
		$this->assertEquals(2,count($alerts));
		
		// test read alerts cogs only 1 per page first page
		$alerts = self::$_db->readAccountLog('cogs',self::$_account_id,0,1,true);
		$this->assertEquals('hello world cogs 2',$alerts[0]['msg']);
		$this->assertEquals('03/06/1979',$alerts[0]['data_date']);
		$this->assertEquals('cogs',$alerts[0]['type']);
		$this->assertEquals('1',$alerts[0]['success']);
		$this->assertEquals(1,count($alerts));
		
		// test read cogs only 1 per page second page
		$alerts = self::$_db->readAccountLog('cogs',self::$_account_id,1,1,false);
		$this->assertEquals('hello world cogs',$alerts[0]['msg']);
		$this->assertEquals('03/05/1979',$alerts[0]['data_date']);
		$this->assertEquals('cogs',$alerts[0]['type']);
		$this->assertEquals('0',$alerts[0]['success']);
		$this->assertEquals(1,count($alerts));
		
		// sales default parameters
		$alerts = self::$_db->readAccountLog('sales',self::$_account_id);
		$this->assertEquals('hello world sales',$alerts[0]['msg']);
		$this->assertEquals('03/04/1979',$alerts[0]['data_date']);
		$this->assertEquals('sales',$alerts[0]['type']);
		$this->assertEquals('1',$alerts[0]['success']);
		$this->assertEquals(1,count($alerts));
		
		// sales offset beyond results
		$alerts = self::$_db->readAccountLog('sales',self::$_account_id,20);
		$this->assertEquals(array(),$alerts);
	}
	
	public function testReadAccountLogFailures()
	{
		// test failures
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->exactly(2))
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->exactly(2))
			->method("execute")
			->will($this->onConsecutiveCalls(false,true));
		
		$mock_pdo_statement->expects($this->once())
			->method("fetchAll")
			->will($this->returnValue(array()));
		
		self::$_db->setPDO($mock_pdo);
		
		// execute fail
		$this->assertEquals(array(),self::$_db->readAccountLog('sales',self::$_account_id));
		
		// fetch results = none
		$this->assertEquals(array(),self::$_db->readAccountLog('sales',self::$_account_id));
	}
	
    /**
     * @depends testWriteReadAccountLogEntries
     */
	public function testHasSyncSuccessDurring()
	{
		$this->assertTrue(self::$_db->hasSyncSuccessDurring('all',self::$_account_id,DateTime::createFromFormat("m/d/Y",'03/04/1979'),DateTime::createFromFormat("m/d/Y",'03/04/1979')));
		$this->assertTrue(self::$_db->hasSyncSuccessDurring('sales',self::$_account_id,DateTime::createFromFormat("m/d/Y",'03/04/1979'),DateTime::createFromFormat("m/d/Y",'03/04/1979')));
		$this->assertFalse(self::$_db->hasSyncSuccessDurring('cogs',self::$_account_id,DateTime::createFromFormat("m/d/Y",'03/05/1979'),DateTime::createFromFormat("m/d/Y",'03/05/1979')));
	}
	
    /**
     * @depends testWriteReadAccountLogEntries
     */
	public function testHasSyncSuccessDurringFailures()
	{
		// test failures
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->exactly(2))
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->exactly(2))
			->method("execute")
			->will($this->onConsecutiveCalls(false,true));
		
		$mock_pdo_statement->expects($this->once())
			->method("fetchAll")
			->will($this->returnValue(array()));
		
		self::$_db->setPDO($mock_pdo);
		
		// execute fail
		$this->assertEquals(false,self::$_db->hasSyncSuccessDurring('all',self::$_account_id,DateTime::createFromFormat("m/d/Y",'03/04/1979'),DateTime::createFromFormat("m/d/Y",'03/04/1979')));
		
		// fetch results = none
		$this->assertEquals(false,self::$_db->hasSyncSuccessDurring('all',self::$_account_id,DateTime::createFromFormat("m/d/Y",'03/04/1979'),DateTime::createFromFormat("m/d/Y",'03/04/1979')));
	}
	
    /**
     * @depends testWriteReadAccountLogEntries
     */
	public function testGetLastSuccessfulDataDate()
	{
		$this->assertEquals('03/06/1979',self::$_db->getLastSuccessfulDataDate('all',self::$_account_id)->format('m/d/Y'));
		$this->assertEquals('03/06/1979',self::$_db->getLastSuccessfulDataDate('cogs',self::$_account_id)->format('m/d/Y'));
		$this->assertNull(self::$_db->getLastSuccessfulDataDate('orders',self::$_account_id));
	}
	
    /**
     * @depends testWriteReadAccountLogEntries
     */
	public function testGetLastSuccessfulDataDateFailures()
	{
		// test failures
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->exactly(2))
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->exactly(2))
			->method("execute")
			->will($this->onConsecutiveCalls(false,true));
		
		$mock_pdo_statement->expects($this->once())
			->method("fetchAll")
			->will($this->returnValue(array()));
		
		self::$_db->setPDO($mock_pdo);
		
		// execute fail
		$this->assertNull(self::$_db->getLastSuccessfulDataDate('all',self::$_account_id));
		
		// fetch results = none
		$this->assertNull(self::$_db->getLastSuccessfulDataDate('all',self::$_account_id));
	}
	
    /**
     * @depends testWriteReadAccountLogEntries
     */
	public function testDismissAlert()
	{
		// test read alerts
		$alerts = self::$_db->readAccountLog('all',self::$_account_id,0,20,true);
		// they come out in reverse order (by time stamp when modified) 
		$this->assertEquals('hello world',$alerts[3]['msg']);
		$this->assertEquals('03/03/1979',$alerts[3]['data_date']);
		$this->assertEquals('msg',$alerts[3]['type']);
		$this->assertEquals('0',$alerts[3]['success']);
		$this->assertEquals('hello world cogs',$alerts[2]['msg']);
		$this->assertEquals('03/05/1979',$alerts[2]['data_date']);
		$this->assertEquals('cogs',$alerts[2]['type']);
		$this->assertEquals('0',$alerts[2]['success']);
		$this->assertEquals('hello world cogs 2',$alerts[1]['msg']);
		$this->assertEquals('03/06/1979',$alerts[1]['data_date']);
		$this->assertEquals('cogs',$alerts[1]['type']);
		$this->assertEquals('1',$alerts[1]['success']);
		$this->assertEquals('hello world no date',$alerts[0]['msg']);
		$this->assertEquals(false,$alerts[0]['data_date']);
		$this->assertEquals('msg',$alerts[0]['type']);
		$this->assertEquals('0',$alerts[0]['success']);
		
		self::$_db->dismissAlert(self::$_account_id,$alerts[0]['account_log_id']);
		self::$_db->dismissAlert(self::$_account_id,$alerts[1]['account_log_id']);
		
		// test to make sure our two we dismissed are gone
		$alerts = self::$_db->readAccountLog('all',self::$_account_id,0,20,true);
		$this->assertEquals('hello world',$alerts[1]['msg']);
		$this->assertEquals('03/03/1979',$alerts[1]['data_date']);
		$this->assertEquals('msg',$alerts[1]['type']);
		$this->assertEquals('0',$alerts[1]['success']);
		$this->assertEquals('hello world cogs',$alerts[0]['msg']);
		$this->assertEquals('03/05/1979',$alerts[0]['data_date']);
		$this->assertEquals('cogs',$alerts[0]['type']);
		$this->assertEquals('0',$alerts[0]['success']);
		
		// should still be there if we query without alerts
		// test read cogs only
		$alerts = self::$_db->readAccountLog('cogs',self::$_account_id,0,20,false);
		$this->assertEquals('hello world cogs',$alerts[1]['msg']);
		$this->assertEquals('03/05/1979',$alerts[1]['data_date']);
		$this->assertEquals('cogs',$alerts[1]['type']);
		$this->assertEquals('0',$alerts[1]['success']);
		$this->assertEquals('hello world cogs 2',$alerts[0]['msg']);
		$this->assertEquals('03/06/1979',$alerts[0]['data_date']);
		$this->assertEquals('cogs',$alerts[0]['type']);
		$this->assertEquals('1',$alerts[0]['success']);
	}
	
	/**
     * @depends testWriteReadAccountLogEntries
     */
	public function testDeleteAccountLogEntry()
	{
		// test read cogs only
		$alerts = self::$_db->readAccountLog('cogs',self::$_account_id,0,20,false);
		$this->assertEquals('hello world cogs',$alerts[1]['msg']);
		$this->assertEquals('03/05/1979',$alerts[1]['data_date']);
		$this->assertEquals('cogs',$alerts[1]['type']);
		$this->assertEquals('0',$alerts[1]['success']);
		$this->assertEquals('hello world cogs 2',$alerts[0]['msg']);
		$this->assertEquals('03/06/1979',$alerts[0]['data_date']);
		$this->assertEquals('cogs',$alerts[0]['type']);
		$this->assertEquals('1',$alerts[0]['success']);
		
		self::$_db->deleteAccountLogEntry(self::$_account_id,$alerts[0]['account_log_id']);
		
		// verify delete
		$alerts = self::$_db->readAccountLog('cogs',self::$_account_id,0,20,false);
		$this->assertEquals('hello world cogs',$alerts[0]['msg']);
		$this->assertEquals('03/05/1979',$alerts[0]['data_date']);
		$this->assertEquals('cogs',$alerts[0]['type']);
		$this->assertEquals('0',$alerts[0]['success']);
	}
	
	public function testWriteReadQBObjects()
	{
		$test_objects = array(
			array(
				'id'=>42,
				'type'=>'foo'
			),
			array(
				'id'=>43,
				'type'=>'bar'
			),
		);
		
		self::$_db->writeQBObjects(self::$_account_id,$test_objects);
		
		// read all
		$read_objs = self::$_db->readQBObjects('all',self::$_account_id);
		$this->assertEquals(43,$read_objs[0]['id']);
		$this->assertEquals('bar',$read_objs[0]['type']);
		$this->assertGreaterThan(0,$read_objs[0]['insert_time']);
		$this->assertEquals(42,$read_objs[1]['id']);
		$this->assertEquals('foo',$read_objs[1]['type']);
		$this->assertGreaterThan(0,$read_objs[1]['insert_time']);
		$this->assertEquals(2,count($read_objs));
		
		// read all 1 per page, page 1
		$read_objs = self::$_db->readQBObjects('all',self::$_account_id,0,1);
		$this->assertEquals(43,$read_objs[0]['id']);
		$this->assertEquals('bar',$read_objs[0]['type']);
		$this->assertGreaterThan(0,$read_objs[0]['insert_time']);
		$this->assertEquals(1,count($read_objs));
		
		// read all 1 per page, page 2
		$read_objs = self::$_db->readQBObjects('all',self::$_account_id,1,1);
		$this->assertEquals(42,$read_objs[0]['id']);
		$this->assertEquals('foo',$read_objs[0]['type']);
		$this->assertGreaterThan(0,$read_objs[0]['insert_time']);
		$this->assertEquals(1,count($read_objs));
	}
	
    /**
     * @depends testWriteReadQBObjects
     */
	public function testReadQBObjectsFailures()
	{
		// test failures
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->exactly(2))
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->exactly(2))
			->method("execute")
			->will($this->onConsecutiveCalls(false,true));
		
		$mock_pdo_statement->expects($this->once())
			->method("fetchAll")
			->will($this->returnValue(array()));
		
		self::$_db->setPDO($mock_pdo);
		
		// execute fail
		$this->assertEquals(array(),self::$_db->readQBObjects('all',self::$_account_id));
		
		// fetch results = none
		$this->assertEquals(array(),self::$_db->readQBObjects('all',self::$_account_id));
	}
	
    /**
     * @depends testWriteReadQBObjects
     */
	public function testDeleteQBObjects()
	{
		// read all verify
		$read_objs = self::$_db->readQBObjects('all',self::$_account_id);
		$this->assertEquals(43,$read_objs[0]['id']);
		$this->assertEquals('bar',$read_objs[0]['type']);
		$this->assertGreaterThan(0,$read_objs[0]['insert_time']);
		$this->assertEquals(42,$read_objs[1]['id']);
		$this->assertEquals('foo',$read_objs[1]['type']);
		$this->assertGreaterThan(0,$read_objs[1]['insert_time']);
		$this->assertEquals(2,count($read_objs));
		
		// delete
		self::$_db->deleteQBObject(self::$_account_id,'bar',43);
		
		// verify delete
		$read_objs = self::$_db->readQBObjects('all',self::$_account_id);
		$this->assertEquals(42,$read_objs[0]['id']);
		$this->assertEquals('foo',$read_objs[0]['type']);
		$this->assertGreaterThan(0,$read_objs[0]['insert_time']);
		$this->assertEquals(1,count($read_objs));
	}
}

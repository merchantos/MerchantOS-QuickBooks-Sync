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
	public function testConstructSetupPDO()
	{
		$db = new mock_Sync_DatabaseForPDOFaking();
		
		$pdo = $db->getPDO();
		
		$result = $pdo->query("SELECT * FROM account WHERE 0");
		
		$this->assertTrue(is_object($result));
		
		return $db;
	}
	
    /**
     * @depends testConstructSetupPDO
     */
	public function testWriteAccountWriteFailed($db)
	{
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->once())
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->once())
			->method("execute")
			->will($this->returnValue(false));
		
		$db->setPDO($mock_pdo);
		
		$this->setExpectedException("Exception","Could not load or create account.");
		$db->writeAccount("foo");
	}
	
    /**
     * @depends testConstructSetupPDO
     */
	public function testWriteAccountWriteException($db)
	{
		$mock_pdo = $this->getMock("mock_PDOStub");
		$mock_pdo_statement = $this->getMock("PDOStatement");
		
		$mock_pdo->expects($this->once())
			->method("prepare")
			->will($this->returnValue($mock_pdo_statement));
		
		$mock_pdo_statement->expects($this->once())
			->method("execute")
			->will($this->throwException(new Exception));
		
		$db->setPDO($mock_pdo);
		
		$this->setExpectedException("Exception");
		$db->writeAccount("foo");
	}
	
	/**
	 * @depends testConstructSetupPDO
	 */
	public function testWriteAccountSuccess($db)
	{
		$db->restorePDO();
		
		$account_id = $db->writeAccount("foo");
		
		$this->assertGreaterThan(0,$account_id);
		
		return array($db,$account_id);
	}
	
	/**
	 * @depends testWriteAccountSuccess
	 */
	public function testWriteAccountDuplicate($db_account)
	{
		list($db,$account_id) = $db_account;
		
		$db->restorePDO();
		
		$duplicate_account_id = $db->writeAccount("foo");
		
		$this->assertEquals($account_id,$duplicate_account_id);
	}
	
    /**
     * @depends testWriteAccountSuccess
     */
	public function testReadAccount($db_account)
	{
		list($db,$account_id) = $db_account;
		
		$duplicate_account_id = $db->readAccount("foo");
		
		$this->assertEquals($account_id,$duplicate_account_id);
		
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
		
		$db->setPDO($mock_pdo);
		
		// execute fail
		$this->assertNull($db->readAccount("foo"));
		
		// fetch results = none
		$this->assertNull($db->readAccount("foo"));
		
		$db->restorePDO();
	}
}

<?php
require_once("config.inc.php");
require_once("Sync/DeleteQuickBooks.class.php");
require_once("IntuitAnywhere/Vendor.class.php");

/**
 * class Sync_DeleteQuickBooksTest
 */
class Sync_DeleteQuickBooksTest extends PHPUnit_Framework_TestCase
{
	public function testDeleteObjectNotSafeType()
	{
		$this->setExpectedException("Exception","Object to delete is not on our list of accepted object types.");
		
		$mock_del_qb = $this->getMock("Sync_DeleteQuickBooks",array('_getIntuitAnywhereDataObject'),array("foo","bar"));
		
		$mock_del_qb->deleteObject(0,'bat',0);
	}
	
	public function testDeleteObjectIntuitDeleteFail()
	{
		$this->setExpectedException("Exception","Could not delete Vendor with Id of 1 from QuickBooks.");
		
		$mock_ivendor = $this->getMock("IntuitAnywhere_Vendor",array("delete"),array("bat"));
		
		$mock_ivendor->expects($this->once())
			->method("delete")
			->will($this->returnValue(false));
		
		$mock_del_qb = $this->getMock("Sync_DeleteQuickBooks",array('_getIntuitAnywhereDataObject'),array("foo","bar"));
		
		$mock_del_qb->expects($this->once())
			->method("_getIntuitAnywhereDataObject")
			->with($this->equalTo("Vendor"))
			->will($this->returnValue($mock_ivendor));
		
		$mock_del_qb->deleteObject(0,'vendor',1);
	}
	
	public function testDeleteObjectDBDelete()
	{
		$this->setExpectedException("Exception","Could not delete object log entry for Vendor with Id of 43.");
		
		$mock_db = $this->getMock("Sync_Database");
		
		$mock_ivendor = $this->getMock("IntuitAnywhere_Vendor",array("delete"),array("bat"));
		
		$mock_ivendor->expects($this->once())
			->method("delete")
			->will($this->returnValue(true));
		
		$mock_db->expects($this->once())
			->method("deleteQBObject")
			->with($this->equalTo(42),$this->equalTo('vendor'),$this->equalTo(43))
			->will($this->returnValue(false));
		
		$mock_del_qb = $this->getMock("Sync_DeleteQuickBooks",array('_getIntuitAnywhereDataObject'),array("foo",$mock_db));
		
		$mock_del_qb->expects($this->once())
			->method("_getIntuitAnywhereDataObject")
			->with($this->equalTo("Vendor"))
			->will($this->returnValue($mock_ivendor));
		
		$mock_del_qb->deleteObject(42,'vendor',43);
	}
	
	public function testDeleteObjectOK()
	{
		$mock_db = $this->getMock("Sync_Database");
		
		$mock_ivendor = $this->getMock("IntuitAnywhere_Vendor",array("delete"),array("bat"));
		
		$mock_ivendor->expects($this->once())
			->method("delete")
			->will($this->returnValue(true));
		
		$mock_db->expects($this->once())
			->method("deleteQBObject")
			->with($this->equalTo(42),$this->equalTo('vendor'),$this->equalTo(43))
			->will($this->returnValue(true));
		
		$mock_del_qb = $this->getMock("Sync_DeleteQuickBooks",array('_getIntuitAnywhereDataObject'),array("foo",$mock_db));
		
		$mock_del_qb->expects($this->once())
			->method("_getIntuitAnywhereDataObject")
			->with($this->equalTo("Vendor"))
			->will($this->returnValue($mock_ivendor));
		
		$res = $mock_del_qb->deleteObject(42,'vendor',43);
		
		$this->assertTrue($res);
	}
	
	public function testGetSafeDataObjectTypes()
	{
		$types = array("account","bill","class","customer","journal-entry","payment","payment-method","vendor");
		
		foreach ($types as $type)
		{
			$mock_del_qb = $this->_getMockDeleteQBForSafeTypeTest();
			$this->assertTrue($mock_del_qb->deleteObject(42,$type,43));
		}
	}
	
	protected function _getMockDeleteQBForSafeTypeTest()
	{
		$mock_db = $this->getMock("Sync_Database");
		
		$mock_ivendor = $this->getMock("IntuitAnywhere_Vendor",array("delete"),array("bat"));
		
		$mock_ivendor->expects($this->once())
			->method("delete")
			->will($this->returnValue(true));
		
		$mock_db->expects($this->once())
			->method("deleteQBObject")
			->will($this->returnValue(true));
		
		$mock_del_qb = $this->getMock("Sync_DeleteQuickBooks",array('_getIntuitAnywhereDataObject'),array("foo",$mock_db));
		
		$mock_del_qb->expects($this->once())
			->method("_getIntuitAnywhereDataObject")
			->will($this->returnValue($mock_ivendor));
		
		return $mock_del_qb;
	}
}

<?php

require_once("Sync/Database.class.php");
require_once("IntuitAnywhere/IntuitAnywhere.class.php");

/**
 * Sync_DeleteQuickBooks: Delete data from quickbooks that was put there by this Sync app.
 *
 * @package  Sync
 * @author   Justin Laing <justin@merchantos.com>
 */
class Sync_DeleteQuickBooks {
	/**
	 * @var IntuitAnywhere
	 */
	protected $_ia;
	/**
	 * @var Sync_Database
	 */
	protected $_db;
	
	/**
	 * @param IntuitAnywhere IntuitAnywhere API access so we can delete objects
	 * @param Sync_Database Database access so we delete our reference to the object
	 * @return Sync_DeleteQuickBooks
	 */
	public function __construct($ianywhere,$db)
	{
		$this->_ia = $ianywhere;
		$this->_db = $db;
	}
	
	/**
	 * Delete an object from QuickBooks (also deletes any object entry in the database)
	 * @param integer $account_id Our account_id we are working in
	 * @param string $type The type of QuickBooks object (one of Account,Bill,Class,Customer,JounalEntry,Payment,PaymentMethod,Vendor)
	 * @return boolean True on success, false otherwise.
	 */
	public function deleteObject($account_id,$type,$id)
	{
		$id = (integer)$id;
		
		$safetype = $this->_getSafeDataObjectType($type);
		if (!$safetype)
		{
			throw new Exception("Object to delete is not on our list of accepted object types.");
		}
		
		// delete from QB
		$ia_data_obj = $this->_getIntuitAnywhereDataObject($safetype);
		$ia_data_obj->Id = $id;
		
		if (!$ia_data_obj->delete())
		{
			throw new Exception("Could not delete $safetype with Id of $id from QuickBooks.");
		}
		
		// delete from database log of objects
		if (!$this->_db->deleteQBObject($account_id,$type,$id))
		{
			throw new Exception("Could not delete object log entry for $safetype with Id of $id.");
		}
		return true;
	}
	
	protected function _getSafeDataObjectType($unsafetype)
	{
		switch (strtolower($unsafetype))
		{
			case "account":
				return "Account";
			case "bill":
				return "Bill";
			case "class":
				return "Class";
			case "customer":
				return "Customer";
			case "journal-entry":
				return "JournalEntry";
			case "payment":
				return "Payment";
			case "payment-method":
				return "PaymentMethod";
			case "vendor":
				return "Vendor";
			default:
				return false;
		}
		return false;
	}
	
	/**
	 * Override this function for unit testing mock object.
	 * @codeCoverageIgnore
	 */
	protected function _getIntuitAnywhereDataObject($safetype)
	{
		require_once("IntuitAnywhere/" . $safetype . ".class.php");
		$iaclassname = "IntuitAnywhere_".$safetype;
		return new $iaclassname($this->_ia);
	}
}

<?php

require_once("Sync/Database.class.php");

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
	}
	
	/**
	 * Delete an object from QuickBooks (also deletes any object entry in the database)
	 * @param integer $account_id Our account_id we are working in
	 * @param string $type The type of QuickBooks object (one of Account,Bill,Class,Customer,JounalEntry,Payment,PaymentMethod,Vendor)
	 * @return boolean True on success, false otherwise.
	 */
	public function deleteObject($account_id,$type,$id)
	{
		$unsafetype = $_GET['type'];
		$id = (integer)$id;
		
		$safetype = false;
		switch (strtolower($unsafetype))
		{
			case "account":
				$safetype = "Account";
				break;
			case "bill":
				$safetype = "Bill";
				break;
			case "class":
				$safetype = "Class";
				break;
			case "customer":
				$safetype = "Customer";
				break;
			case "journal-entry":
				$safetype = "JournalEntry";
				break;
			case "payment":
				$safetype = "Payment";
				break;
			case "payment-method":
				$safetype = "PaymentMethod";
				break;
			case "vendor":
				$safetype = "Vendor";
				break;
		}
		if (!$safetype)
		{
			throw new Exception("Object to delete is not on our list of accepted object types.");
		}
		
		// delete from QB
		require_once("IntuitAnywhere/" . $safetype . ".class.php");
		$iaclassname = "IntuitAnywhere_".$safetype;
		$ia_data_obj = new $iaclassname($this->_ia);
		$ia_data_obj->Id = $id;
		
		if (!$ia_data_obj->delete())
		{
			throw new Exception("Could not delete $safetype with Id of $id from QuickBooks.");
		}
		
		// delete from database log of objects
		if (!$db->deleteQBObject($account_id,$type,$id))
		{
			throw new Exception("Could not delete object log entry for $safetype with Id of $id.");
		}
		return true;
	}
}

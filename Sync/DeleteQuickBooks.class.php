<?php

require_once("database.inc.php");

class Sync_DeleteQuickBooks {
	/**
	 * @var IntuitAnywhere
	 */
	protected $_i_anywhere;
	
	public function __construct($i_anywhere)
	{
		$this->_i_anywhere = $i_anywhere;
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
		$ia_data_obj = new $iaclassname($this->_i_anywhere);
		$ia_data_obj->Id = $id;
		
		if (!$ia_data_obj->delete())
		{
			throw new Exception("Could not delete $safetype with Id of $id from QuickBooks.");
		}
		
		// delete from database log of objects
		if (!mosqb_database::deleteQBObject($account_id,$type,$id))
		{
			throw new Exception("Could not delete object log entry for $safetype with Id of $id.");
		}
		return true;
	}
}

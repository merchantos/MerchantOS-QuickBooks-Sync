<?php

require_once("MerchantOS/MOSAPICall.class.php");

class MerchantOS_Account
{
	/*
	 * @var MOSAPICall
	 */
	private $_mosapi;
	
	function __construct($apikey,$account_num=null)
	{
		$this->_mosapi = new MOSAPICall($apikey,$account_num);
	}
	
	function create($account_name,$email,$phone,$password)
	{
	    // @todo - are these values sanitized already?
	    
	    $xml = "<SystemCustomer>
    <name>".htmlentities($account_name)."</name>
	<promotionCode>QuickBooks</promotionCode>
    <SystemContact>
        <email>".htmlentities($email)."</email>
        <phone1>".htmlentities($phone)."</phone1>
    </SystemContact>
    <SystemUsers>
        <SystemUser>
            <pwd>".htmlentities($password)."</pwd>
        </SystemUser>
    </SystemUsers>
</SystemCustomer>";
	    
		$response = $this->_mosapi->makeAPICall("Account","Create",null,$xml);
		
		return $response;
	}
}

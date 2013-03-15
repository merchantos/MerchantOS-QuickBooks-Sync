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
	
	function create($account_name,$email,$firstName,$lastName,$phone,$password)
	{
		$employee = "";
		if (strlen(trim($firstName))>0 || strlen(trim($lastName))>0)
		{
			$employee = "<Employee><firstName>" . htmlentities($firstName) . "</firstName><lastName>" . htmlentities($lastName) . "</lastName></Employee>";
		}
		
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
			$employee
        </SystemUser>
    </SystemUsers>
</SystemCustomer>";
	    
		$response = $this->_mosapi->makeAPICall("Account","Create",null,$xml);
		
		return $response;
	}
}

<?php

/**
 * oauth-php: Example OAuth client for accessing Google Docs
 *
 * @author BBG
 *
 * 
 * The MIT License
 * 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

include_once("config.inc.php");

include_once("oauth/library/OAuthStore.php");
include_once("oauth/library/OAuthRequester.php");

session_start();

if (!isset($_SESSION['BaseURI']))
{
	require_once("quickbooks_get_baseurl.php");
}

//  Init the OAuthStore
$options = array(
	'consumer_key' => INTUIT_CONSUMER_KEY, 
	'consumer_secret' => INTUIT_CONSUMER_SECRET,
	'server_uri' => INTUIT_OAUTH_HOST,
	'request_token_uri' => INTUIT_REQUEST_TOKEN_URL,
	'authorize_uri' => INTUIT_AUTHORIZE_URL,
	'access_token_uri' => INTUIT_ACCESS_TOKEN_URL
);
// Note: do not use "Session" storage in production. Prefer a database
// storage, such as MySQL.
$store = OAuthStore::instance("Session", $options);

try
{
	$extra_headers = array(
		"Content-Type: application/x-www-form-urlencoded",
		"Host: qbo.intuit.com",
		"Accept-Encoding: gzip,deflate",
	);
	
	/*
	GET params
	'oauth_token' => string 'qyprdErXzJhXHMPLBANiGjOL24jkdJSM3Mvbn9nRp10DjA5X' (length=48)
	'oauth_verifier' => string 'ax7mwfk' (length=7)
	'realmId' => string '512439790' (length=9)
	'dataSource' => string 'QBO' (length=3)
	*/
	// make the docs requestrequest.
	var_dump($_SESSION);
	$body = array (
		"PageNum=1",
		"ResultsPerPage=100"
	);
	$body = join("&",$body);
	$request = new OAuthRequester($_SESSION['BaseURI']."/resource/accounts/v2/".$_SESSION['realmId'],'POST',null,$body);
	$result = $request->doRequest(0,array(CURLOPT_HTTPHEADER=>$extra_headers,CURLOPT_ENCODING=>1));
	if ($result['code'] == 200)
	{
		$xml = new SimpleXMLElement($result['body']);
		$namespaces = $xml->getNamespaces(true);
		var_dump($namespaces);
		$qbo_xml = $xml->children($namespaces["qbo"]);
		var_dump($qbo_xml);
		foreach ($qbo_xml->CdmCollections->children() as $account)
		{
			var_dump($account);
		}
	}
	else
	{
		var_dump($result);
	}
}
catch(OAuthException2 $e) {
	echo "OAuthException:  " . $e->getMessage();
	var_dump($e);
}
?>

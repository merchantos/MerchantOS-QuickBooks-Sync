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

include_once("IntuitAnywhere/IntuitAnywhere.class.php");

session_start();

try
{
	$ianywhere = new IntuitAnywhere($_SESSION);
	
	$displayName = 'MerchantOS QuickBooks Sync';
	$callbackURL = 'https://rad.localdev/QuickBooks/quickbooks_oauth_test.php';
	
	$ianywhere->initOAuth(
		$displayName,
		$callbackURL,
		array(
			'consumer_key'		=> INTUIT_CONSUMER_KEY, 
			'consumer_secret'	=> INTUIT_CONSUMER_SECRET,
			'server_uri'		=> INTUIT_OAUTH_HOST,
			'request_token_uri'	=> INTUIT_REQUEST_TOKEN_URL,
			'authorize_uri'		=> INTUIT_AUTHORIZE_URL,
			'access_token_uri'	=> INTUIT_ACCESS_TOKEN_URL
	));
	
	var_dump($_SESSION);
}
catch(Exception $e) {
	echo "Exception: " . $e->getMessage();
	var_dump($e);
}
?>

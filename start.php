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
GLOBAL $_OAUTH_INTUIT_CONFIG;

include_once("lib/SessionAccess.class.php");

include_once("oauth/library/OAuthStore.php");
include_once("oauth/library/OAuthRequester.php");

include_once("IntuitAnywhere/IntuitAnywhere.class.php");

$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

try
{
	$ianywhere = new IntuitAnywhere($qb_sess_access);
	$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,true);
	
	$user = $ianywhere->getCurrentUser();
	
	$qb_sess_access->CurrentUser = $user;
}
catch(Exception $e) {
	echo "Exception: " . $e->getMessage();
	var_dump($e);
}

?>
<html>
	<head>
		<script src="javascript/jquery-1.8.2.min.js" type="text/javascript"></script>
		<script src="javascript/mos_qb_sync.js" type="text/javascript"></script>
		<style type="text/css">
			h1, h2, h3 {
				margin-top: 10px;
				margin-bottom: 0px;
				padding: 0px;
			}
			.setup_group {
				margin-top: 10px;
				padding: 5px;
			}
			.setup_category {
				margin-top: 5px;
				padding: 10px;
			}
			.account_select {
				margin-top: 10px;
				margin-left: 10px;
			}
			.subaccounts {
				margin-top: 5px;
				margin-left: 20px;
			}
			.account_option {
				margin-top: 10px;
				margin-left: 10px;
			}
		</style>
	</head>
	<body>
		<h3>Welcome <?php echo $user['handle']; ?></h3>
		One time setup: We need to map your QuickBooks accounts to MerchantOS activities. Please select the QuickBooks account you want to record each activity under.
		<div class="setup_group">
			<h2>Sales</h2>
			<div class="setup_category">
				<h3>Sales Income</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_1" name="account_1" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
				<div class="account_option">
					<input type="checkbox" checked="checked" /> Create subaccounts for each Tax Class.
				</div>
			</div>
			<div class="setup_category">
				<h3>Payments</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_2" name="account_2" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
				<div class="account_option">
					<input type="checkbox" checked="checked" /> Create subaccounts for each Payment Type.
				</div>
			</div>
			<div class="setup_category">
				<h3>Tax</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_2" name="account_2" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
				<div class="account_option">
					<input type="checkbox" checked="checked" /> Create subaccounts for each Sales Tax.
				</div>
			</div>
			<div class="setup_category">
				<h3>Credit Accounts</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_2" name="account_2" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
			</div>
			<div class="setup_category">
				<h3>Gift Cards</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_2" name="account_2" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
			</div>
		</div>
		<div class="setup_group">
			<h2>Inventory</h2>
			<div class="setup_category">
				<h3>Cost Of Goods Sold</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_2" name="account_2" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
				<div class="account_option">
					<input type="checkbox" checked="checked" /> Create subaccounts for each Tax Class.
				</div>
			</div>
			<div class="setup_category">
				<h3>Inventory Assets</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_2" name="account_2" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
				<div class="account_option">
					<input type="checkbox" checked="checked" /> Create subaccounts for each Tax Class.
				</div>
			</div>
			<div class="setup_category">
				<h3>Cost Method</h3>
				<div class="account_option">
					<input type="radio" name="cost_method" value="Average" checked="checked" /> Average Cost
				</div>
				<div class="account_option">
					<input type="radio" name="cost_method" value="FIFO" /> FIFO Cost
				</div>
			</div>
		</div>
		<div class="setup_group">
			<h2>Ordering (POs)</h2>
			<div class="setup_category">
				<h3>Orders Expense</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_2" name="account_2" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
			</div>
			<div class="setup_category">
				<h3>Shipping Expense</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_2" name="account_2" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
			</div>
			<div class="setup_category">
				<h3>Other Exepnse</h3>
				<div class="account_select">
					<select class="qb_account_list" id="account_2" name="account_2" >
						<option value='loading'>Loading...</option>
					</select>
				</div>
			</div>
		</div>
	</body>
</html>

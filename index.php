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

$setup_sess_access = new SessionAccess("setup");
$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");

try
{
	$ianywhere = new IntuitAnywhere($qb_sess_access);
	
	$is_authorized = false;
	$is_setup = false;
	
	if ($ianywhere->isUserAuthorized())
	{
		$ianywhere->initOAuth($oauth_sess_access,INTUIT_DISPLAY_NAME,INTUIT_CALLBACK_URL,$_OAUTH_INTUIT_CONFIG,true);
		$user = $qb_sess_access->CurrentUser;
		$is_authorized = true;
	}
	if ($setup_sess_access->setupComplete)
	{
		$is_setup = true;
	}
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
			.section {
				display: none;
			}
			.section.selected_section {
				display: block;
			}
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
		<?php if ($is_authorized) { ?><h3>Welcome <?php echo $user['handle']; ?> <a href="./logout.php">Discount From QuickBooks</a></h3><? } ?>
		<div id="welcome" class="section <?php if (!$is_authorized) echo "selected_section"; ?>">
			<h1>Welcome</h1>
			The first thing we need you to do is give us access to your QuickBooks data. Click "Get Started" below to continue.
			<div>
				<a href="./oauth.php">Get Started</a>
			</div>
		</div>
		<div id="dashboard" class="section <?php if ($is_authorized && $is_setup) echo "selected_section"; ?>">
			<h1>Dashboard</h1>
		</div>
		<div id="settings" class="section <?php if ($is_authorized && !$is_setup) echo "selected_section"; ?>">
			<h1>Settings</h1>
			<div class="setup_group">
				<h2>What Data? How Often?</h2>
				<div class="setup_category">
					<h3>Schedule</h3>
					We want to give you time to fix mistakes in MerchantOS before sending them to QuickBooks. So how long do you want to hold data in MerchantOS before sending it to QuickBooks?
					<div>
						Send Data After
						<select id="setup_data_delay">
							<option value="1">1 Day</option>
							<option value="2">2 Days</option>
							<option value="3">3 Days</option>
							<option value="4">4 Days</option>
							<option value="5">5 Days</option>
							<option value="6">6 Days</option>
							<option value="7">1 Week</option>
							<option value="14">2 Weeks</option>
							<option value="21">3 Weeks</option>
							<option value="month">1 Month</option>
						</select>
					</div>
					<h3>Data</h3>
					Choose which type of data you want to send to QuickBooks.
					<div>
						<div>
							<input type="checkbox" id="setup_send_sales" checked="checked" class="setup_group_toggle" /> Sales, Payments, and Tax
						</div>
						<div>
							<input type="checkbox" id="setup_send_inventory" checked="checked" class="setup_group_toggle" /> Cost of Goods Sold and Inventory
						</div>
						<div>
							<input type="checkbox" id="setup_send_orders" checked="checked" class="setup_group_toggle" /> Orders (POs)
						</div>
					</div>
			</div>
			<h2>Chart Of Accounts</h2>
			We need to map your QuickBooks accounts to MerchantOS activities. Please select the QuickBooks account you want to record each activity under.
			<div class="setup_group" id="setup_group_sales">
				<h2>Sales</h2>
				<div class="setup_category">
					<h3>Sales Income</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_sales" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
					<div class="account_option">
						<input type="checkbox" checked="checked"  class="setup_field" id="setup_sales_subaccounts" /> Create subaccounts for each Tax Class.
					</div>
				</div>
				<div class="setup_category">
					<h3>Payments</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_payments" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
					<div class="account_option">
						<input type="checkbox" checked="checked" class="setup_field" id="setup_payments_subaccounts" /> Create subaccounts for each Payment Type.
					</div>
				</div>
				<div class="setup_category">
					<h3>Tax</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_tax" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
					<div class="account_option">
						<input type="checkbox" checked="checked" class="setup_field" id="setup_tax_subaccounts" /> Create subaccounts for each Sales Tax.
					</div>
				</div>
				<div class="setup_category">
					<h3>Credit Accounts</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_credit_accounts" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
				<div class="setup_category">
					<h3>Gift Cards</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_gift_cards" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
			</div>
			<div class="setup_group" id="setup_group_inventory">
				<h2>Inventory</h2>
				<div class="setup_category">
					<h3>Cost Of Goods Sold</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_cogs" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
					<div class="account_option">
						<input type="checkbox" checked="checked" class="setup_field" id="setup_cogs_subaccounts" /> Create subaccounts for each Tax Class.
					</div>
				</div>
				<div class="setup_category">
					<h3>Inventory Assets</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_inventory" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
					<div class="account_option">
						<input type="checkbox" checked="checked" class="setup_field" id="setup_inventory_subaccounts" /> Create subaccounts for each Tax Class.
					</div>
				</div>
			</div>
			<div class="setup_group" id="setup_group_orders">
				<h2>Ordering (POs)</h2>
				<div class="setup_category">
					<h3>Orders Expense</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_orders" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
				<div class="setup_category">
					<h3>Shipping Expense</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_orders_shipping" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
				<div class="setup_category">
					<h3>Other Exepnse</h3>
					<div class="account_select">
						<select class="qb_account_list" id="setup_orders_other" class="setup_field" >
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>

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
		<link href="css/normalize.css" rel="stylesheet" type="text/css" />
		<link href="css/style.css" rel="stylesheet" type="text/css" />
		<title>MerchantOS - Quickbooks Sync</title>
	</head>
	<body>
	    <header>
	        <h3><a href="http://merchantos.com">MerchantOS</a></h3>
            <h1>Quickbooks Sync</h1>
	    </header>
	    <p>
	    	<?php if ($is_authorized) { ?>
    		    <h3>Welcome <?php echo $user['handle']; ?> <a href="./logout.php">Disconnect From QuickBooks</a></h3>
    		<? } ?>

		<section id="welcome" class="<?php if (!$is_authorized) echo "selected"; ?>">
			<h1>Welcome</h1>
			<p>To get started we'll need access to your QuickBooks data.</p>
			<a href="./oauth.php" class="button">Get Started &rarr;</a>
		</section>
		
		<section id="dashboard" class="<?php if ($is_authorized && $is_setup) echo "selected"; ?>">
			<h1>Dashboard</h1>
		</section>
		
		<section id="settings" class="<?php if ($is_authorized && !$is_setup) echo "selected"; ?>">
		    <form id="settings_form">
    			<h1>Settings</h1>		
                <fieldset>
    				<legend>Schedule</legend>
    				<p>Want a little extra time to fix mistakes in MerchantOS before sending them to QuickBooks?</p>
    				<ol>
    				    <li>
    			            <label for="setup_data_delay">Send Data After</label>
            				<select id="setup_data_delay" name="data_delay" class="setup_field">
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
    				    </li>
    				</ol>
    			</fieldset>
                <fieldset class="setup_group_toggle">
                    <legend>Data</legend>
                    <p>What data do you want sent to QuickBooks.
    				<ol class="checkboxes">
    				    <li>
    				        <label>
            					<input type="checkbox" name="send_sales" id="setup_send_sales" checked="checked" class="setup_field" /> Sales, Payments, and Tax
            				</label>
                        </li>
                        <li>
    				        <label>
    					        <input type="checkbox" name="send_inventory" id="setup_send_inventory" checked="checked" class="setup_field" /> Cost of Goods Sold and Inventory
            				</label>
                        </li>
                        <li>
            				<label>
            					<input type="checkbox" name="send_orders" id="setup_send_orders" checked="checked" class="setup_field" /> Orders (POs)
    				        </label>
                        </li>
                    </ol>
    			</fieldset>

                <fieldset id="chart">
    			    <legend>Chart Of Accounts</legend>
        			<p>Map MerchantOS activities to QuickBooks.</p>

                    <div class="labels">
                        <p class="merchantos">MerchantOS Activity</p>
                        <p class="quickbooks">Quickbooks Account</p>
    			    </div>
    			    <fieldset id="setup_group_sales">
            			<legend>Sales</legend>
        			
        				<div class="setup_category">
    					   <label for="setup_sales">Sales Income</label>
        				   <div class="account_select">
        				       <select class="qb_account_list" id="setup_sales" name="sales" class="setup_field" >
        						   <option value='loading'>Loading...</option>
        					   </select>
        					   <label><input type="checkbox" checked="checked"  class="setup_field" id="setup_sales_subaccounts" name="sales_subaccounts" /> Create subaccounts for each Tax Class.</label>
        					</div>
        				</div>
        				<div class="setup_category">
        					<label for="setup_payments">Payments</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_payments" class="setup_field" name="payments" >
        							<option value='loading'>Loading...</option>
        						</select>
            					<label><input type="checkbox" checked="checked" class="setup_field" id="setup_payments_subaccounts" name="payments_subaccounts" /> Create subaccounts for each Payment Type.</label>
        					</div>
        				</div>
        				<div class="setup_category">
        					<label for="qb_account_list">Tax</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_tax" class="setup_field" name="tax" >
        							<option value='loading'>Loading...</option>
        						</select>
        						<label><input type="checkbox" checked="checked" class="setup_field" id="setup_tax_subaccounts" name="tax_subaccounts" /> Create subaccounts for each Sales Tax.</label>
            				</div>
        				</div>
        				<div class="setup_category">
        					<label for="qb_account_list">Credit Accounts</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_credit_accounts" class="setup_field" name="credit_accounts" >
        							<option value='loading'>Loading...</option>
        						</select>
        					</div>
        				</div>
        				<div class="setup_category">
        					<label for="setup_gift_cards">Gift Cards</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_gift_cards" class="setup_field" name="gift_cards" >
        							<option value='loading'>Loading...</option>
        						</select>
        					</div>
        				</div>
        			</fieldset>
    			    <fieldset id="setup_group_inventory">
    				    <legend>Inventory</legend>
    				    <div class="setup_category">
    					    <label for="setup_cogs">Cost Of Goods Sold</label>
    					    <div class="account_select">
    						    <select class="qb_account_list" id="setup_cogs" class="setup_field" name="cogs" >
    							    <option value='loading'>Loading...</option>
    						    </select>
                                <label><input type="checkbox" checked="checked" class="setup_field" id="setup_cogs_subaccounts" name="cogs_subaccounts" /> Create subaccounts for each Tax Class.</label>
        					</div>
    				    </div>
    				    <div class="setup_category">
    					    <label for="setup_inventory">Inventory Assets</label>
    					    <div class="account_select">
    						    <select class="qb_account_list" id="setup_inventory" class="setup_field" name="inventory" >
    							    <option value='loading'>Loading...</option>
    						    </select>
    						    <label><input type="checkbox" checked="checked" class="setup_field" id="setup_inventory_subaccounts" name="inventory_subaccounts" /> Create subaccounts for each Tax Class.</label>
    					    </div>
    				    </div>
    			    </fieldset>
    			    <fieldset id="setup_group_orders">
    				    <legend>Ordering (POs)</legend>
    				    <div class="setup_category">
    					    <label for="setup_orders">Orders Expense</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_orders" class="setup_field" >
        							<option value='loading'>Loading...</option>
        						</select>
        					</div>
        				</div>
        				<div class="setup_category">
        					<label for="setup_orders_shipping">Shipping Expense</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_orders_shipping" class="setup_field" >
        							<option value='loading'>Loading...</option>
        						</select>
        					</div>
        				</div>
        				<div class="setup_category">
        					<label for="setup_orders_other">Other Expense</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_orders_other" class="setup_field" >
        							<option value='loading'>Loading...</option>
        						</select>
        					</div>
        				</div>
    			    </fieldset>
    		    </fieldset>
				<button type="submit">Save Settings</button>
    		</form>
		</section>
	</body>
</html>

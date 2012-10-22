<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.php");

require_once("IntuitAnywhere/IntuitAnywhere.class.php");

$setup_sess_access = new SessionAccess("setup");
$qb_sess_access = new SessionAccess("qb");
$oauth_sess_access = new SessionAccess("oauth");
$merchantos_sess_access = new SessionAccess("merchantos");

try
{
	$ianywhere = new IntuitAnywhere($qb_sess_access);
	
	$is_authorized = false;
	$is_setup = false;
	
	if ($ianywhere->isUserAuthorized())
	{
		$user = $qb_sess_access->CurrentUser;
		$is_authorized = true;
		
		if (isset($setup_sess_access->data_delay))
		{
			$is_setup = true;
			
			if ($_GET['return_url'] && $_GET['return_on_setup'])
			{
				header ("location: " . $_GET['return_url']);
				exit;
			}
		}
	}
}
catch(Exception $e) {
	echo "Exception: " . $e->getMessage();
	var_dump($e);
}

?>
<html>
	<head>
		<link href="css/normalize.css" rel="stylesheet" type="text/css" />
		<link href="css/style.css" rel="stylesheet" type="text/css" />
		<title>MerchantOS - QuickBooks Sync</title>
	</head>
	<body>
	    <header>
	        <h3><a href="http://merchantos.com">MerchantOS</a></h3>
            <h1>Quickbooks Sync</h1>
    		<ul class="user">
    		    <?php if ($is_authorized) { ?><li class="block"><?php echo $user['handle']; ?></li><?php } ?>
    		    <li class="logout"><a href="<?php echo $merchantos_sess_access->return_url; if ($_GET['disconnected'] || !$ianywhere->isUserAuthorized()) echo "&disconnected=1"; ?>">Return to MerchantOS &rarr; </a></li>
    		</ul>
	    </header>


		<div id="loading"><img src="images/loading.gif" height="16" width="16" border="0"> loading your data...</div>


		<section id="welcome" class="<?php if (!$is_authorized) echo "selected"; ?>" style="display: none;">
			<h1>Welcome</h1>
			<p>To get started we'll need access to your QuickBooks data.</p>
			<a href="./oauth.php" class="button">Get Started &rarr;</a>
		</section>
		
		<section id="dashboard" class="<?php if ($is_authorized && $is_setup) echo "selected"; ?>" >
			<header>
    			<h1>Dashboard</h1>
			    <nav>
			        <ul>
			            <li><a href="#settings">Sync Settings</a></li>
			        </ul>
			    </nav>
			</header>
			
			<h2>History</h2>
			<dl>
			    <dt>October 17, 2012 at 4:13pm</dt>
			    <dd>Synced 10/12 to 10/12 with $459 Sales, $360 Cost of Goods Sold, $385 Orders.</dd>
			    <dt>October 16, 2012 at 4:16pm</dt>
			    <dd>Synced 10/11 to 10/11 with $438 Sales, $258 Cost of Goods Sold, $392 Orders.</dd>
			    <dt>October 15, 2012 at 4:22pm</dt>
			    <dd>Synced 10/10 to 10/10 with $211 Sales, $150 Cost of Goods Sold, $0 Orders.</dd>
			    <dt>October 14, 2012 at 4:01pm</dt>
			    <dd>Synced 10/9 to 10/9 with no activity.</dd>
			    <dt>October 13, 2012 at 4:36pm</dt>
			    <dd>Synced 10/8 to 10/8 with $498 Sales, $412 Cost of Goods Sold, $695 Orders.</dd>
			    <dt>October 12, 2012 at 4:12pm</dt>
			    <dd>Synced 10/8 to 10/8 with $389 Sales, $263 Cost of Goods Sold, $328 Orders.</dd>
			</dl>
			<a href="./oauth.php" class="button">Sync Now</a>
			<a href="<?php echo $merchantos_sess_access->return_url; if ($_GET['disconnected'] || !$ianywhere->isUserAuthorized()) echo "&disconnected=1"; ?>">Return to MerchantOS &rarr; </a>
		</section>
		
		<section id="settings" class="<?php if ($is_authorized && !$is_setup) echo "selected"; ?>" style="display: none;">
		    <form id="settings_form">
    			<h1>Settings</h1>		
                <fieldset>
    				<h2>Schedule</h2>
    				<p>Want a little extra time to fix mistakes in MerchantOS before sending them to QuickBooks?</p>
    				<ol>
    				    <li>
    			            <label for="setup_data_delay">Send Data After</label>
            				<select id="setup_data_delay" name="data_delay" class="setup_field">
            					<option value="-1 day">1 Day</option>
            					<option value="-2 days">2 Days</option>
            					<option value="-3 days">3 Days</option>
            					<option value="-4 days">4 Days</option>
            					<option value="-5 days">5 Days</option>
            					<option value="-6 days">6 Days</option>
            					<option value="-1 week">1 Week</option>
            					<option value="-2 weeks">2 Weeks</option>
            					<option value="-3 weeks">3 Weeks</option>
            					<option value="-1 month">1 Month</option>
            				</select>
    				    </li>
    				</ol>
					<br />
					<p>What date would you like to go back to for data sent to QuickBooks?</p>
    				<label for="setup_start_date">Starting From</label>
            		<input type="input" id="setup_start_date" name="start_date" value="2012-10-01" size="10" />
    			</fieldset>
				<fieldset id="shop_locations">
					<h2>Shop Locations</h2>
					<p>Which locations do you want to sync with QuickBooks?</p>
					<ol class="checkboxes">
					</ol>
				</fieldset>
                <fieldset class="setup_group_toggle">
                    <h2>Data</h2>
                    <p>What data do you want sent to QuickBooks. To turn this integration off unselect all three.
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
    			    <h2>Chart Of Accounts</h2>
        			<p>Map MerchantOS activities to QuickBooks.</p>

                    <div class="labels">
                        <p class="merchantos">MerchantOS Activity</p>
                        <p class="quickbooks">QuickBooks Account</p>
    			    </div>
    			    <fieldset id="setup_group_sales">
            			<h3>Sales</h3>
        			
        				<div class="setup_category">
    					   <label for="setup_sales">Sales Income</label>
        				   <div class="account_select">
        				       <select data-placeholder="Choose a category" class="qb_account_list" id="setup_sales" name="sales" >
        						   <option value='loading'>Loading...</option>
        					   </select>
        					   <!--<label><input type="checkbox" checked="checked"  class="setup_field" id="setup_sales_subaccounts" name="sales_subaccounts" /> Create subaccounts for each Tax Class.</label>-->
        					</div>
        				</div>
						<div class="setup_category">
        					<label for="setup_payments">Discounts</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_discounts" name="discounts" >
        							<option value='loading'>Loading...</option>
        						</select>
        					</div>
        				</div>
        				<div class="setup_category">
        					<label for="setup_payments">Payments</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_payments" name="payments" >
        							<option value='loading'>Loading...</option>
        						</select>
            					<!--<label><input type="checkbox" checked="checked" class="setup_field" id="setup_payments_subaccounts" name="payments_subaccounts" /> Create subaccounts for each Payment Type.</label>-->
        					</div>
        				</div>
        				<div class="setup_category">
        					<label for="qb_account_list">Tax</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_tax" name="tax" >
        							<option value='loading'>Loading...</option>
        						</select>
        						<!--<label><input type="checkbox" checked="checked" class="setup_field" id="setup_tax_subaccounts" name="tax_subaccounts" /> Create subaccounts for each Sales Tax.</label>-->
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
    				    <h3>Inventory</h3>
    				    <div class="setup_category">
    					    <label for="setup_cogs">Cost Of Goods Sold</label>
    					    <div class="account_select">
    						    <select class="qb_account_list" id="setup_cogs" class="setup_field" name="cogs" >
    							    <option value='loading'>Loading...</option>
    						    </select>
                                <!--<label><input type="checkbox" checked="checked" class="setup_field" id="setup_cogs_subaccounts" name="cogs_subaccounts" /> Create subaccounts for each Tax Class.</label>-->
        					</div>
    				    </div>
    				    <div class="setup_category">
    					    <label for="setup_inventory">Inventory Assets</label>
    					    <div class="account_select">
    						    <select class="qb_account_list" id="setup_inventory" class="setup_field" name="inventory" >
    							    <option value='loading'>Loading...</option>
    						    </select>
    						    <!--<label><input type="checkbox" checked="checked" class="setup_field" id="setup_inventory_subaccounts" name="inventory_subaccounts" /> Create subaccounts for each Tax Class.</label>-->
    					    </div>
    				    </div>
    			    </fieldset>
    			    <fieldset id="setup_group_orders">
    				    <h3>Ordering (POs)</h3>
    				    <div class="setup_category">
    					    <label for="setup_orders">Orders Expense</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_orders" class="setup_field" name="orders" >
        							<option value='loading'>Loading...</option>
        						</select>
        					</div>
        				</div>
        				<div class="setup_category">
        					<label for="setup_orders_shipping">Shipping Expense</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_orders_shipping" class="setup_field" name="orders_shipping" >
        							<option value='loading'>Loading...</option>
        						</select>
        					</div>
        				</div>
        				<div class="setup_category">
        					<label for="setup_orders_other">Other Expense</label>
        					<div class="account_select">
        						<select class="qb_account_list" id="setup_orders_other" class="setup_field" name="orders_other" >
        							<option value='loading'>Loading...</option>
        						</select>
        					</div>
        				</div>
    			    </fieldset>
    		    </fieldset>
				<input type="submit" value="Save Settings" class="submit" />
				<?php if ($is_setup) { ?><a href="javascript: mosqb.sections.activate('dashboard');">Cancel</a><?php } ?>
    		</form>
    		
    		<?php if ($is_authorized) { ?>
    		<div class="scary">
	    	    <h3><a href="./disconnect.php" onclick="if (!confirm('Disconnect and stop syncing with QuickBooks?')) return false; return true;">Disconnect From QuickBooks</a></h3>
	    	    <p>Disconnecting from QuickBooks will prevent MerchantOS from syncing any data into your QuickBooks account. You can reconnect at any time.</p>
	    	</div>
	    	<?php } ?>
		</section>

        <script src="javascript/jquery-1.8.2.min.js" type="text/javascript"></script>
		<script src="javascript/mos_qb_sync.js" type="text/javascript"></script>
	</body>
</html>

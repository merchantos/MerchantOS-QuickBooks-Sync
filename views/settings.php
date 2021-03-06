<div id="loading"><img src="assets/images/loading.gif" height="16" width="16" border="0"> loading your data...</div>
<div id="errors" style="display: none;">
	<ul></ul>
	<button>Close</button>
</div>

<section id="welcome" class="<?php if (!$is_authorized) echo "selected"; ?>" style="display: none;">
	<h1>Welcome</h1>
	<p>To get started we'll need access to your QuickBooks data.</p>
	<a href="./oauth.php" class="button">Get Started &rarr;</a>
</section>

<section id="dashboard" class="<?php if ($is_authorized && $is_setup) echo "selected"; ?>" style="display: none;">
	<header>
		<h1>Dashboard</h1>
	    <nav>
	        <ul>
	            <li><a href="#settings">Sync Settings</a></li>
	        </ul>
	    </nav>
	</header>
	<div style="display: none;">
		<h2>Alerts</h2>
		<dl class='alerts'>
			<dt>Loading...</dt>
		</dl>
	</div>
	<h2>History</h2>
	<dl class='history'>
	    <dt>Loading...</dt>
	</dl>
	<a href="#syncnow" class="button">Sync Now</a>
	<a href="<?php echo $merchantos_sess_access->return_url; if (isset($_GET['disconnected']) || !$ianywhere->isUserAuthorized()) echo "&disconnected=1"; ?>">Return to MerchantOS &rarr; </a>
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
						<select data-placeholder="Choose a category" class="qb_account_list" id="setup_sales" name="sales" default_account="Sales of Product Income">
							<option value='loading'>Loading...</option>
						</select>
						<!--<label>We will automatically create a QuickBooks class for each tax class.</label>-->
						<!--<label><input type="checkbox" checked="checked"  class="setup_field" id="setup_sales_subaccounts" name="sales_subaccounts" /> Create subaccounts for each Tax Class.</label>-->
					</div>
				</div>
				<div class="setup_category">
					<label for="setup_discounts">Discounts</label>
					<div class="account_select">
						<select class="qb_account_list" id="setup_discounts" name="discounts" default_account="Discounts given">
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
				<div class="setup_category">
					<label for="setup_payments">Payments</label>
					<div class="account_select">
						<!--<select class="qb_account_list" id="setup_payments" name="payments" default_account="Undeposited Funds">
							<option value='loading'>Loading...</option>
						</select>-->
						Undeposited Funds
    					<!--<label><input type="checkbox" checked="checked" class="setup_field" id="setup_payments_subaccounts" name="payments_subaccounts" /> Create subaccounts for each Payment Type.</label>-->
					</div>
				</div>
				<div class="setup_category">
					<label for="setup_payments">Accounts Receivable</label>
					<div class="account_select">
						<select class="qb_account_list" id="setup_accounts_receivable" name="accounts_receivable" default_account="Accounts Receivable (A/R)">
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
				<div class="setup_category">
					<label for="setup_credit_accounts">Credit Accounts</label>
					<div class="account_select">
						<select class="qb_account_list" id="setup_credit_accounts" class="setup_field" name="credit_accounts" default_account="Customer Credit Accounts">
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
				<div class="setup_category">
					<label for="setup_gift_cards">Gift Cards</label>
					<div class="account_select">
						<select class="qb_account_list" id="setup_gift_cards" class="setup_field" name="gift_cards" default_account="Customer Gift Cards">
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
			</fieldset>
		    <fieldset id="setup_group_tax">
			    <h3>Sales Tax</h3>
				<div class="setup_category">
					<label for="qb_account_list">Sales Tax</label>
					<div class="account_select">
						<select class="qb_account_list" id="setup_tax" name="tax" default_account="Sales Tax Agency Payable">
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
					    <select class="qb_account_list" id="setup_cogs" class="setup_field" name="cogs" default_account="Cost of Goods Sold">
						    <option value='loading'>Loading...</option>
					    </select>
						<!--<label>We will automatically create a QuickBooks class for each tax class.</label>
                        <!--<label><input type="checkbox" checked="checked" class="setup_field" id="setup_cogs_subaccounts" name="cogs_subaccounts" /> Create subaccounts for each Tax Class.</label>-->
					</div>
			    </div>
			    <div class="setup_category">
				    <label for="setup_inventory">Inventory Assets</label>
				    <div class="account_select">
					    <select class="qb_account_list" id="setup_inventory" class="setup_field" name="inventory" default_account="Inventory Asset">
						    <option value='loading'>Loading...</option>
					    </select>
						<!--<label>We will automatically create a QuickBooks class for each tax class.</label>-->
					    <!--<label><input type="checkbox" checked="checked" class="setup_field" id="setup_inventory_subaccounts" name="inventory_subaccounts" /> Create subaccounts for each Tax Class.</label>-->
				    </div>
			    </div>
		    </fieldset>
		    <fieldset id="setup_group_orders">
			    <h3>Ordering (POs)</h3>
			    <div class="setup_category">
				    <label for="setup_orders">Product Cost</label>
					<!--<div class="account_select">
						<select class="qb_account_list" id="setup_orders" class="setup_field" name="orders" default_account="Purchases">
							<option value='loading'>Loading...</option>
						</select>
					</div>-->
					<div class="account_select">
						Inventory Assets
					</div>
				</div>
				<div class="setup_category">
					<label for="setup_orders_shipping">Shipping Expense</label>
					<div class="account_select">
						<select class="qb_account_list" id="setup_orders_shipping" class="setup_field" name="orders_shipping" default_account="Cost of Goods Sold">
							<option value='loading'>Loading...</option>
						</select>
					</div>
				</div>
				<div class="setup_category">
					<label for="setup_orders_other">Other Expense</label>
					<div class="account_select">
						<select class="qb_account_list" id="setup_orders_other" class="setup_field" name="orders_other" default_account="Cost of Goods Sold">
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
	<div style='margin-top: 20px; font-size: 10px; text-align: right;'>
		<a href="#objects">Objects</a>
	</div>
</section>
<section id="objects" style="display: none;">
	<h2>Objects Created In QuickBooks</h2>
	<dl class='objects'>
	</dl>
	<a href="javascript: mosqb.sections.activate('dashboard');">Close</a>
</section>

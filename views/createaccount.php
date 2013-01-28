<form id="signup" action="" method="post"> 
	<dl>
	    <dt><label for="shop_name">Business Name</label></dt>
		<dd><input name="shop_name" type="text" required="required" label="Business Name" value="<?php htmlentities($shop_name) ?>"></dd>
		
		<dt><label for="email">Email Address</label></dt>
		<dd><input label="Email Address" name="email" type="email" autofocus required="required" value="<?php htmlentities($email) ?>"></dd>

		<dt><label for="phone">Phone</label></dt>
		<dd><input name="phone" type="tel" required="required" minlength="10" label="Phone Number" value="<?php htmlentities($phone) ?>"></dd>
		
		<dt><label for="password">Password</label></dt>
		<dd><input class="small" name="password" type="password" autocomplete="off" required="required" minlength="6" label="Password">
			<p class="hint">Please enter a secure password (6 characters minimum).</p>
		</dd>
	</dl>

    <div class="submit">
    	  <input type="submit" value="Create my account" />
    	  <p class="terms">By clicking "Create My Account" you agree to the MerchantOS <a href="/terms">Terms of Service</a> and <a href="/privacy">Privacy Policy</a>.</p>
    </div>
</form>
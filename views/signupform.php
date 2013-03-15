<section id="createaccount">
    <h1>Create Your Account</h1>

    <form id="signup" action="" method="post">
    	<dl>
    		<dt><label for="email">Email Address</label></dt>
    		<dd><input label="Email Address" name="email" type="email" size="40" autofocus required="required" value="<?php echo htmlentities($email) ?>"></dd>
   	</dl>

        <div class="submit">
      	    <input type="hidden" name="form_name" value="createaccount" />
            <input type="submit" class="button" value="Create My Account" />
        	<p class="terms">By clicking "Create My Account" you agree to the MerchantOS <a href="http://merchantos.com/terms" target="_blank">Terms of Service</a> and <a href="http://merchantos.com/privacy" target="_blank">Privacy Policy</a>.</p>
			<p class="haveaccount">Already have an account? <a href="<?php echo $openid_login_url; ?>">Login</a></p>
        </div>
    </form>

</section>

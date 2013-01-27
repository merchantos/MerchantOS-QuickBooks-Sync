<?php

require_once("config.inc.php");
GLOBAL $_OAUTH_INTUIT_CONFIG;

require_once("session.inc.php");

$login_sess_access = new SessionAccess("login");

if (isset($login_sess_access->account_id))
{
	// they are already setup with an account, so go to the normal welcome screen.
	header("location: ./");
	exit;
}

/*
 * They have no account so we'll continue, and call idrectConnectToIntuit() with the javascript library
 * We'll record that we are in account creation mode, so when we return from oauth.php we'll know where to go
 */
$login_sess_access->account_creation = true;

?>
<html>
	<head>
		<link href="css/normalize.css" rel="stylesheet" type="text/css" />
		<link href="css/style.css" rel="stylesheet" type="text/css" />
		<title>MerchantOS - QuickBooks Sync</title>
	</head>
	<body>
		<ipp:blueDot></ipp:blueDot>
	
	    <header>
	        <h3><a href="http://merchantos.com">MerchantOS</a></h3>
            <h1>QuickBooks Sync</h1>
    	</header>
		
		<div id="loading"><img src="images/loading.gif" height="16" width="16" border="0"> loading your data...</div>
		<div id="errors" style="display: none;">
			<ul></ul>
			<button>Close</button>
		</div>
		
		<section id="welcome" class="selected">
			<h1>Welcome</h1>
			<p>To get started we'll need access to your QuickBooks data.</p>
			<a href="./oauth.php" class="button">Get Started &rarr;</a>
		</section>
		
        <script src="javascript/jquery-1.8.2.min.js" type="text/javascript"></script>
		<script src="javascript/mos_qb_sync.js" type="text/javascript"></script>
		<script type="text/javascript" src="https://appcenter.intuit.com/Content/IA/intuit.ipp.anywhere.js"></script>
		<script>
		intuit.ipp.anywhere.setup({
			menuProxy: 'https://<?php echo $_SERVER['HTTP_HOST']; ?>/QuickBooks/menuproxy.php',
			grantUrl: 'https://<?php echo $_SERVER['HTTP_HOST']; ?>/QuickBooks/oauth.php'
		});
		intuit.ipp.anywhere.directConnectToIntuit();
		</script>
	</body>
</html>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
		<script src="assets/javascript/mos_qb_sync.js" type="text/javascript"></script>
		<script type="text/javascript" src="https://appcenter.intuit.com/Content/IA/intuit.ipp.anywhere.js"></script>
		<?php if (isset($_SERVER['HTTP_HOST'])) { ?>
			<script>
			intuit.ipp.anywhere.setup({
				menuProxy: 'https://<?php echo $_SERVER['HTTP_HOST']; ?>/QuickBooks/menuproxy.php',
				grantUrl: 'https://<?php echo $_SERVER['HTTP_HOST']; ?>/QuickBooks/oauth.php'
			});
			</script>
		<?php } ?>
		<?php $is_authorized ?>
			<script>
				if (window.opener) {
					window.opener.location.href = window.opener.location.href;
					window.close();
				}
			</script>
	</body>
</html>

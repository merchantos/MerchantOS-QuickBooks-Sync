        <script src="javascript/jquery-1.8.2.min.js" type="text/javascript"></script>
		<script src="javascript/mos_qb_sync.js" type="text/javascript"></script>
		<script type="text/javascript" src="https://appcenter.intuit.com/Content/IA/intuit.ipp.anywhere.js"></script>
		<script>
		intuit.ipp.anywhere.setup({
			menuProxy: 'https://<?php echo $_SERVER['HTTP_HOST']; ?>/QuickBooks/menuproxy.php',
			grantUrl: 'https://<?php echo $_SERVER['HTTP_HOST']; ?>/QuickBooks/oauth.php'
		});
		<?php if(isset($incoming) && $incoming) { ?>
		 intuit.ipp.anywhere.directConnectToIntuit();
		<?php } ?>
		</script>
	</body>
</html>
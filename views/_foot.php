        <script src="javascript/jquery-1.8.2.min.js" type="text/javascript"></script>
		<script src="javascript/mos_qb_sync.js" type="text/javascript"></script>
		<?php if ($is_authorized) { ?>
			<script type="text/javascript" src="https://appcenter.intuit.com/Content/IA/intuit.ipp.anywhere.js"></script>
			<script>
			intuit.ipp.anywhere.setup({
				menuProxy: 'https://<?php echo $_SERVER['HTTP_HOST']; ?>/QuickBooks/menuproxy.php',
				grantUrl: 'https://<?php echo $_SERVER['HTTP_HOST']; ?>/QuickBooks/oauth.php'
			});
			</script>
		<?php } ?>
	</body>
</html>
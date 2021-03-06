<html>
	<head>
		<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<title>MerchantOS - QuickBooks Sync</title>
	</head>
	<body>
		<ipp:blueDot></ipp:blueDot>
	    <header>
	        <h3><a href="http://merchantos.com">MerchantOS</a></h3>
            <?php if (!isset($no_title)) { ?>
				<h1>QuickBooks Sync</h1>
				<ul class="user">
					<?php if(isset($is_authorized) && $is_authorized) { ?><li class="block"><?php echo $user['handle']; ?></li><?php } ?>
					<?php if(isset($merchantos_sess_access)) { ?><li class="logout"><a href="<?php echo $merchantos_sess_access->return_url; if (isset($_GET['disconnected']) || !$ianywhere->isUserAuthorized()) echo "&disconnected=1"; ?>">Return to MerchantOS &rarr; </a></li><?php } ?>
				</ul>
			<?php } ?>
	    </header>

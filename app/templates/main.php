<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="utf-8">
		<title>MudPi Setup - First Time Setup</title>
	    <?php echo csrf_meta(); ?>
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
	    <meta name="description" content="MudPi Initial Setup Page for First time users.">
	    <meta name="author" content="MudPi - Eric Davisson">

		<!-- <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&display=swap" rel="stylesheet">
    	<link href="https://fonts.googleapis.com/css?family=Lato:100,300,600" rel="stylesheet">
    	<link href="https://fonts.googleapis.com/css?family=Raleway:100,300,600" rel="stylesheet" type="text/css">
    	<link href="https://fonts.googleapis.com/css?family=Montserrat:100,200,400,600,700" rel="stylesheet"> -->
		<link rel="stylesheet" type="text/css" href="css/main.css">
		<link rel="stylesheet" type="text/css" href="css/app.css">
	</head>
	<body>
		<div class="container" id="app">
		<div class="columns is-centered">
		<div class="column sm:column-10 block">
			
			<h1 class="h1">MudPi Wifi Setup</h1>
			<table class="table table-responsive table-striped mb-2" id="networks" style="width:100%;text-align:left;">
				<thead>
					<tr>
						<th><?php echo _("SSID"); ?></th>
						<th><?php echo _("RSSI"); ?></th>
						<th><?php echo _("Channel"); ?></th>
						<th><?php echo _("Security"); ?></th>
						<th><?php echo _("Mac Address"); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
			<div class="loader hidden mb-2" id="loader"></div>

			<button id="rescan" class="button is-primary px-2"><?php echo _("Scan"); ?></button>

			<?php echo _("<p><strong>Note:</strong> WEP access points appear as 'Open'. MudPi does not currently support connecting to WEP</p>"); ?>

		</div> <!-- /Container -->
		</div> <!-- /Columns -->
		</div> <!-- /Column 10 -->

		
		<?php include 'modal.php'; ?>

		<script>
			function showPassword(index) {
				var x = document.getElementsByName("passphrase"+index)[0];
				if (x.type === "password") {
					x.type = "text";
				} else {
					x.type = "password";
				}
			}
		</script>
		<script type="text/javascript" src="js/app.js"></script>
	</body>
</html>
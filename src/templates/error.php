<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo $this->e( $title ); ?></title>
	<style>
		body {
			background-color: #eee;
			color: #555;
			font-family: sans-serif;
			padding-top: 100px;
			line-height: 1.5;
		}
		.box {
			background-color: #fff;
			width: 500px;
			margin: 0 auto;
			border: 1px solid #ccc;
			padding: 30px 60px;
		}
	</style>
</head>
<body>
	<div class="box">
		<?php if ( $title ) : ?>
			<h1><?php echo $this->e( $title ); ?></h1>
		<?php endif; ?>
		<p><?php echo $error_msg; ?></p>
	</div>

	<script>
		(function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
		function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
		e=o.createElement(i);r=o.getElementsByTagName(i)[0];
		e.src='https://www.google-analytics.com/analytics.js';
		r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
		ga('create','UA-33538073-25','auto',{'allowLinker':true});
		ga('require','linker');
		ga('linker:autoLink',['proteusthemes.com','themeforest.net']);
		ga('send','pageview');
	</script>
</body>
</html>

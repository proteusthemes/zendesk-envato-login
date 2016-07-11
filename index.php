<?php
use Firebase\JWT\JWT;
use ProteusThemes\ZEL\PermanentStorage;

require_once 'bootstrap.php';

$envato_code = filter_input( INPUT_GET, 'code' );

if ( empty( $envato_code ) ) {
	$_SESSION['zendesk_return_to'] = filter_input( INPUT_GET, 'return_to' );

	header( sprintf( 'Location: https://api.envato.com/authorization?response_type=code&client_id=%s&redirect_uri=%s', $config['envato_client_id'], urlencode( $config['envato_redirect_uri'] ) ) );

	exit;
}
else {
	$EnvatoApi->authorize( $envato_code );

	/**
	 * See https://github.com/zendesk/zendesk_jwt_sso_examples/blob/master/php_jwt.php
	 */

	$key   = $config['zendesk_shared_secret'];
	$now   = time();
	$token = [
		'jti'         => md5( $now . mt_rand() ),
		'iat'         => $now,
		'name'        => $EnvatoApi->get_name(),
		'email'       => $EnvatoApi->get_email(),
		'tags'        => [ 'username_' . $EnvatoApi->get_username() ],
		'user_fields' => [
			'bought_themes'    => $EnvatoApi->get_bought_items_string(),
			'supported_themes' => $EnvatoApi->get_supported_items_string(),
			'tf_username'      => $EnvatoApi->get_username(),
			'country'          => $EnvatoApi->get_country(),
		],
	];

	if ( $EnvatoApi->get_number_of_errors() > 0 ) {
		$logger->addNotice( 'Error screen shown when logging in.', [ 'number_of_errors' => $EnvatoApi->get_number_of_errors() ] );

		die( '
		<html>
		<head>
			<meta charset="utf-8">
			<title>Error on Envato API</title>
		</head>
		<body style="background-color: #eee; color: #777; font-family: sans-serif; padding-top: 100px; line-height: 1.5;">
			<div style="background-color: #fff; width: 500px; margin: 0 auto; border: 1px solid #ccc; padding: 30px 60px;">
				<p>Yikes! Something went wrong with the Envato API, we were unable to sign you up. Till we resolve the issue, you can write directly to our support email (please include the purchase code): <strong>support@proteusthemes.zendesk.com</strong></p>
			</div>
		</body>
		</html>' );
	}

	$jwt = JWT::encode( $token, $key );
	$location = sprintf( 'https://%s.zendesk.com/access/jwt?jwt=%s', $config['zendesk_subdomain'], $jwt );

	if( ! empty( $_SESSION['zendesk_return_to'] ) ) {
		$location .= sprintf( '&return_to=%s', urlencode( $_SESSION['zendesk_return_to'] ) );
	}

	// Permanent storage
	if ( IS_USING_PERMANENT_STORAGE ) {
		$firebase = $firebase = new \Firebase\FirebaseLib( getenv( 'ZEL_FIREBASE_URL' ), getenv( 'ZEL_FIREBASE_TOKEN' ) );

		$permanentStorage = new PermanentStorage( $firebase );
		$permanentStorage->set_logger( $logger );

		$permanentStorage->set( [
			'name'          => $EnvatoApi->get_name(),
			'email'         => $EnvatoApi->get_email(),
			'country'       => $EnvatoApi->get_country(),
			'tf_username'   => $EnvatoApi->get_username(),
			'bought_themes' => $EnvatoApi->get_bought_items(),
		] );
	}

	// Redirect
	if ( 'true' !== getenv( 'ZEL_DEBUG' ) ) {
		header( 'Location: ' . $location );
		exit;
	}
	else {
		print_r( $token );
	}
}

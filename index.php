<?php

// sesstion needed for storing variables between redirects and user authorization
session_start();

require_once 'vendor/autoload.php';
require_once 'src/EnvatoApi.php';

use \Firebase\JWT\JWT;
use \GuzzleHttp\Client;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required( [
	'ENVATO_CLIENT_ID',
	'ENVATO_REDIRECT_URI',
	'ENVATO_CLIENT_SECRET',
	'ZENDESK_SHARED_SECRET',
	'ZENDESK_SUBDOMAIN',
] );

/**
 * Config, from .env
 */
$config = [
	'envato_client_id'      => getenv( 'ENVATO_CLIENT_ID' ),
	'envato_redirect_uri'   => getenv( 'ENVATO_REDIRECT_URI' ),
	'zendesk_shared_secret' => getenv( 'ZENDESK_SHARED_SECRET' ),
	'zendesk_subdomain'     => getenv( 'ZENDESK_SUBDOMAIN' ),
];


$EnvatoApi = new EnvatoApi();

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
		// 'user_fields' => json_encode( ['bought_themes' => json_encode( $EnvatoApi->get_bought_items() ) ] ),
		'user_fields' => [ 'bought_themes' => 'carpress and hairpress' ],
	];

	$jwt = JWT::encode( $token, $key );
	$location = sprintf( 'https://%s.zendesk.com/access/jwt?jwt=%s', $config['zendesk_subdomain'], $jwt );

	if( ! empty( $_SESSION['zendesk_return_to'] ) ) {
		$location .= sprintf( '&return_to=%s', urlencode( $_SESSION['zendesk_return_to'] ) );
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

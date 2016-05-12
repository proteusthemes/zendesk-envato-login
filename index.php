<?php

// sesstion needed for storing variables between redirects and user authorization
session_start();

require_once 'vendor/autoload.php';
require_once 'src/EnvatoApi.php';

use \Firebase\JWT\JWT;
use \Monolog\Logger;
use \Monolog\Handler\SlackHandler;
use \Monolog\Handler\RotatingFileHandler;

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
	'slack_token'           => getenv( 'SLACK_TOKEN' ),
	'slack_channel'         => getenv( 'SLACK_CHANNEL' ),
];

/**
 * Logger
 */
$logger = new Logger( 'general' );
$logger->pushHandler( new RotatingFileHandler( __DIR__ . '/logs/log', 7, Logger::DEBUG ) );

if ( $config['slack_token'] ) {
	$logger->pushHandler( new SlackHandler( $config['slack_token'], $config['slack_channel'], 'Zendesk-Envato Login', true, ':helmet_with_white_cross:', Logger::ERROR ) );
}

/**
 * EnvatoApi instance
 * @var EnvatoApi
 */
$EnvatoApi = new EnvatoApi();
$EnvatoApi->set_logger( $logger );

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
		'tags'        => [ 'username_' . $EnvatoApi->get_username ],
		'user_fields' => [
			'bought_themes'    => $EnvatoApi->get_bought_items_string(),
			'supported_themes' => $EnvatoApi->get_supported_items_string(),
			'tf_username'      => $EnvatoApi->get_username(),
		],
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

<?php

require_once 'vendor/autoload.php';

// sesstion needed for storing variables between redirects and user authorization
session_start();

require_once 'bootstrap/autoload.php';

$dotenv = new Dotenv\Dotenv( __DIR__ );
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

define( 'IS_USING_PERMANENT_STORAGE', ( ! empty( getenv( 'ZEL_FIREBASE_URL' ) ) && ! empty( getenv( 'ZEL_FIREBASE_TOKEN' ) ) ) );

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

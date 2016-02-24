<?php

require 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use \GuzzleHttp\Client;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

/**
 * Config, this will go to .env
 */
$config = [
	'envato_client_id'      => getenv( 'ENVATO_CLIENT_ID' ),
	'envato_redirect_uri'   => getenv( 'ENVATO_REDIRECT_URI' ),
	'envato_client_secret'  => getenv( 'ENVATO_CLIENT_SECRET' ),
	'zendesk_shared_secret' => getenv( 'ZENDESK_SHARED_SECRET' ),
	'zendesk_subdomain'     => getenv( 'ZENDESK_SUBDOMAIN' ),
];


/**
 * Client which will make requests to Envato API
 *
 * http://guzzle.readthedocs.org/en/latest/quickstart.html
 */

$envato_api = new Client( [
	'base_uri' => 'https://api.envato.com/v1/market'
] );

if ( ! isset( $_GET['code'] ) ) {
	header( sprintf( 'Location: https://api.envato.com/authorization?response_type=code&client_id=%s&redirect_uri=%s', $config['envato_client_id'], urlencode( $config['envato_redirect_uri'] ) ) );
}
else {
	$response = $envato_api->post( 'https://api.envato.com/token', [
		'form_params'   => [
			'grant_type'    => 'authorization_code',
			'code'          => $_GET['code'],
			'client_id'     => $config['envato_client_id'],
			'client_secret' => $config['envato_client_secret'],
		],
	] );

	$envato_credentials = json_decode( $response->getBody()->getContents() );

	$user = $envato_api->get( 'https://api.envato.com/v1/market/private/user/account.json', [
		'headers'   => [
			'Authorization' => sprintf( 'Bearer %s', $envato_credentials->access_token ),
		],
	] );

	$user = json_decode( $user->getBody()->getContents() );

	$mail = $envato_api->get( 'https://api.envato.com/v1/market/private/user/email.json', [
		'headers'   => [
			'Authorization' => sprintf( 'Bearer %s', $envato_credentials->access_token ),
		],
	] );

	$mail = json_decode( $mail->getBody()->getContents() );
	$mail = $mail->email;

	echo '<pre>'; var_dump( $user ); echo '</pre>';

	/**
	 * See https://github.com/zendesk/zendesk_jwt_sso_examples/blob/master/php_jwt.php
	 */

	$key       = $config['zendesk_shared_secret'];
	$subdomain = $config['zendesk_subdomain'];
	$now       = time();
	$token = [
		'jti'   => md5( $now . mt_rand() ),
		'iat'   => $now,
		'name'  => sprintf( '%s %s', $user->account->firstname, $user->account->surname ),
		'email' => $mail,
	];

	$jwt = JWT::encode( $token, $key );
	$location = 'https://' . $subdomain . '.zendesk.com/access/jwt?jwt=' . $jwt;

	echo '<pre>'; var_dump( $location ); echo '</pre>';

	if(isset($_GET['return_to'])) {
		$location .= '&return_to=' . urlencode($_GET['return_to']);
	}

	// Redirect
	// header("Location: " . $location);
}
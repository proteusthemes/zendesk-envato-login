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

	$templates = new League\Plates\Engine( 'src/templates' );

	if ( $EnvatoApi->get_number_of_errors() > 0 ) {
		$logger->addNotice( 'Error screen shown: Error on Envato API.', [ 'number_of_errors' => $EnvatoApi->get_number_of_errors() ] );

		die( $templates->render( 'error', [
			'title' => 'Error on Envato API',
			'error_msg' => sprintf( 'Yikes! Something went wrong with the Envato API, we were unable to sign you up. Till we resolve the issue, you can write directly to our support email (please include the purchase code): <strong><a href="mailto:support@proteusthemes.zendesk.com">%1$s</a></strong>.', getenv( 'ZEL_SUPPORT_BACKUP' ) ),
		] ) );
	}
	else if ( 'yes' === getenv( 'ZEL_RESTRICT_SUPPORT' ) && ! $EnvatoApi->user_has_supported_item() ) {
		$logger->addNotice( 'Error screen shown: No supported themes.', [ 'username' => $token['user_fields']['tf_username'] ] );

		die( $templates->render( 'error', [
			'title'       => 'No supported themes found',
			'error_msg'   => sprintf( 'We cannot find any supported themes associated with this Envato account. In order to get support you will have to login with the Envato account you used to purchase the theme or extend the item support. <a href="https://support.proteusthemes.com/hc/en-us/articles/213572845">How can I do that?</a>
						</p><p>
						If you believe this is a mistake, you can still open a support request by writing to <a href="mailto:%1$s">%1$s</a>, but we will not answer if you don\'t provide the valid purchase code.', getenv( 'ZEL_SUPPORT_BACKUP' ) ),
		] ) );
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

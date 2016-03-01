<?php

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\RequestException;

/**
* Wrapper for the Envato API
*/
class EnvatoApi  {
	/**
	 * GuzzleHttp client
	 * @var \GuzzleHttp\Client
	 */
	protected $client;

	/**
	 * Envato API access token
	 * @var string
	 */
	protected $access_token = '';

	/**
	 * Array of cached data
	 * @var array
	 */
	protected $cached_data = [];

	/**
	 * Array of cached data
	 * @var \Monolog\Logger
	 */
	protected $logger;

	public function __construct() {
		$this->client = new Client( [
			'base_uri' => 'https://api.envato.com/',
		] );
	}

	public function set_logger( \Monolog\Logger $logger ) {
		$this->logger = $logger;
	}

	public function is_authorized() {
		return ( ! empty( $this->access_token ) );
	}

	public function authorize( $envato_code ) {

		try {
			$response = $this->client->post( '/token', [
				'form_params'   => [
					'grant_type'    => 'authorization_code',
					'code'          => $envato_code,
					'client_id'     => getenv( 'ENVATO_CLIENT_ID' ),
					'client_secret' => getenv( 'ENVATO_CLIENT_SECRET' ),
				]
			] );
		} catch ( RequestException $e ) {
			$msg = sprintf( 'Error when authorizing: %s', $e->getMessage() );

			if ( $this->logger ) {
				$this->logger->addCritical( $msg, [ $e->getRequest(), $e->getResponse() ] );
			}

			echo $msg;
		}

		$envato_credentials = $this->decode_response( $response );

		$this->set_access_token( $envato_credentials->access_token );

		if ( $this->logger ) {
			$this->logger->addInfo( sprintf( 'New user logged in to Zendesk: %s (%s)', $this->get_name(), $this->get_username() ) );
		}

		if ( 'true' === getenv( 'ZEL_DEBUG' ) ) {
			print_r( $envato_credentials );
		}
	}

	protected function decode_response( $response ) {
		return json_decode( $response->getBody()->getContents() );
	}

	public function set_access_token( $token ) {
		$this->access_token = $token;

		return $this->access_token;
	}

	public function get_access_token() {
		return $this->access_token;
	}

	// check if the data is cached already
	private function is_cached( $hash ) {
		return array_key_exists( $hash, $this->cached_data );
	}

	private function set_cached_data( $hash, $data ) {
		$this->cached_data[ $hash ] = $data;

		return $this->get_cached_data( $hash );
	}

	private function get_cached_data( $hash ) {
		return $this->cached_data[ $hash ];
	}

	// GET http request, with predefined $this->client
	protected function get( $endpoint ) {
		if ( ! $this->is_cached( $endpoint ) ) {
			try {
				$response = $this->client->get( $endpoint, [
					'headers'   => [
						'Authorization' => sprintf( 'Bearer %s', $this->access_token ),
					],
				] );
			} catch ( RequestException $e ) {
				$msg = sprintf( 'Error when doing GET to Envato API: %s', $e->getMessage() );

				if ( $this->logger ) {
					$this->logger->addCritical( $msg, [ $e->getRequest(), $e->getResponse() ] );
				}

				echo $msg;
			}

			$this->set_cached_data( $endpoint, $this->decode_response( $response ) );
		}

		return $this->get_cached_data( $endpoint );
	}

	public function get_email() {
		$response = $this->get( '/v1/market/private/user/email.json' );
		return $response->email;
	}

	public function get_username() {
		$response = $this->get( '/v1/market/private/user/username.json' );
		return $response->username;
	}

	public function get_name() {
		$response = $this->get( '/v1/market/private/user/account.json' );
		return sprintf( '%s %s', $response->account->firstname, $response->account->surname );
	}

	public function get_bought_items() {
		if ( ! $this->is_cached( 'bought_items' ) ) {
			$response = $this->get( '/v3/market/buyer/purchases' );

			$out = [];

			foreach ( $response->purchases as $purchase ) {
				$out[] = [
					'id'              => $purchase->item->id,
					'name'            => $purchase->item->name,
					'short_name'      => current( explode( ' ', $purchase->item->name ) ),
					'supported_until' => $purchase->supported_until,
					'sold_at'         => $purchase->sold_at,
					'code'            => $purchase->code,
				];
			}

			// cache the data
			$this->set_cached_data( 'bought_items', $out );
		}

		return $this->get_cached_data( 'bought_items' );
	}

	public function get_bought_items_string() {
		return array_reduce( $this->get_bought_items(), function ( $out, $item ) {
			$out .= sprintf( '%s (%s)%s', $item['short_name'], date( 'j M Y', strtotime( $item['sold_at'] ) ), "\n" );
			return $out;
		}, '' );
	}

	public function get_supported_items_string() {
		return array_reduce( $this->get_bought_items(), function ( $out, $item ) {
			if ( null !== $item['supported_until'] ) {
				$out .= sprintf( '%s (%s)%s', $item['short_name'], date( 'j M Y', strtotime( $item['supported_until'] ) ), "\n" );
			}
			return $out;
		}, '' );
	}
}

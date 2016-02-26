<?php

use \GuzzleHttp\Client;

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

	public function __construct() {
		$this->client = new Client( [
			'base_uri' => 'https://api.envato.com/v1/market/'
		] );
	}

	public function is_authorized() {
		return ( ! empty( $this->access_token ) );
	}

	public function authorize( $envato_code ) {
		$response = $this->client->post( 'https://api.envato.com/token', [
			'form_params'   => [
				'grant_type'    => 'authorization_code',
				'code'          => $envato_code,
				'client_id'     => getenv( 'ENVATO_CLIENT_ID' ),
				'client_secret' => getenv( 'ENVATO_CLIENT_SECRET' ),
			]
		] );

		$envato_credentials = $this->decode_response( $response );

		$this->set_access_token( $envato_credentials->access_token );

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

	public function get_email() {
		$response = $this->client->get( 'private/user/email.json', [
			'headers'   => [
				'Authorization' => sprintf( 'Bearer %s', $this->access_token ),
			],
		] );
		$response = $this->decode_response( $response );
		return $response->email;
	}

	public function get_username() {
		$response = $this->client->get( 'private/user/username.json', [
			'headers'   => [
				'Authorization' => sprintf( 'Bearer %s', $this->access_token ),
			],
		] );
		$response = $this->decode_response( $response );
		return $response->username;
	}

	public function get_name() {
		$response = $this->client->get( 'private/user/account.json', [
			'headers'   => [
				'Authorization' => sprintf( 'Bearer %s', $this->access_token ),
			],
		] );
		$response = $this->decode_response( $response );
		return sprintf( '%s %s', $response->account->firstname, $response->account->surname );
	}

	public function get_bought_items() {
		$response = $this->client->get( 'https://api.envato.com/v3/market/buyer/purchases', [
			'headers'   => [
				'Authorization' => sprintf( 'Bearer %s', $this->access_token ),
			],
		] );
		$response = $this->decode_response( $response );

		$out = [];

		foreach ( $response->purchases as $key => $purchase ) {
			$out[] = [
				'id'              => $purchase->item->id,
				'name'            => $purchase->item->name,
				'short_name'      => current( explode( ' ', $purchase->item->name ) ),
				'supported_until' => $purchase->supported_until,
				'sold_at'         => $purchase->sold_at,
				'code'            => $purchase->code,
			];
		}

		return $out;
	}

	public function get_bought_items_string() {
		return array_reduce( $this->get_bought_items(), function ( $out, $item ) {
			$out .= $item['short_name'] . "\n";
			return $out;
		}, '' );
	}
}

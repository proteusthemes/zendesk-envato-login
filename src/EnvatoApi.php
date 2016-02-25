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
			'base_uri' => 'https://api.envato.com/v1/market',
			'headers'   => [
				'Authorization' => sprintf( 'Bearer %s', $this->access_token ),
			],
		] );
	}

	public function is_authorized() {
		return ( ! empty( $this->access_token ) );
	}
}

<?php

namespace ProteusThemes\ZEL;

/**
* Wrapper for the Firebase API
*/
class PermanentStorage  {
	/**
	 * Firebase API client instance
	 * @var \Firebase\FirebaseLib
	 */
	protected $client;

	/**
	 * Logger instance
	 * @var \Monolog\Logger
	 */
	protected $logger;

	public function __construct( \Firebase\FirebaseLib $firebase_instance ) {
		$this->client = $firebase_instance;
	}

	public function set_logger() {
		$this->logger = $logger;
	}
}

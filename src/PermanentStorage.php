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

	public function set_logger( \Monolog\Logger $logger ) {
		$this->logger = $logger;
	}

	public function set( array $payload ) {
		try {
			$dateTime = new \DateTime();
			$this->client->set( '/firebase/login_data/' . $dateTime->format( 'c' ), $payload );
		}
		catch ( Exception $e ) {
			$msg = sprintf( 'Error when sending data to Firebase: %s', $e->getMessage() );

			if ( $this->logger ) {
				$this->logger->addCritical( $msg );
			}
		}
	}
}

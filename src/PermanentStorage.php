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

	/**
	 * Path used for the db
	 * @var string
	 */
	const DB_PATH = '/tf_users/';

	public function __construct( \Firebase\FirebaseLib $firebase_instance ) {
		$this->client = $firebase_instance;
	}

	public function set_logger( \Monolog\Logger $logger ) {
		$this->logger = $logger;
	}

	public function set( array $payload ) {
		if ( ! isset( $payload['datetime'] ) ) {
			$dateTime = new \DateTime();
			$payload['last_updated'] = $dateTime->format( 'c' );
		}

		$payload['email'] = filter_var( $payload['email'], FILTER_SANITIZE_EMAIL );

		$path = self::DB_PATH . $payload['tf_username'];

		try {
			$this->client->set( $path, $payload );

			$this->log_login( '/tf_logins/' . $payload['tf_username'], [
				'datetime' => $payload['last_updated'],
			] );
		}
		catch ( Exception $e ) {
			$msg = sprintf( 'Error when sending data to Firebase: %s', $e->getMessage() );

			if ( $this->logger ) {
				$this->logger->addError( $msg );
			}
		}
	}

	private function log_login( $path, $payload ) {
		try {
			$this->client->push( $path, $payload );
		}
		catch ( Exception $e ) {
			$msg = sprintf( 'Error when sending login logs to Firebase: %s', $e->getMessage() );

			if ( $this->logger ) {
				$this->logger->addError( $msg );
			}
		}
	}
}

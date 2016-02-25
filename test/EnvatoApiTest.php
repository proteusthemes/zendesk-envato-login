<?php

require_once "../vendor/autoload.php";
require_once "../src/EnvatoApi.php";

class EnvatoApiTest extends PHPUnit_Framework_TestCase {
	protected $EnvatoApi;

	protected function setUp() {
		$this->EnvatoApi = new EnvatoApi();
	}

	public function testIsAuthorized() {
		$this->assertFalse( $this->EnvatoApi->is_authorized() );
	}
}

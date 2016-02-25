<?php

require_once "../vendor/autoload.php";
require_once "../src/EnvatoApi.php";

class EnvatoApiTest extends PHPUnit_Framework_TestCase {
	public function testIsAuthorized() {
		$EnvatoApi = new EnvatoApi();

		$this->assertFalse( $EnvatoApi->is_authorized() );
	}
}

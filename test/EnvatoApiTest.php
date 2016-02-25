<?php

class EnvatoApiTest extends PHPUnit_Framework_TestCase {
	protected $EnvatoApi;

	protected function setUp() {
		$this->EnvatoApi = new EnvatoApi();
		$this->EnvatoApi->authorize();
	}

	public function testIsAuthorized() {
		$this->assertTrue( $this->EnvatoApi->is_authorized() );
	}

	public function testGetEmail() {
		$this->assertEquals( 'primoz.cigler@gmail.com', $this->EnvatoApi->get_email() );
	}

	public function testGetUsername() {
		$this->assertEquals( 'cyman', $this->EnvatoApi->get_username() );
	}

	public function testGetName() {
		$this->assertEquals( 'Primoz Cigler', $this->EnvatoApi->get_name() );
	}

	public function testArrayOfBoughtThemes() {
		$expected = array();
		$actual = $this->EnvatoApi->get_bought_items();

		$this->assertTrue( is_array( $actual ) );

		return $actual;
	}

	/**
	 * @depends testArrayOfBoughtThemes
	 */
	public function testSingleTheme(array $bought_themes) {
		$actual = array_pop( $bought_themes );

		$expected = [
			'id'              => '4099496',
			'name'            => 'HairPress - WordPress Theme for Hair Salons',
			'supported_until' => null,
			'sold_at'         => '2014-01-20T19:16:32+11:00',
			'code'            => '40cbd94a-9da4-4c42-84d3-aee1f7a65ef3',
		];

		$this->assertEquals( $expected, $actual );
	}
}

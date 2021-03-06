<?php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use ProteusThemes\ZEL\EnvatoApi;

class EnvatoApiTest extends PHPUnit_Framework_TestCase {
	protected $itemsMock;

	public function setUp() {
		date_default_timezone_set( 'UTC' );

		$this->itemsMock = new MockHandler([
			new Response( 200, ['content-type' => 'application/json'], '{
				"purchases": [{
						"sold_at": "2014-02-12T05:33:01+11:00",
						"item": {
							"id": 3803346,
							"name": "Hairpress - HTML Template for Hair Salons"
						},
						"supported_until": null,
						"code": "ggg41153-fff0-ffff-ffff-111ee0686786"
					}, {
						"sold_at": "2015-03-01T05:33:01+11:00",
						"item": {
							"id": 4099496,
							"name": "HairPress - WordPress Theme for Hair Salons"
						},
						"supported_until": "2016-02-27T05:33:01+11:00",
						"code": "ggg41153-fff0-ffff-ffff-000ee0686786"
					}, {
						"sold_at": "2016-03-01T05:45:00+11:00",
						"item": {
							"id": 14458185,
							"name": "Beauty - Hair Salon, Nail, Spa, Fashion WP Theme"
						},
						"supported_until": "2026-02-27T05:33:01+11:00",
						"code": "ggg41153-fff0-ffff-ffff-000ee0686786"
					}, {
						"sold_at": "2015-05-01T22:00:00+11:00",
						"item": {
							"id": 7499064,
							"name": "Readable - Blog Template Focused on Readability"
						},
						"supported_until": null,
						"code": "ggg41155-fff0-ffff-ffff-000ee0686786"
					}, {
						"sold_at": "2016-05-01T12:00:00+11:00",
						"item": {
							"id": 15194530,
							"name": "Auto - Ideal Car Mechanic and Auto Repair Template for WordPress"
						},
						"supported_until": null,
						"code": "gag41155-fff0-ffff-ffff-000ee0686786"
					}]
				}'
			),
		]);
	}

	public function testIsAuthorized() {
		$envatoApi = new EnvatoApi();

		$this->assertFalse( $envatoApi->is_authorized() );

		$envatoApi->set_access_token( 'anything' );

		$this->assertTrue( $envatoApi->is_authorized() );
	}

	public function testGetEmail() {
		$mock = new MockHandler([
			new Response( 200, ['content-type' => 'application/json'], '{"email": "info@example.io"}' ),
		]);

		$handler   = HandlerStack::create( $mock );
		$envatoApi = new EnvatoApi( $handler );

		$this->assertEquals( 'info@example.io', $envatoApi->get_email() );
	}

	public function testGetUsername() {
		$mock = new MockHandler([
			new Response( 200, ['content-type' => 'application/json'], '{"username": "TestUsername"}' ),
		]);

		$handler   = HandlerStack::create( $mock );
		$envatoApi = new EnvatoApi( $handler );

		$this->assertEquals( 'TestUsername', $envatoApi->get_username() );
	}

	public function testGetName() {
		$mock = new MockHandler([
			new Response( 200, ['content-type' => 'application/json'], '{
				"account": {
					"image": "https://0.s3.envato.com/files/83661947/avatar_lite.png",
					"firstname": "Primož",
					"surname": "Cigler",
					"available_earnings": "10.0",
					"total_deposits": "0.00",
					"balance": "15.0",
					"country": "Uganda"
					}
				}'
			),
		]);

		$handler   = HandlerStack::create( $mock );
		$envatoApi = new EnvatoApi( $handler );

		$this->assertEquals( 'Primož Cigler', $envatoApi->get_name() );
		$this->assertEquals( 'Uganda', $envatoApi->get_country() );
	}

	public function testBoughtItemsString() {
		$handler   = HandlerStack::create( $this->itemsMock );
		$envatoApi = new EnvatoApi( $handler );

		$expected = "Hairpress HTML (11 Feb 2014)
HairPress (28 Feb 2015)
Beauty (29 Feb 2016)
Readable HTML (1 May 2015)
Auto (1 May 2016)";
		$actual = $envatoApi->get_bought_items_string();

		$this->assertEquals( $expected, $actual );
	}

	public function testSupportedItemsString() {
		$handler   = HandlerStack::create( $this->itemsMock );
		$envatoApi = new EnvatoApi( $handler );

		$expected = "Beauty (26 Feb 2026)";
		$actual = $envatoApi->get_supported_items_string();

		$this->assertEquals( $expected, $actual );
	}

	public function testErrorsCounter() {
		$mock = new MockHandler([
			new RequestException( 'Error Communicating with Server', new Request('GET', 'test') ),
		]);

		$handler   = HandlerStack::create( $mock );
		$envatoApi = new EnvatoApi( $handler );

		$expected = 1;

		$envatoApi->authorize( 'some code' );
		$actual = $envatoApi->get_number_of_errors();

		$this->assertEquals( $expected, $actual );
	}

	public function testUserHasSupportedItem() {
		$handler   = HandlerStack::create( $this->itemsMock );
		$envatoApi = new EnvatoApi( $handler );

		$actual = $envatoApi->user_has_supported_item();

		$this->assertTrue( $actual );
	}

	public function testUserHasSupportedSingleItem() {
		$handler = HandlerStack::create( new MockHandler([
			new Response( 200, ['content-type' => 'application/json'], '{
				"purchases": [{
						"sold_at": "2014-02-12T05:33:01+11:00",
						"item": {
							"id": 3803346,
							"name": "Hairpress - HTML Template for Hair Salons"
						},
						"supported_until": "2018-02-27T05:33:01+11:00",
						"code": "ggg41153-fff0-ffff-ffff-111ee0686786"
					}]
				}'
			),
		] ) );
		$envatoApi = new EnvatoApi( $handler );

		$actual = $envatoApi->user_has_supported_item();

		$this->assertTrue( $actual );
	}

	public function testUserDoesntHaveSupportedSingleItem() {
		$handler   = HandlerStack::create( new MockHandler([
			new Response( 200, ['content-type' => 'application/json'], '{
				"purchases": [{
						"sold_at": "2014-02-12T05:33:01+11:00",
						"item": {
							"id": 3803346,
							"name": "Hairpress - HTML Template for Hair Salons"
						},
						"supported_until": null,
						"code": "ggg41153-fff0-ffff-ffff-111ee0686786"
					}]
				}'
			),
		] ) );
		$envatoApi = new EnvatoApi( $handler );

		$actual = $envatoApi->user_has_supported_item();

		$this->assertFalse( $actual );
	}

	public function testUserDoesntHaveSupportedMultipleItems() {
		$handler   = HandlerStack::create( new MockHandler([
			new Response( 200, ['content-type' => 'application/json'], '{
				"purchases": [{
						"sold_at": "2014-02-12T05:33:01+11:00",
						"item": {
							"id": 3803346,
							"name": "Hairpress - HTML Template for Hair Salons"
						},
						"supported_until": null,
						"code": "ggg41153-fff0-ffff-ffff-111ee0686786"
					}, {
						"sold_at": "2015-02-12T05:33:01+11:00",
						"item": {
							"id": 3803347,
							"name": "Hairpress - HTML Template for Hair Salons"
						},
						"supported_until": null,
						"code": "egg41153-fff0-ffff-ffff-111ee0686786"
					}]
				}'
			),
		] ) );
		$envatoApi = new EnvatoApi( $handler );

		$actual = $envatoApi->user_has_supported_item();

		$this->assertFalse( $actual );
	}
}

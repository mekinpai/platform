<?php
/*
 *This is a test case for StripeSeed Class. It was developed based on the test case for PaypalSeed with some alterations.
 *
 */
require_once(dirname(__FILE__) . '/base.php');

class StripeSeedTests extends UnitTestCase {
	private $stripe_connection_id, $stripe_client_id,$cash_user_id;
	
	function __construct() {
		echo "Testing Stripe Seed\n";
		
		// add a new admin user for this
		$user_add_request = new CASHRequest(
			array(
				'cash_request_type' => 'system', 
				'cash_action' => 'addlogin',
				'address' => 'email@anothertest.com',
				'password' => 'thiswillneverbeused',
				'is_admin' => 1
			)
		);
		$this->cash_user_id = $user_add_request->response['payload'];
		
		// add a new connection 
		$this->stripe_client_id = getTestEnv("STRIPE_CLIENT_ID");
		if(!$this->stripe_client_id) {
			echo "Stripe credentials not found, skipping tests\n";
		}
		$c = new CASHConnection($this->cash_user_id); // the '1' sets a user id=1
		$this->stripe_connection_id = $c->setSettings('Stripe', 'com.stripe',
			array(
				"client_id" => $this->stripe_client_id, 
				"redirect_uri" => getTestEnv("STRIPE_REDIRECT_URI"), 
				"client_secret" => getTestEnv("STRIPE_CLIENT_SECRET")
			) 
		);
	}

	function testStripeSeed(){
		if($this->stripe_client_id) {
			$pp = new StripeSeed($this->cash_user_id, $this->stripe_connection_id);
			$this->assertIsa($pp, 'StripeSeed');
		}
	}

	function testSet(){
		if($this->stripe_client_id) {
			$pp = new StripeSeed($this->cash_user_id, $this->stripe_connection_id);
			$json_chunk = $pp->setExpressCheckout(
				'13.26',
				'order_sku',
				'this is the best order ever',
				'http://localhost',
				'http://localhost'
			);
			$this->assertTrue($json_chunk);
		}
	}
}

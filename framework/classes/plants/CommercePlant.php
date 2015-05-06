<?php
/**
 * CommercePlant manages products/offers/orders, records transactions, and
 * deals with payment processors
 *
 * @package platform.org.cashmusic
 * @author CASH Music
 * @link http://cashmusic.org/
 *
 * Copyright (c) 2013, CASH Music
 * Licensed under the GNU Lesser General Public License version 3.
 * See http://www.gnu.org/licenses/lgpl-3.0.html
 *
 *
 * This file is generously sponsored by Devin Palmer | www.devinpalmer.com
 *
 **/
class CommercePlant extends PlantBase {
	
	public function __construct($request_type,$request) {
		$this->request_type = 'commerce';
		$this->routing_table = array(
			// alphabetical for ease of reading
			// first value  = target method to call
			// second value = allowed request methods (string or array of strings)
			'additem'             => array('addItem','direct'),
			'addorder'            => array('addOrder','direct'),
			'addtransaction'      => array('addTransaction','direct'),
			'deleteitem'          => array('deleteItem','direct'),
			'edititem'            => array('editItem','direct'),
			'editorder'           => array('editOrder','direct'),
			'edittransaction'     => array('editTransaction','direct'),
			'getanalytics'        => array('getAnalytics','direct'),
			'getitem'             => array('getItem','direct'),
			'getitemsforuser'     => array('getItemsForUser','direct'),
			'getorder'            => array('getOrder','direct'),
			'getordersforuser'    => array('getOrdersForUser','direct'),
			'getordersbycustomer' => array('getOrdersByCustomer','direct'),
			'gettransaction'      => array('getTransaction','direct'),
			'finalizepayment'     => array('finalizeRedirectedPayment',array('get','post','direct')),
			'initiatecheckout'    => array('initiateCheckout',array('get','post','direct','api_public'))
		);
		$this->plantPrep($request_type,$request);
	}
	
	protected function addItem(
		$user_id,
		$name,
		$description='',
		$sku='',
		$price=0,
		$flexible_price=0,
		$available_units=-1,
		$digital_fulfillment=0,
		$physical_fulfillment=0,
		$physical_weight=0,
		$physical_width=0,
		$physical_height=0,
		$physical_depth=0,
		$variable_pricing=0,
		$fulfillment_asset=0,
		$descriptive_asset=0
	   ) {
	   	if (!$fulfillment_asset) {
	   		$digital_fulfillment = false;
	   	}
		$result = $this->db->setData(
			'items',
			array(
				'user_id' => $user_id,
				'name' => $name,
				'description' => $description,
				'sku' => $sku,
				'price' => $price,
				'flexible_price' => $flexible_price,
				'available_units' => $available_units,
				'digital_fulfillment' => $digital_fulfillment,
				'physical_fulfillment' => $physical_fulfillment,
				'physical_weight' => $physical_weight,
				'physical_width' => $physical_width,
				'physical_height' => $physical_height,
				'physical_depth' => $physical_depth,
				'variable_pricing' => $variable_pricing,
				'fulfillment_asset' => $fulfillment_asset,
				'descriptive_asset' => $descriptive_asset
			)
		);
		return $result;
	}
	
	protected function getItem($id,$user_id=false) {
		$condition = array(
			"id" => array(
				"condition" => "=",
				"value" => $id
			)
		);
		if ($user_id) {
			$condition['user_id'] = array(
				"condition" => "=",
				"value" => $user_id
			);
		}
		$result = $this->db->getData(
			'items',
			'*',
			$condition
		);
		if ($result) {
			return $result[0];
		} else {
			return false;
		}
	}
	
	protected function editItem(
		$id,
		$name=false,
		$description=false,
		$sku=false,
		$price=false,
		$flexible_price=false,
		$available_units=false,
		$digital_fulfillment=false,
		$physical_fulfillment=false,
		$physical_weight=false,
		$physical_width=false,
		$physical_height=false,
		$physical_depth=false,
		$variable_pricing=false,
		$fulfillment_asset=false,
		$descriptive_asset=false,
		$user_id=false
	   ) {
	   	if ($fulfillment_asset === 0) {
	   		$digital_fulfillment = 0;
	   	}
	   	if ($fulfillment_asset > 0) {
	   		$digital_fulfillment = 1;
	   	}
		$final_edits = array_filter(
			array(
				'name' => $name,
				'description' => $description,
				'sku' => $sku,
				'price' => $price,
				'flexible_price' => $flexible_price,
				'available_units' => $available_units,
				'digital_fulfillment' => $digital_fulfillment,
				'physical_fulfillment' => $physical_fulfillment,
				'physical_weight' => $physical_weight,
				'physical_width' => $physical_width,
				'physical_height' => $physical_height,
				'physical_depth' => $physical_depth,
				'variable_pricing' => $variable_pricing,
				'fulfillment_asset' => $fulfillment_asset,
				'descriptive_asset' => $descriptive_asset
			),
			'CASHSystem::notExplicitFalse'
		);
		$condition = array(
			"id" => array(
				"condition" => "=",
				"value" => $id
			)
		);
		if ($user_id) {
			$condition['user_id'] = array(
				"condition" => "=",
				"value" => $user_id
			);
		}
		$result = $this->db->setData(
			'items',
			$final_edits,
			$condition
		);
		return $result;
	}
	
	protected function deleteItem($id,$user_id=false) {
		$condition = array(
			"id" => array(
				"condition" => "=",
				"value" => $id
			)
		);
		if ($user_id) {
			$condition['user_id'] = array(
				"condition" => "=",
				"value" => $user_id
			);
		}
		$result = $this->db->deleteData(
			'items',
			$condition
		);
		return $result;
	}

	protected function getItemsForUser($user_id) {
		$result = $this->db->getData(
			'items',
			'*',
			array(
				"user_id" => array(
					"condition" => "=",
					"value" => $user_id
				)
			)
		);
		return $result;
	}

	protected function addOrder(
		$user_id,
		$order_contents,
		$transaction_id=-1,
		$physical=0,
		$digital=0,
		$cash_session_id='',
		$element_id=0,
		$customer_user_id=0,
		$fulfilled=0,
		$canceled=0,
		$notes='',
		$country_code='',
		$currency='USD'
	) {
		if (is_array($order_contents)) {
			/*
				basically we store as JSON to prevent loss of order history
				in the event an item changes or is deleted. we want accurate 
				history so folks don't get all crazy bananas about teh $$s
			*/
			$final_order_contents = json_encode($order_contents);
			$result = $this->db->setData(
				'orders',
				array(
					'user_id' => $user_id,
					'customer_user_id' => $customer_user_id,
					'transaction_id' => $transaction_id,
					'order_contents' => $final_order_contents,
					'fulfilled' => $fulfilled,
					'canceled' => $canceled,
					'physical' => $physical,
					'digital' => $digital,
					'notes' => $notes,
					'country_code' => $country_code,
					'currency' => $currency,
					'element_id' => $element_id,
					'cash_session_id' => $cash_session_id
				)
			);
			return $result;
		} else {
			return false;
		}
	}
	
	protected function getOrder($id,$deep=false,$user_id=false) {
		if ($deep) {
			$result = $this->db->getData(
				'CommercePlant_getOrder_deep',
				false,
				array(
					"id" => array(
						"condition" => "=",
						"value" => $id
					)
				)
			);
			if ($result) {
				if ($user_id) {
					if ($result[0]['user_id'] != $user_id) {
						return false;
					}
				}
				$result[0]['order_totals'] = $this->getOrderTotals($result[0]['order_contents']);
				$user_request = new CASHRequest(
					array(
						'cash_request_type' => 'people', 
						'cash_action' => 'getuser',
						'user_id' => $result[0]['customer_user_id']
					)
				);
				$result[0]['customer_details'] = $user_request->response['payload'];
			}
		} else {
			$condition = array(
				"id" => array(
					"condition" => "=",
					"value" => $id
				)
			);
			if ($user_id) {
				$condition['user_id'] = array(
					"condition" => "=",
					"value" => $user_id
				);
			}
			$result = $this->db->getData(
				'orders',
				'*',
				$condition
			);
		}
		if ($result) {
			return $result[0];
		} else {
			return false;
		}
	}
	
	protected function editOrder(
		$id,
		$fulfilled=false,
		$canceled=false,
		$notes=false,
		$country_code=false,
		$customer_user_id=false,
		$order_contents=false,
		$transaction_id=false,
		$physical=false,
		$digital=false,
		$user_id=false
	) {
		if ($order_contents) {
			$order_contents = json_encode($order_contents);
		}
		$final_edits = array_filter(
			array(
				'transaction_id' => $transaction_id,
				'order_contents' => $order_contents,
				'fulfilled' => $fulfilled,
				'canceled' => $canceled,
				'physical' => $physical,
				'digital' => $digital,
				'notes' => $notes,
				'country_code' => $country_code,
				'customer_user_id' => $customer_user_id
			),
			'CASHSystem::notExplicitFalse'
		);
		$condition = array(
			"id" => array(
				"condition" => "=",
				"value" => $id
			)
		);
		if ($user_id) {
			$condition['user_id'] = array(
				"condition" => "=",
				"value" => $user_id
			);
		}
		$result = $this->db->setData(
			'orders',
			$final_edits,
			$condition
		);
		return $result;
	}

	protected function parseTransactionData($connection_type,$data_sent=false,$data_returned=false) {
		if ($connection_type == 'com.paypal') {
			$data_sent = json_decode($data_sent,true);
			if ($data_sent) {
				$return_array = array(
					'transaction_description' => $data_sent['PAYMENTREQUEST_0_DESC'],
					'customer_email' => $data_sent['EMAIL'],
					'customer_first_name' => $data_sent['FIRSTNAME'],
					'customer_last_name' => $data_sent['LASTNAME']
				);
				
				// this is ugly, but the if statements normalize Paypal's love of omitting empty data

				if (isset($data_sent['PAYMENTREQUEST_0_SHIPTONAME'])) {
					$return_array['customer_shipping_name'] = $data_sent['PAYMENTREQUEST_0_SHIPTONAME'];
				} else {
					$return_array['customer_shipping_name'] = '';
				}
				if (isset($data_sent['PAYMENTREQUEST_0_SHIPTOSTREET'])) {
					$return_array['customer_address1'] = $data_sent['PAYMENTREQUEST_0_SHIPTOSTREET'];
				} else {
					$return_array['customer_address1'] = '';
				}
				if (isset($data_sent['PAYMENTREQUEST_0_SHIPTOSTREET2'])) {
					$return_array['customer_address2'] = $data_sent['PAYMENTREQUEST_0_SHIPTOSTREET2'];
				} else {
					$return_array['customer_address2'] = '';
				}
				if (isset($data_sent['PAYMENTREQUEST_0_SHIPTOCITY'])) {
					$return_array['customer_city'] = $data_sent['PAYMENTREQUEST_0_SHIPTOCITY'];
				} else {
					$return_array['customer_city'] = '';
				}
				if (isset($data_sent['PAYMENTREQUEST_0_SHIPTOSTATE'])) {
					$return_array['customer_region'] = $data_sent['PAYMENTREQUEST_0_SHIPTOSTATE'];
				} else {
					$return_array['customer_region'] = '';
				}
				if (isset($data_sent['PAYMENTREQUEST_0_SHIPTOZIP'])) {
					$return_array['customer_postalcode'] = $data_sent['PAYMENTREQUEST_0_SHIPTOZIP'];
				} else {
					$return_array['customer_postalcode'] = '';
				}
				if (isset($data_sent['SHIPTOCOUNTRYNAME'])) {
					$return_array['customer_country'] = $data_sent['SHIPTOCOUNTRYNAME'];
				} else {
					$return_array['customer_country'] = '';
				}
				if (isset($data_sent['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'])) {
					$return_array['customer_countrycode'] = $data_sent['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'];
				} else {
					$return_array['customer_countrycode'] = '';
				}
				if (isset($data_sent['PAYMENTREQUEST_0_SHIPTOPHONENUM'])) {
					$return_array['customer_phone'] = $data_sent['PAYMENTREQUEST_0_SHIPTOPHONENUM'];
				} else {
					$return_array['customer_phone'] = '';
				}
				return $return_array;
			} else {
				return false;
			}
		}
		return false;
	}

	protected function getOrdersForUser($user_id,$include_abandoned=false,$max_returned=false,$since_date=0,$unfulfilled_only=0,$deep=false,$skip=0) {
		if ($max_returned) {
			$limit = $skip . ', ' . $max_returned;
		} else {
			$limit = false;
		}
		if ($deep) {
			$result = $this->db->getData(
				'CommercePlant_getOrders_deep',
				false,
				array(
					"user_id" => array(
						"condition" => "=",
						"value" => $user_id
					),
					"unfulfilled_only" => array(
						"condition" => "=",
						"value" => $unfulfilled_only
					)
				)
			);
			if ($result) {
				// loop through and parse all transactions
				if (is_array($result)) {
					foreach ($result as &$order) {
						$transaction_data = $this->parseTransactionData($order['connection_type'],$order['data_sent']);
						if (is_array($transaction_data)) {
							$order = array_merge($order,$transaction_data);
						}
					}
				}
			}
		} else {
			$conditions = array(
				"user_id" => array(
					"condition" => "=",
					"value" => $user_id
				),
				"creation_date" => array(
					"condition" => ">",
					"value" => $since_date
				)
			);
			if ($unfulfilled_only) {
				$conditions['fulfilled'] = array(
					"condition" => "=",
					"value" => 0
				);
			}
			if (!$include_abandoned) {
				$conditions['modification_date'] = array(
					"condition" => ">",
					"value" => 0
				);
			}
			$result = $this->db->getData(
				'orders',
				'*',
				$conditions,
				$limit,
				'id DESC'
			);
		}
		return $result;
	}

	protected function getOrdersByCustomer($user_id,$customer_email) {
		$user_request = new CASHRequest(
			array(
				'cash_request_type' => 'people', 
				'cash_action' => 'getuseridforaddress',
				'address' => $customer_email
			)
		);
		$customer_id = $user_request->response['payload'];

		$result = $this->db->getData(
			'orders',
			'*',
			array(
				"user_id" => array(
					"condition" => "=",
					"value" => $user_id
				),
				"customer_user_id" => array(
					"condition" => "=",
					"value" => $customer_id
				),
				"modification_date" => array(
					"condition" => ">",
					"value" => 0
				)
			)
		);
		return $result;
	}

	protected function addTransaction(
		$user_id,
		$connection_id,
		$connection_type,
		$service_timestamp='',
		$service_transaction_id='',
		$data_sent='',
		$data_returned='',
		$successful=-1,
		$gross_price=0,
		$service_fee=0,
		$status='abandoned',
		$currency='USD'
	) {
		$result = $this->db->setData(
			'transactions',
			array(
				'user_id' => $user_id,
				'connection_id' => $connection_id,
				'connection_type' => $connection_type,
				'service_timestamp' => $service_timestamp,
				'service_transaction_id' => $service_transaction_id,
				'data_sent' => $data_sent,
				'data_returned' => $data_returned,
				'successful' => $successful,
				'gross_price' => $gross_price,
				'service_fee' => $service_fee,
				'currency' => $currency,
				'status' => $status
			)
		);
		return $result;
	}
	
	protected function getTransaction($id,$user_id=false) {
		$condition = array(
			"id" => array(
				"condition" => "=",
				"value" => $id
			)
		);
		if ($user_id) {
			$condition['user_id'] = array(
				"condition" => "=",
				"value" => $user_id
			);
		}
		$result = $this->db->getData(
			'transactions',
			'*',
			$condition
		);
		if ($result) {
			return $result[0];
		} else {
			return false;
		}
	}
	
	protected function editTransaction(
		$id,
		$service_timestamp=false,
		$service_transaction_id=false,
		$data_sent=false,
		$data_returned=false,
		$successful=false,
		$gross_price=false,
		$service_fee=false,
		$status=false
	) {
		$final_edits = array_filter(
			array(
				'service_timestamp' => $service_timestamp,
				'service_transaction_id' => $service_transaction_id,
				'data_sent' => $data_sent,
				'data_returned' => $data_returned,
				'successful' => $successful,
				'gross_price' => $gross_price,
				'service_fee' => $service_fee,
				'status' => $status
			),
			'CASHSystem::notExplicitFalse'
		);
		$result = $this->db->setData(
			'transactions',
			$final_edits,
			array(
				'id' => array(
					'condition' => '=',
					'value' => $id
				)
			)
		);
		return $result;
	}
	
	protected function initiateCheckout($user_id,$connection_id,$order_contents=false,$item_id=false,$element_id=false,$total_price=false,$return_url_only=false) {
		 $connection_type= $this->getConnectionType($connection_id);
		if (!$order_contents && !$item_id) {
			return false;
		} else {
			$is_physical = 0;
			$is_digital = 0;
			if (!$order_contents) {
				$order_contents = array();
			}
			if ($item_id) {
				$item_details = $this->getItem($item_id);
				$order_contents[] = $item_details;
				if ($total_price !== false && $total_price >= $item_details['price']) {
					$price_addition = $total_price - $item_details['price'];
				} elseif ($total_price === false) {
					$price_addition = 0;
				} else {
					return false;
				}
				if ($item_details['physical_fulfillment']) {
					$is_physical = 1;
				}
				if ($item_details['digital_fulfillment']) {
					$is_digital = 1;
				}
			}

			$currency = $this->getCurrencyForUser($user_id);
			
			//develop this case for Stripe since the word "abandoned" when user just closes the javascript seems confusing.
			$initial_status = 'abandoned';
			if($connection_type == 'com.stripe'){
				$initial_status = 'canceled';
			}
			
			$transaction_id = $this->addTransaction(
				$user_id,
				$connection_id,
				$this->getConnectionType($connection_id),
				'',
				'',
				'',
				'',
				-1,
				0,
				0,
				$initial_status,
				$currency
			);
			$order_id = $this->addOrder(
				$user_id,
				$order_contents,
				$transaction_id,
				$is_physical,
				$is_digital,
				$this->getSessionID(),
				$element_id,
				0,
				0,
				0,
				'',
				'',
				$currency
			);
			if ($order_id) {
				$success = $this->initiatePaymentRedirect($order_id,$element_id,$price_addition,$return_url_only);
				return $success;
			} else {
				return false;
			}
		}
	}
	
	protected function getOrderTotals($order_contents) {
		$contents = json_decode($order_contents,true);
		$return_array = array(
			'price' => 0,
			'description' => ''
		);
		foreach($contents as $item) {
			$return_array['price'] += $item['price'];
			$return_array['description'] .= $item['name'] . "\n";
		}
		$return_array['description'] = rtrim($return_array['description']);
		return $return_array;
	}

	protected function getCurrencyForUser($user_id) {
		$currency_request = new CASHRequest(
			array(
				'cash_request_type' => 'system', 
				'cash_action' => 'getsettings',
				'type' => 'use_currency',
				'user_id' => $user_id
			)
		);
		if ($currency_request->response['payload']) {
			$currency = $currency_request->response['payload'];
		} else {
			$currency = 'USD';
		}
		return $currency;
	}
	
	protected function initiatePaymentRedirect($order_id,$element_id=false,$price_addition=0,$return_url_only=false) {
		$order_details = $this->getOrder($order_id);
		$transaction_details = $this->getTransaction($order_details['transaction_id']);
		$order_totals = $this->getOrderTotals($order_details['order_contents']);
		$connection_type = $this->getConnectionType($transaction_details['connection_id']);

		$currency = $this->getCurrencyForUser($order_details['user_id']);

		if (($order_totals['price'] + $price_addition) < 0.35) {
			// basically a zero dollar transaction. hard-coding a 35¢ minimum for now
			// we can add a system minimum later, or a per-connection minimum, etc...
			return 'force_success';
		}
		switch ($connection_type) {
			case 'com.paypal':
				$pp = new PaypalSeed($order_details['user_id'],$transaction_details['connection_id']);
				$return_url = CASHSystem::getCurrentURL() . '?cash_request_type=commerce&cash_action=finalizepayment&order_id=' . $order_id . '&creation_date=' . $order_details['creation_date'];
				if ($element_id) {
					$return_url .= '&element_id=' . $element_id;
				}
				$require_shipping = false;
				$allow_note = false;
				if ($order_details['physical']) {
					$require_shipping = true;
					$allow_note = true;
				}
				$redirect_url = $pp->setExpressCheckout(
					$order_totals['price'] + $price_addition,
					'order-' . $order_id,
					$order_totals['description'],
					$return_url,
					$return_url,
					$require_shipping,
					$allow_note,
					$currency 
				);
				if (!$return_url_only) {
					$redirect = CASHSystem::redirectToUrl($redirect_url);
					// the return will only happen if headers have already been sent
					// if they haven't redirectToUrl() will handle it and call exit
					return $redirect;
				} else {
					return $redirect_url;
				}
				break;
			case 'com.stripe':				
				$pp = new StripeSeed($order_details['user_id'],$transaction_details['connection_id']);
				//add price_addition as another parameter to sent to the finalizeRedirectedPayment method.
				$return_url = CASHSystem::getCurrentURL() . '?cash_request_type=commerce&cash_action=finalizepayment&order_id=' . $order_id . '&creation_date=' . $order_details['creation_date'].'&price_addition='.$price_addition;
				
				if ($element_id) {
					$return_url .= '&element_id=' . $element_id;
				}
				$require_shipping = false;
				$allow_note = false;
				if ($order_details['physical']) {
					$require_shipping = true;
					$allow_note = true;
				}
				
				$redirect_url = $pp->setExpressCheckout(
					$order_totals['price'] + $price_addition,
					'order-' . $order_id,
					$order_totals['description'],
					$return_url,
					$return_url,
					$require_shipping,
					$allow_note,
					$currency 
				);
				
		
				if (!$return_url_only) {
					$redirect = CASHSystem::redirectToUrl($redirect_url);
					// the return will only happen if headers have already been sent
					// if they haven't redirectToUrl() will handle it and call exit
					return $redirect;
				} else {
					
					return $redirect_url;
				}
				
				break;
			default:
				return false;
		}
		return false;
	}
	
	protected function finalizeRedirectedPayment($order_id,$creation_date,$direct_post_details=false,$price_addition=0) {
		$order_details = $this->getOrder($order_id);
		$transaction_details = $this->getTransaction($order_details['transaction_id']);
		$order_totals = $this->getOrderTotals($order_details['order_contents']);
		$connection_type = $this->getConnectionType($transaction_details['connection_id']);
		
		$currency = $this->getCurrencyForUser($order_details['user_id']);
		switch ($connection_type) {
			case 'com.paypal':
				if (isset($_GET['token'])) {
					if (isset($_GET['PayerID'])) {
						$pp = new PaypalSeed($order_details['user_id'],$transaction_details['connection_id'],$_GET['token']);
						$initial_details = $pp->getExpressCheckout();
						if ($initial_details['ACK'] == 'Success') {
							$order_totals = $this->getOrderTotals($order_details['order_contents']);
							if ($initial_details['AMT'] >= $order_totals['price']) {
								$final_details = $pp->doExpressCheckout();
								if ($final_details) {
									// look for a user to match the email. if not present, make one
									$user_request = new CASHRequest(
										array(
											'cash_request_type' => 'people', 
											'cash_action' => 'getuseridforaddress',
											'address' => $initial_details['EMAIL']
										)
									);
									$user_id = $user_request->response['payload'];
									if (!$user_id) {
										$user_request = new CASHRequest(
											array(
												'cash_request_type' => 'system', 
												'cash_action' => 'addlogin',
												'address' => $initial_details['EMAIL'], 
												'password' => time(),
												'is_admin' => 0,
												'display_name' => $initial_details['FIRSTNAME'] . ' ' . $initial_details['LASTNAME'],
												'first_name' => $initial_details['FIRSTNAME'],
												'last_name' => $initial_details['LASTNAME'],
												'address_country' => $initial_details['COUNTRYCODE']
											)
										);
										$user_id = $user_request->response['payload'];
									}
									
									// deal with physical quantities
									if ($order_details['physical'] == 1) {
										$order_items = json_decode($order_details['order_contents'],true);
										if (is_array($order_items)) {
											foreach ($order_items as $i) {
												if ($i['available_units'] > 0 && $i['physical_fulfillment'] == 1) {
													$item = $this->getItem($i['id']);
													$available_units =
													$this->editItem(
														$i['id'],
														false,
														false,
														false,
														false,
														false,
														$item['available_units'] - 1
													);
												}
											}
										}
									}

									// record all the details
									if ($order_details['digital'] == 1 && $order_details['physical'] == 0) {
										// if the order is 100% digital just mark it as fulfilled
										$is_fulfilled = 1;
									} else {
										// there's something physical. sorry dude. gotta deal with it still.
										$is_fulfilled = 0;
									}

									$this->editOrder(
										$order_id,
										$is_fulfilled,
										0,
										false,
										$initial_details['COUNTRYCODE'],
										$user_id
									);
			
									$this->editTransaction(
										$order_details['transaction_id'],
										strtotime($final_details['TIMESTAMP']),
										$final_details['CORRELATIONID'],
										json_encode($initial_details),
										json_encode($final_details),
										1,
										$final_details['PAYMENTINFO_0_AMT'],
										$final_details['PAYMENTINFO_0_FEEAMT'],
										'complete'
									);
									
									// TODO: add code to order metadata
									// bit of a hack, hard-wiring the email bits:
									try {
										if ($order_details['digital']) {
											$addcode_request = new CASHRequest(
												array(
													'cash_request_type' => 'element', 
													'cash_action' => 'addlockcode',
													'element_id' => $order_details['element_id']
												)
											);

											CASHSystem::sendEmail(
												'Thank you for your order',
												$order_details['user_id'],
												$initial_details['EMAIL'],
												'Your download of "' . $initial_details['PAYMENTREQUEST_0_DESC'] . '" is ready and can be found at: '
												. CASHSystem::getCurrentURL() . '?cash_request_type=element&cash_action=redeemcode&code=' . $addcode_request->response['payload']
												. '&element_id=' . $order_details['element_id'] . '&email=' . urlencode($initial_details['EMAIL']),
												'Thank you.'
											);
										} else {
											CASHSystem::sendEmail(
												'Thank you for your order',
												$order_details['user_id'],
												$initial_details['EMAIL'],
												'Your order is complete.' . "\n\n" . $initial_details['PAYMENTREQUEST_0_DESC'] . "\n\n" . ' Thank you.',
												'Thank you.'
											);
										}
									} catch (Exception $e) {
										// TODO: handle the case where an email can't be sent. maybe display the download
										//       code on-screen? that plus storing it with the order is probably enough
									}
									return true;
								} else {
									// make sure this isn't an accidentally refreshed page
									if ($initial_details['CHECKOUTSTATUS'] != 'PaymentActionCompleted'){
										$initial_details['ERROR_MESSAGE'] = $pp->getErrorMessage();
										// there was an error processing the transaction
										$this->editOrder(
											$order_id,
											0,
											1
										);
										$this->editTransaction(
											$order_details['transaction_id'],
											strtotime($initial_details['TIMESTAMP']),
											$initial_details['CORRELATIONID'],
											false,
											json_encode($initial_details),
											0,
											false,
											false,
											'error processing payment'
										);
										return false;
									} else {
										// this is a successful transaction with the user hitting refresh
										// as long as it's within 30 minutes of the original return true, otherwise
										// call it false and allow the page to expire
										if (time() - strtotime($initial_details['TIMESTAMP']) < 180) {
											return true;
										} else {
											return false;
										}
									}
								}
							} else {
								// insufficient funds — user changed amount?
								$this->editOrder(
									$order_id,
									0,
									1
								);
								$this->editTransaction(
									$order_details['transaction_id'],
									strtotime($initial_details['TIMESTAMP']),
									$initial_details['CORRELATIONID'],
									false,
									json_encode($initial_details),
									0,
									false,
									false,
									'incorrect amount'
								);
								return false;
							}
						} else {
							// order reporting failure
							$this->editOrder(
								$order_id,
								0,
								1
							);
							$this->editTransaction(
								$order_details['transaction_id'],
								strtotime($initial_details['TIMESTAMP']),
								$initial_details['CORRELATIONID'],
								false,
								json_encode($initial_details),
								0,
								false,
								false,
								'payment failed'
							);
							return false;
						}
					} else {
						// user canceled transaction
						$this->editOrder(
							$order_id,
							0,
							1
						);
						$this->editTransaction(
							$order_details['transaction_id'],
							time(),
							false,
							false,
							false,
							0,
							false,
							false,
							'canceled'
						);
						return false;
					}
				}
				break;
			case 'com.stripe':
					if (isset($_GET['stripe-token'])) {
						$pp = new StripeSeed($order_details['user_id'],$transaction_details['connection_id'],$_GET['stripe-token']);
						$initial_details = $pp->getTokenInformation();
						if ($initial_details) {
							$order_totals = $this->getOrderTotals($order_details['order_contents']);
								$final_details = $pp->doCharge($order_totals['price'] + $price_addition, $currency, $order_totals['description']);
								if ($final_details) {
									// look for a user to match the email. if not present, make one
									$user_request = new CASHRequest(
										array(
											'cash_request_type' => 'people', 
											'cash_action' => 'getuseridforaddress',
											'address' => $initial_details['email']
										)
									);
									$user_id = $user_request->response['payload'];
									if (!$user_id) {
										$user_request = new CASHRequest(
											array(
												'cash_request_type' => 'system', 
												'cash_action' => 'addlogin',
												'address' => $initial_details['email'], 
												'password' => time(),
												'is_admin' => 0,
												'display_name' => $initial_details['card']['name'],
												'first_name' => $initial_details['card']['name'],
												'last_name' => '',
												'address_country' => $initial_details['card']['country']
											)
										);
										$user_id = $user_request->response['payload'];
									}
									
									// deal with physical quantities
									if ($order_details['physical'] == 1) {
										$order_items = json_decode($order_details['order_contents'],true);
										if (is_array($order_items)) {
											foreach ($order_items as $i) {
												if ($i['available_units'] > 0 && $i['physical_fulfillment'] == 1) {
													$item = $this->getItem($i['id']);
													$available_units =
													$this->editItem(
														$i['id'],
														false,
														false,
														false,
														false,
														false,
														$item['available_units'] - 1
													);
												}
											}
										}
									}

									// record all the details
									if ($order_details['digital'] == 1 && $order_details['physical'] == 0) {
										// if the order is 100% digital just mark it as fulfilled
										$is_fulfilled = 1;
									} else {
										// there's something physical. sorry dude. gotta deal with it still.
										$is_fulfilled = 0;
									}
									//after finished charging money. Edit the database.
									$this->editOrder(
										$order_id,
										$is_fulfilled,
										0,
										false,
										$initial_details['card']['country'],
										$user_id
									);
									$this->editTransaction(
										$order_details['transaction_id'],
										$final_details['created'],
										$final_details['id'], // not sure
										$initial_details->__toJSON(),
										$final_details->__toJSON(),
										1,
										$final_details['amount']/100,
										0, //not sure
										$final_details['status']
									);
									// TODO: add code to order metadata
									// bit of a hack, hard-wiring the email bits:
									try {
										if ($order_details['digital']) {
											$addcode_request = new CASHRequest(
												array(
													'cash_request_type' => 'element', 
													'cash_action' => 'addlockcode',
													'element_id' => $order_details['element_id']
												)
											);

											CASHSystem::sendEmail(
												'Thank you for your order',
												$order_details['user_id'],
												$initial_details['email'],
												'Your download of "' . $order_totals['description'] . '" is ready and can be found at: '
												. CASHSystem::getCurrentURL() . '?cash_request_type=element&cash_action=redeemcode&code=' . $addcode_request->response['payload']
												. '&element_id=' . $order_details['element_id'] . '&email=' . urlencode($initial_details['email']),
												'Thank you.'
											);
										} else {
											CASHSystem::sendEmail(
												'Thank you for your order',
												$order_details['user_id'],
												$initial_details['email'],
												'Your order is complete.' . "\n\n" . $order_totals['description'] . "\n\n" . ' Thank you.',
												'Thank you.'
											);
										}
									} catch (Exception $e) {
										// TODO: handle the case where an email can't be sent. maybe display the download
										//       code on-screen? that plus storing it with the order is probably enough
									}
									return true;
								} else {
									// make sure this isn't an accidentally refreshed page
									if ($initial_details['used']){
										$initial_details['ERROR_MESSAGE'] = $pp->getErrorMessage();
										// there was an error processing the transaction
										$this->editOrder(
											$order_id,
											0,
											1
										);
										$this->editTransaction(
											$order_details['transaction_id'],
											$initial_details['created'],
											$initial_details['id'],
											false,
											$initial_details->__toJSON(),
											0,
											false,
											false,
											'error processing payment'
										);
										return false;
									} else {
										// this is a successful transaction with the user hitting refresh
										// as long as it's within 30 minutes of the original return true, otherwise
										// call it false and allow the page to expire
										if (time() - $initial_details['created'] < 180) {
											return true;
										} else {
											return false;
										}
									}
								}

						} else {
							// order reporting failure
							$this->editOrder(
								$order_id,
								0,
								1
							);
							$this->editTransaction(
								$order_details['transaction_id'],
								time(),
								false,
								false,
								false,
								0,
								false,
								false,
								'token information missing'
							);
							return false;
						}
					} else {
						// user canceled transaction
						$this->editOrder(
							$order_id,
							0,
							1
						);
						$this->editTransaction(
							$order_details['transaction_id'],
							time(),
							false,
							false,
							false,
							0,
							false,
							false,
							'canceled'
						);
						return false;
					}
				
				break;
			default:
				return false;
		}
	}

	/**
	 * Pulls analytics queries in a few different formats
	 *
	 * @return array
	 */protected function getAnalytics($analtyics_type,$user_id,$date_low=false,$date_high=false) {
		//
		// left a commented-out switch so we can easily add more cases...
		//
		//switch (strtolower($analtyics_type)) {
		//	case 'transactions':
				if (!$date_low) $date_low = 201243600;
				if (!$date_high) $date_high = time();
				$result = $this->db->getData(
					'CommercePlant_getAnalytics_transactions',
					false,
					array(
						"user_id" => array(
							"condition" => "=",
							"value" => $user_id
						),
						"date_low" => array(
							"condition" => "=",
							"value" => $date_low
						),
						"date_high" => array(
							"condition" => "=",
							"value" => $date_high
						)
					)
				);
				if ($result) {
					return $result[0];
				} else {
					return $result;
				}
		//		break;
		//}
	}
	
} // END class 
?>
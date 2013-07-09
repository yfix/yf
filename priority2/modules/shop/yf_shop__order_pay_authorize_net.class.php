<?php
class yf_shop__order_pay_authorize_net{

	/**
	* Order payment method by authorize.net
	*/
	function _order_pay_authorize_net($params = array()) {
		$order_info = $params["order_info"];
		$params		= $params["params"];

		$PAY_METHOD_ID = 2;
		$PAY_METHOD_PARAMS = module('shop')->_pay_method_params[$PAY_METHOD_ID];

		$amount 		= $order_info["total_sum"];
		$description 	= $params["DESCRIPTION"] ? $params["DESCRIPTION"] : $PAY_METHOD_PARAMS["DESCRIPTION"];

		$login_id		= $PAY_METHOD_PARAMS["LOGIN_ID"];
		$transaction_key= $PAY_METHOD_PARAMS["TRANSACTION_KEY"];
		$TEST_MODE		= $PAY_METHOD_PARAMS["TEST_MODE"] ? "true" : "false";
		$IN_PRODUCTION	= $PAY_METHOD_PARAMS["IN_PRODUCTION"];

		// By default, this sample code is designed to post to our test server for
		// developer accounts: https://test.authorize.net/gateway/transact.dll
		// for real accounts (even in test mode), please make sure that you are
		// posting to: https://secure.authorize.net/gateway/transact.dll

		// Useful for debugging:
		// $url = "https://developer.authorize.net/param_dump.asp";

		if ($IN_PRODUCTION) {
			$url = "https://secure.authorize.net/gateway/transact.dll";
		} else {
			$url = "https://test.authorize.net/gateway/transact.dll";
		}
		// an invoice is generated using the date and time
		$invoice	= date("YmdHis");
		// a sequence number is randomly generated
		$sequence	= rand(1, 1000);
		// a timestamp is generated
		$time_stamp	= time ();

		// The following lines generate the SIM fingerprint.  PHP versions 5.1.2 and
		// newer have the necessary hmac function built in.  For older versions, it
		// will try to use the mhash library.
		if (phpversion() >= '5.1.2') {
			$fingerprint = hash_hmac("md5", $login_id . "^" . $sequence . "^" . $time_stamp . "^" . $amount . "^", $transaction_key);
		} else {
			$fingerprint = bin2hex(mhash(MHASH_MD5, $login_id . "^" . $sequence . "^" . $time_stamp . "^" . $amount . "^", $transaction_key));
		}

		// Required authorise.net fields
		$_fields_and_values = array(
			"x_login"						=> substr($login_id, 0, 20),
			"x_amount"					=> substr($amount, 0, 15),
			"x_description"			=> substr($description, 0, 255),
			"x_invoice_num"		=> substr($invoice, 0, 128),
			"x_fp_sequence"		=> $sequence,
			"x_fp_timestamp"		=> $time_stamp,
			"x_fp_hash"				=> $fingerprint,
			"x_test_request"		=> $TEST_MODE,
			"x_delim_char"	  		=> '|', // The default delimiter is a comma
			"x_delim_data"	  		 => 'TRUE',
			"x_version"		   		 => '3.1',  // 3.1 is required to use CVV codes
			"x_relay_response"	=> "FALSE",

			//"x_show_form"			=> "PAYMENT_FORM",
			//"x_relay_response"	=> "TRUE",
			//"x_relay_url"			=> process_url("./?object=shop&action=payment_callback"),

			//"x_receipt_link_method"	=> "LINK",
			//"x_receipt_link_text"	=> "Return to our online store",
			//"x_receipt_link_URL"	=> process_url("./?object=shop"),
		);
		// Test mode only
		if ($TEST_MODE) {
			$_fields_and_values = my_array_merge($_fields_and_values, array(
				"x_card_num"	=> "370000000000002",
				"x_exp_date"		=> "1220",
			));
		}
		$_order_fields_values = array(
			"x_cust_id"				=> substr($order_info["user_id"], 0, 20),
			"x_customer_ip"			=> substr($_SERVER["REMOTE_ADDR"], 0, 20),
			"x_card_num"			=> substr($order_info["card_num"], 0, 50),
			"x_exp_date"			=> substr($order_info["exp_date"], 0, 4),
			"x_first_name"			=> substr($order_info["b_first_name"], 0, 50),
			"x_last_name"			=> substr($order_info["b_last_name"], 0, 50),
			"x_address"				=> substr($order_info["b_address"], 0, 60),
			"x_city"				=> substr($order_info["b_city"], 0, 40),
			"x_state"				=> substr($order_info["b_state"], 0, 40),
			"x_zip"					=> substr($order_info["b_zip_code"], 0, 20),
			"x_country"				=> substr($order_info["b_country"], 0, 60),
			"x_phone"				=> substr($order_info["b_phone"], 0, 25),
			"x_company"				=> substr($order_info["b_company"], 0, 50),
			"x_email"				=> substr($order_info["b_email"], 0, 255),

			"x_ship_to_first_name"	=> substr($order_info["s_first_name"], 0, 50),
			"x_ship_to_last_name"	=> substr($order_info["s_last_name"], 0, 50),
			"x_ship_to_address"		=> substr($order_info["s_address"], 0, 60),
			"x_ship_to_city"		=> substr($order_info["s_city"], 0, 40),
			"x_ship_to_state"		=> substr($order_info["s_state"], 0, 40),
			"x_ship_to_zip"			=> substr($order_info["s_zip_code"], 0, 20),
			"x_ship_to_country"		=> substr($order_info["s_country"], 0, 60),
			"x_ship_to_company"		=> substr($order_info["s_company"], 0, 50),
		);

		$_fields_and_values = my_array_merge($_fields_and_values, $_order_fields_values);

		$_data_to_post = array();
		foreach ((array)$_fields_and_values as $k => $v) {
			$_data_to_post[$k] = $k.'='. urlencode(str_replace('|', '', $v));
		}
		$_data_to_post = implode("&", $_data_to_post);

		db()->UPDATE(db('shop_orders'), array(
			"status"	=> "pending payment",
		), "id=".intval($order_info['id']));

		// Try to post data
		$ch = curl_init();
		if ($ch) {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_REFERER, process_url("./?object=shop&action=".$_GET["action"]."&id=".$_GET["id"]));
			curl_setopt($ch, CURLOPT_VERBOSE, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $_data_to_post);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response	= curl_exec($ch);
			$error		= curl_error($ch);
			curl_close ($ch);
			if ($error) {
				trigger_error("SHOP: authorize.net response error: ".$error, E_USER_WARNING);
				return _e("SHOP: Payment gateway error #1. Please <a href='".process_url("./?object=support")."'>contact</a> site admin");
			}
		} else {
			return _e("SHOP: Payment gateway error #2. Please <a href='".process_url("./?object=support")."'>contact</a> site admin");
		}
		// Sample good response: 
		// 1,1,1,This transaction has been approved.,Ms6s3z,P,2148412154,20090317055427,Shop Description Here,32.95,CC,auth_capture,14,fixit,fixit,,fixit 78,fixit,fixit,12345,,,,,fixit,fixit,,fixit 78,fixit,fixit,12345,,,,,,,2E9E8E7E6236B4344F9985FDE9E6522E,,2,,,,,,,,,,,,,,,,,,,,,,,,,,,,
		$gateway_response = explode('|', $response);
		// Even though authorize.net is told to return the data delimited with the pipe character,
		// many times it will return data comma-delimited.
		if (count($gateway_response) < 5) {
			$gateway_response = explode(',', $response);
		}
		// If the response code is not 1 (approved) then redirect back to the payment page 
		// with the appropriate error message
		if ($gateway_response[0] != '1') {

			trigger_error("SHOP: authorize.net not approved: ".$response, E_USER_WARNING);
			return _e("SHOP: Payment gateway error #3. Please <a href='".process_url("./?object=support")."'>contact</a> site admin");

		} else {

			db()->UPDATE(db('shop_orders'), array(
				"status"	=> "processed",
			), "id=".intval($order_info['id']));
		}

		// Display order result
		$_GET["id"] = $order_info['id'];
		return module('shop')->_order_step_finish();
	}
	
}
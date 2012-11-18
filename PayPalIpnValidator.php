<?php

/**
* A PayPal IPN Listener based on the code sample directly from PayPal.  Uses CURL to verify the $_POST data.
*
* @author Avais Sethi
*/

class PayPalIpnValidator {
	
	private $_sandbox 		= false;  //testing mode (PayPal Sandbox)  
	private $_callbacks		= array();  //any valid 'payment_status' from PayPal

	/**
	* Construct the object
	*
	* @access public
	* @param bool $sandbox  Are we in testing mode?
	*/
	public function __construct($sandbox = false) {
		$this->_sandbox = (bool)$sandbox;
	}

	/**
	* Set testing mode
	*
	* @access public
	* @param bool $sandbox  Are we in testing mode?
	* @return bool  If NULL, current $sandbox value will be returned
	*/
	public function is_sandbox($sandbox = null) {
		if ( !isset($sandbox) ) return $this->_sandbox;
		$this->_sandbox = (bool)$sandbox;
	}

	/**
	* Set callbacks for the different 'payment_status' values. All callbacks functions must accept 1 parameter,
	* which will be the $_POST array.
	*
	* @access public
	* @param string $status  'Completed', 'Pending', 'Refund', 'Reversal', etc.
	* @param function $function  Anonymous function or function created by 'create_function()'
	*/
	public function set_callback($status, $function) {
		$this->_callbacks[$status] = $function;
	}

	/**
	* Validate the IPN request
	*
	* @access public
	* @param array $params  Associative array of items that need to be validated in the $_POST and their correct
	* (e.g. 'receiver_email', 'mc_gross', etc.)
	* @return bool  TRUE on successful validation, FALSE if request is 'INVALID' or if params do not validate
	*/
	public function validate($params = array()) {
		
		//$params must be an array
		if ( !is_array($params) ) throw new Exception('Value of $params must be an associative array.');

		//verify the request
		$result = $this->_verify();

		//perform appropriate callback if it exists
		if ( isset($this->_callbacks[$result]) ) {
			$cb = $this->_callbacks[$result];
			$cb($_POST);
		}


		//do something based on the response from PayPal
		switch ( $result ) {

			case 'VERIFIED':  //if we get a 'VERIFIED' response...
				
				foreach ( $params as $key => $value ) {  //validate the $params given
					if ( $_POST[$key] != $value ) {

						//perform the MISMATCH callback
						if ( isset($this->_callbacks['MISMATCH']) ) {
							$cb = $this->_callbacks['MISMATCH'];
							$cb($_POST);
							return false;  //return FALSE if something isn't right
						}			
					}  
				}

				foreach ( $this->_callbacks as $key => $value ) {  //run through callbacks
					if ( $_POST['payment_status'] == $key ) { 
						$value($_POST);  //call function if we find one for the given 'payment_status'
						break;
					}
				}

				return true;
			
			break;

			case 'INVALID':  //if we get an 'INVALID' response...
				return false;
			break;

		} 
	}

	/**
	* Get the query string to be sent back to PayPal
	*
	* @access private
	* @return string  The query string
	*/	
	private function _get_post_string() {
		//use 'php://input' to alleviate any urldecoding headaches, as suggested by PayPal
		if ( file_get_contents('php://input') == '' ) throw new Exception('No post data.');
		return 'cmd=_notify-validate&'.file_get_contents('php://input');
	}

	/**
	* Send verification request to PayPal using CURL
	*
	* @access public
	* @return string  Either 'VERIFIED' or 'INVALID
	*/
	private function _verify() {
		
		//initialize CURL, check to see if we're in testing mode
		$ch = curl_init('https://www'.($this->_sandbox ? '.sandbox' : '').'.paypal.com/cgi-bin/webscr');
		
		//a bunch of CURL settings...basically a copy & paste from PayPal's code sample
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_get_post_string());
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
		 
		//throw exception on error
		if( !($result = curl_exec($ch)) ) {
		    curl_close($ch);
		    throw new Exception('cURL error - '.curl_error($ch));
		}

		curl_close($ch);  //close the connection
		return $result;

	}

}
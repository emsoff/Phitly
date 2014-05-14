<?php 
	header('Access-Control-Allow-Origin: *');

	/**
	* Phitly is a modular PHP wrapper for the bit.ly API specifically developed for user in multi-user and multi-app applications.
	*
	* @author     	Justin Emsoff
	* @version    	1.0
  	* @license 		wtfpl http://www.wtfpl.net/txt/copying/
  	* @todo 		Add /bundle endpoints
  	* @see 			http://dev.bitly.com/api.html
  	*
	*/

	class Phitly {

		public $defaults = array(
							'unit' => 'day',
							'units' => '-1',
							'timezone' => '-8',
							'limit' => '100'
						);

		/**
		 * Initializes the class
		 *
		 * @param (user_config) array of the user preferences
		 * @param (app_config) array of app preferences
		*/

		public function __construct($user_config, $app_config) {

			// Assign all user_config to class variables.	
			$this->redirect  	= $user_config['redirect_to'];
			$this->username  	= $user_config['username'];
			$this->access_token = $user_config['access_token'];
 
			// Assign all app_config to class variables.	
			$this->client_id 	= $app_config['client_id'];
			$this->secret 		= $app_config['secret'];

			// If populated with a general access token, $grant will allow requests to be made on behalf of the app. Created bitmarks will belong to the app, in other words.
			$grant		 		= $app_config['access_token'];
		
			// If we don't have an access token...
			if(is_null($this->access_token)) {

				// Check if $grant is set and set it as our access token.
				if(!is_null($grant)) {
					$this->access_token = $grant;

				// If we don't have a user access token or a general access token, let's request a user access token.	
				} else {

					$this->auth();
				}
			}
			
		}

		/**
		 * Arranges for appropriate API call
		 *
		 * @param (method) null, detected from call
		 * @param (params) call parameters
		*/

		public function __call($method = NULL, $params) {

			// Quick reference of available API endpoints, and confirms all requests. 		
			$allowed_methods = array(

				// Domains
						'bitly_pro_domain',
						'user/tracking_domain_clicks',
						'user/tracking_domain_shorten_counts',

				// Data
						'highvalue',
						'search',
						'realtime/bursting_phrases',
						'realtime/hot_phrases',
						'realtime/clickrate',
						'link/info',
						'link/content',
						'link/category',
						'link/social',
						'link/location',
						'link/language',

				// Links
						'expand',
						'info',
						'link/lookup',
						'shorten',
						'user/link_edit',
						'user/link_lookup',
						'user/link_save',
						'user/save_custom_domain_keyword',

				// Link Metrics
						'link/clicks',
						'link/countries',
						'link/encoders',
						'link/encoders_by_count',
						'link/encoders_count',
						'link/referrers',
						'link/referrers_by_domain',
						'link/referring_domains',
						'link/shares',

				// User Info
						'oauth/app',		
						'user/info',		
						'user/link_history',		
						'user/network_history',		
						'user/tracking_domain_list',		

				// User Metrics
						'user/clicks',
						'user/countries',
						'user/popular_earned_by_clicks',
						'user/popular_earned_by_shortens',
						'user/popular_links',
						'user/popular_owned_by_clicks',
						'user/popular_owned_by_shortens',
						'user/referrers',
						'user/referring_domains',
						'user/share_counts',
						'user/share_counts_by_share_type',
						'user/shorten_counts'
				);

			// Convert our method to an actual bit.ly endpoint. 
			$method = str_replace('_', '/', $method);

			$method = strtolower(preg_replace('/(?<!\ )[A-Z]/', '_$0', $method));

	        $endpoint = 'https://api-ssl.bit.ly/v3/' . $method . '?access_token=' . $this->access_token;

	        // If we have some parameters, let's add them to our endpoint.
	        foreach ($params[0] as $key => $value) {
	        	$endpoint .= "&" . $key .  "=" . urlencode($value);
	        }

	        // Confirm it's a valid endpoint and execute the request.
	        if(in_array($method, $allowed_methods)) {
	       		return $this->request($endpoint);

	       	// If it's not a valid endpoint, echo an error and exit. 	
	        } else {
	        	echo "Sorry, that's not an acceptable endpoint for the bitly API.";
				return;
	        }
		}

		/**
		 * Performs API call
		 *
		 * @param (endpoint) null, detected from call
		 * @return (output) received response
		*/

		public function request($endpoint) {
			try {
				$ch = curl_init($endpoint);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_TIMEOUT, 4);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				$output = curl_exec($ch);
			} catch (Exception $e) {
				echo  "error >  " . $e;
				return false;
			}
			return json_decode($output);
		}

		/**
		 * Exchange code for access token
		 *
		 * @param (code) code obtained from cashinIn();
		 * @return (access_token)
		*/

		public function cashinOut($code) {

				$ch = curl_init("https://api-ssl.bit.ly/oauth/access_token");
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, array(
						'client_id' => $this->client_id,
						'client_secret' => $this->secret,
						'code' => $code,
						'redirect_uri' => $this->redirect
					)
				);

				curl_setopt($ch, CURLOPT_TIMEOUT, 2);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				
				$response = explode('&', curl_exec($ch));

				$access_token = explode('=', $response[0]);
				$user_api_key = explode('=', $response[2]);

				$access_token = $access_token[1];
				$user_api_key = $user_api_key[1];

				$_SESSION['bitly_access_token'] = $access_token;
				
				setcookie('bitly_access_token', $access_token, time() + (86400 * 7));

				return $access_token;

		}

		/**
		 * Requests code to be exchanged for access token
		 *
		*/

		public function cashinIn() {

			// We don't have a code, let's request one.
			header('location: https://bitly.com/oauth/authorize?client_id=' . $this->client_id . '&redirect_uri=' . $this->redirect);
		}

		/**
		 * Negotiates setting of access token
		 *
		 * @return (access_token)
		*/

		public function auth() {

			if(!session_id()) {
				session_start();
			}	
			
			// If we don't have an access token...
			if(is_null($this->access_token)) {

				// First let's check cookies...
				if($_COOKIE['bitly_access_token'] != '') {
					$this->access_token = $_COOKIE['bitly_access_token'];
					return;
				}

				// Then let's check sessions...
				if($_SESSION['bitly_access_token'] != '') {
					$this->access_token = $_SESSION['bitly_access_token'];
					return;
				}

				// If we still don't have an access token after checking sessions and cookies, let's see if we have a code to request one...
				$code = ($_GET['code'] ? $_GET['code'] : $this->cashinIn());
				
				$this->access_token = $this->cashinOut($code);

			}

		}

	}

?>
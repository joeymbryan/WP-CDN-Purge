<?php
	
	class LLNW_API
	{
		// Config Variables
		private $shared_key;
		private $shortname;
		private $account_name;
		private $domain;

		// cURL POST/GET Variables
		private $url; 
		private $headers;
		private $post_data;
		private $request_method;
		private $data_string;

		function __construct () {
			$this->shared_key = get_option( 'shared_key' );
			$this->shortname = get_option( 'shortname' );
			$this->account_name = get_option( 'account_name' );
			$this->domain = get_option( 'domain_name' );
		}

		function use_llnw_api ($query_string, $page, $request_method){
			$this->url = "https://purge.llnw.com/purge/v1/account/" . $this->shortname . "/requests" . $query_string;
			$timestamp = $this->time_in_microseconds();

			$this->request_method = $request_method;

			$this->build_data_string($timestamp, $page);

			$this->build_header($timestamp);

			return $this->curl_LLNW();
		}

		function curl_LLNW(){

			// Run Curl
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
			// Setup if Post
			if ($this->request_method == "POST") {
				curl_setopt($ch, CURLOPT_POST, 1);
	   			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->post_data);
		    }

			$server_output = curl_exec ($ch);
			curl_close ($ch);
			if ($this->request_method == "GET") {
				return $this->build_recent_purges_list($server_output);
			} elseif ($this->request_method == "POST") {
				header('Refresh: 0; url=admin.php?page=wp-cdn-purge.php');
			}
		}

		function time_in_microseconds($timestamp) {
			$timestamp = round(microtime(1) * 1000);
			$timestamp = number_format($timestamp, 0, '', '');
			return $timestamp;
		}

		function build_data_string($timestamp, $page){
			// build data_string REQUEST_METHOD + URL + QUERY_STRING (if present) + TIMESTAMP + REQUEST_BODY
			$data_string = $this->request_method . $this->url;
			$data_string .= $timestamp;
			if ($this->request_method == "POST") {

				$this->build_post_data($page);
				$data_string .= $this->post_data;
			}
			$this->data_string = $data_string;
		}

		function build_post_data($page){
			// set exact to false so * is read in regex form and all site is purged
			$exact = true;
			if ($page == '*'){
				$exact = false;
			}
			// Build post data for cURL
			$post_data = array (
			  'patterns' => 
			  array (
			    0 => 
			    array (
			      'pattern' => 'http://' . $this->domain . '/' . $page,
			      'exact' => $exact,
			      'evict' => true,
			      'incqs' => false,
			    ),
			    1 => 
			    array (
			      'pattern' => 'https://' . $this->domain . '/' . $page,
			      'exact' => $exact,
			      'evict' => true,
			      'incqs' => false,
			    ),
			  ),
			);
			$this->post_data = json_encode($post_data);
		}

		function build_header($timestamp) {
			// Build Token
			$token = hash_hmac('sha256', $this->data_string, hex2bin($this->shared_key) );
			// Build HTTP Headers
			$this->headers = [
				'Content-Type: application/json',
				'X-LLNW-Security-Token: ' . $token,
				'X-LLNW-Security-Principal: ' . $this->account_name,
				'X-LLNW-Security-Timestamp: ' . $timestamp
			];
		}

		function build_recent_purges_list($server_output) {
			$server_output = json_decode($server_output, true);
			$purge_list = "<table><tr><th>Page</th><th>Time Purged (PST)</th></tr>";
			// Get All purge history and loop through each request
			foreach($server_output["requests"] as $purge) {
				//time in PST
				date_default_timezone_set('America/Los_Angeles');
				//time of purge
				$time = date('Y-m-d H:i:s',($purge["states"][1]["ts"] / 1000));
				// web URL purged
				$web_url = $purge["patterns"][1]["pattern"];
				$purge_list .= "<tr><td>" . $web_url . "</td><td>" . $time . "</td></td>";
			}
			$purge_list .= "</table>";
			return $purge_list;
		}
	}

?>
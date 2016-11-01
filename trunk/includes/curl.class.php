<?php

/**
 * HTTP request via lib-CURL
 *
 * @since      2.0
 * @package    abovethefold
 * @subpackage abovethefold/includes
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


class Abovethefold_Curl {

	/**
	 * Above the fold controller
	 */
	public $CTRL;

	public $timeout;
	public $ch;
	public $user_agent;
	public $last_url;
	public $result_cache;
	public $cookie,$cookiedir;
	public $transfer_info;

	/**
	 * Initialize the class and set its properties
	 */
	public function __construct( &$CTRL ) {
		$this->CTRL =& $CTRL;
	}

	/**
	 * Return random user agent
	 */
	public function ua() {
		$uas = array();

		// Chrome Generic Win10
		// @link https://techblog.willshouse.com/2012/01/03/most-common-user-agents/
		$uas[] = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.116 Safari/537.36';
		if (count($uas) === 1) {
			return $uas[0];
		}

		// return random
		return $uas[array_rand($uas,1)];
	}

	/**
	 * Get content from HTTP request
	 */
	public function get($url,$referer=false,$post=null,$ua=false,$timeout=false,$use_get=false,$followlocation=1,$httpheaders = false,$encoding = false) {
		if (!$timeout) {
			$timeout = 15;
		}
		if ($ua) {
			$this->user_agent = $ua;
		} else {
			$this->user_agent = $this->ua();
		}
		if (!$timeout) {
			$this->timeout = 15;
		} else {
			$this->timeout = $timeout;
		}
		$this->ch = curl_init();

		// SSL request
		if (preg_match('|^https://|Ui',$url)) {
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // do not verify
		}

		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);

		/**
		 * Follow redirects
		 *
		 * Bugreport by drazon
		 * @link https://wordpress.org/support/topic/php-warning-curl_setopt-open_basedir
		 */
		if ($followlocation && !ini_get('open_basedir')) {
			curl_setopt($this->ch, CURLOPT_MAXREDIRS, $followlocation);
			curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
		}

		/**
		 * Set custom http headers
		 */
		if (is_array($httpheaders)) {
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $httpheaders);
		}

		/**
		 * Set encoding
		 */
		if ($encoding) {
			curl_setopt($this->ch, CURLOPT_ENCODING , $encoding);
		}
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);

		/**
		 * Set referer to bypass referer based 404
		 */
		if ($referer!='') {
			curl_setopt($this->ch, CURLOPT_REFERER, $referer);
		}

		/**
		 * Perform post request
		 */
		if ($post) {
			if ($use_get) {
				/**
				 * Send variables as GET request
				 */
				curl_setopt($this->ch, CURLOPT_GET,1);
				curl_setopt($this->ch, CURLOPT_GETFIELDS,$post);
			} else {
				curl_setopt($this->ch, CURLOPT_POST,1);
				curl_setopt($this->ch, CURLOPT_POSTFIELDS,$post);
			}
		}

		$return_data = curl_exec($this->ch);

		// Log transfer info
		$this->transfer_info = curl_getinfo($this->ch);

		curl_close($this->ch);

		return $return_data;
	}

}
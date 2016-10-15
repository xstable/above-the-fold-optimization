<?php

/**
 * Abovethefold caching external resource proxy.
 *
 * This class provides the functionality for caching external resource proxy functions and hooks.
 *
 * @since      2.5.0
 * @package    abovethefold
 * @subpackage abovethefold/includes
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


class Abovethefold_Proxy {

	/**
	 * Above the fold controller
	 *
	 * @var      object    $CTRL
	 */
	public $CTRL;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 * @var      object    $Optimization       The Optimization class.
	 */
	public function __construct( &$CTRL ) {

		$this->CTRL =& $CTRL;

		if ($this->CTRL->disabled) {
			return; // above the fold optimization disabled for area / page
		}

		if (!isset($this->CTRL->options['js_proxy'])) {
			$this->CTRL->options['js_proxy'] = false;
		}
		if (!isset($this->CTRL->options['css_proxy'])) {
			$this->CTRL->options['css_proxy'] = false;
		}

		if ($this->CTRL->options['css_proxy']) {
		
			// add filter for CSS file processing
			$this->CTRL->loader->add_filter( 'abtf_cssfile_pre', $this, 'process_cssfile' );
		}

		if ($this->CTRL->options['js_proxy']) {
		
			// add filter for javascript file processing
			$this->CTRL->loader->add_filter( 'abtf_jsfile_pre', $this, 'process_jsfile' );
		}

	}

	/**
	 * Get proxy url
	 *
	 * @since  2.5.0 temporary until further improvement
	 */
	public function url($url = '{PROXY:URL}', $type = '{PROXY:TYPE}', $tryCache = false) {

		if ($url !== '{PROXY:URL}') {

			// remove hash from url
			$url = preg_replace('|\#.*$|Ui','',$url);

			// try direct url to file
			if ($tryCache) {
				$cache_url = $this->cache_url(md5($url), $type);
				if ($cache_url) {
					return $cache_url;
				}
			}
		}

		$site_url = site_url();
		$proxy_url = $site_url . ((strpos($site_url,'?') !== false) ? '&' : '?') . 'url='.$url.'&type='.$type.'&abtf-proxy=' . md5(SECURE_AUTH_KEY . AUTH_KEY);
		//$proxy_url = WPABTF_URI . 'proxy.php?url={PROXY:URL}&type={PROXY:TYPE}';
		return $proxy_url;

	}

	/**
	 * Parse CSS file in CSS file loop
	 */
	public function process_cssfile($cssfile) {

		// ignore
		if (!$cssfile || in_array($cssfile,array('delete','ignore'))) {
			return $cssfile;
		}

		$parsed_url = parse_url($cssfile);
		if ($parsed_url['host'] === $_SERVER['HTTP_HOST']) {

			// not external
			return $cssfile;
		}

		// External, proxify url
		return $this->url($cssfile,'css',true);
	}

	/**
	 * Parse javascript file in javascript file loop
	 */
	public function process_jsfile($jsfile) {

		// ignore
		if (!$jsfile || in_array($jsfile,array('delete','ignore'))) {
			return $jsfile;
		}

		$parsed_url = parse_url($jsfile);
		if ($parsed_url['host'] === $_SERVER['HTTP_HOST']) {

			// not external
			return $jsfile;
		}

		// External, proxify url
		return $this->url($jsfile,'js',true);
	}

	/**
	 * Handle forbidden requests
	 */
	public function forbidden() {
		ob_end_clean();
		header('HTTP/1.0 403 Forbidden');
		die('Forbidden');
	}

	/**
	 * Cache file path
	 */
	public function cache_file_path($hash, $type, $create = true) {

		// verify hash
		if (strlen($hash) !== 32) {
			wp_die('Invalid cache file hash');
		}

		// Initialize cache path
		$cache_path = $this->CTRL->cache_path() . 'proxy/';
		if (!is_dir($cache_path)) {
			mkdir($cache_path,0775);
		}

		$dir_blocks = array_slice(str_split($hash, 2), 0, 5);
		foreach ($dir_blocks as $block) {
			$cache_path .= $block . '/';

			if (!$create && !is_dir($cache_path)) {
				return false;
			}
		}

		if (!is_dir($cache_path)) {
			mkdir($cache_path, 0755, true);
		}

		$cache_path .= $hash;

		if ($type === 'js') {
			$cache_path .= '.js';
		} else if ($type === 'css') {
			$cache_path .= '.css';
		}

		if (!$create && !file_exists($cache_path)) {
			return false;
		}

		return $cache_path;
	}

	/**
	 * Cache url
	 */
	public function cache_url($hash, $type) {

		// verify hash
		if (strlen($hash) !== 32) {
			wp_die('Invalid cache file hash');
		}

		$exists = $this->cache_file_path($hash, $type, false);
		if (!$exists) {
			return false;
		}

		$url = $this->CTRL->cache_dir() . 'proxy/';
		
		$dir_blocks = array_slice(str_split($hash, 2), 0, 5);
		foreach ($dir_blocks as $block) {
			$url .= $block . '/';
		}

		$url .= $hash;

		if ($type === 'js') {
			$url .= '.js';
		} else if ($type === 'css') {
			$url .= '.css';
		}

		return $url;
	}

	/**
	 * Handle request
	 */
	public function handle_request() {

		if ((!isset($this->CTRL->options['js_proxy']) || !$this->CTRL->options['js_proxy']) && (!isset($this->CTRL->options['css_proxy']) || !$this->CTRL->options['css_proxy'])) {
			wp_die('Proxy is disabled');
		}

		if (!$this->CTRL->curl_support()) {
			
			/**
			 * cURL or file_get_contents not available
			 */
			trigger_error('PHP <a href="http://php.net/manual/en/book.curl.php" target="_blank">lib cURL</a> should be installed or <a href="http://php.net/manual/en/filesystem.configuration.php" target="_blank">allow_url_fopen</a> should be enabled for external resource proxy.',E_USER_ERROR);
		}

		$url = trim($_REQUEST['url']);
		$type = trim($_REQUEST['type']);

		if (!in_array($type,array('js','css'))) {
			$this->forbidden();
		}

		// invalid protocol
		if (!preg_match('|^http(s)?://|Ui',$url) && preg_match('|^[a-z0-9_-]*://|Ui',$url)) {
			$this->forbidden();
		}

		// proxy resource
		list($filehash, $cache_file) = $this->proxy_resource($url, $type);

		if (!$cache_file) {
			wp_die('Proxy is disabled for file');
		}


		$last_modified = filemtime($cache_file);

		/**
		 * Verify last modified
		 */
	    if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified || 
	        trim($_SERVER['HTTP_IF_NONE_MATCH']) == $filehash) {

    		header("Etag: $filehash");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified) . " GMT");
	        header("HTTP/1.1 304 Not Modified"); 
		    exit; 
		}

		/**
		 * File headers
		 */
		if ($type === 'css') {
			header("Content-Type: text/css", true);
		} else {
			header("Content-Type: application/javascript", true);
		}

		header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified) . " GMT");

		/**
		 * Set gzip compression
		 */
		if (extension_loaded("zlib") && (ini_get("output_handler") != "ob_gzhandler")) {
		    ini_set("zlib.output_compression", 1);
		}


		// prevent sniffing of content type
		header("X-Content-Type-Options: nosniff", true);

		/**
		 * Cache headers
		 */
		// cache age: 30 days
		$cache_age = 2592000;
		header("Pragma: cache");
		header("Cache-Control: max-age=2592000, public");
		header("Expires: " .  gmdate("D, d M Y H:i:s", ($last_modified + $cache_age)) . " GMT");

		readfile($cache_file);

		exit;
	}

	/**
	 * Proxy resource
	 */
	public function proxy_resource($url, $type) {

		if (!in_array($type,array('js','css'))) {
			wp_die('Invalid proxy resource');
		}

		/**
		 * Handle local file
		 */
		$local_file = false;
		if (preg_match('|^http(s)?://|Ui',$url)) {

			$parsed_url = parse_url($url);
			if ($parsed_url['host'] === $_SERVER['HTTP_HOST']) {

				// not external
				$url = str_replace($parsed_url['host'],'',$url);
			}
		}

		if (!preg_match('|^http(s)?://|Ui',$url)) {

			// invalid protocol
			if (preg_match('|^[a-z0-9_-]*://|Ui',$url)) {
				return false;
			}

			// resource not recognized 
			if (!preg_match('#\.(css|js)$#Ui',$url,$out)) {
				return false;
			}

			// get home path
			$path = trailingslashit( ABSPATH );

			if (substr($url,0,1) === '/') {
				$url = substr($url,1);
			}
			$resource_path = realpath($path . $url);

			// make sure resource is in WordPress root
			if (strpos($resource_path, $path) === false || !file_exists($resource_path)) {
				return false;
			}

			$local_file = $resource_path;

			$filehash = md5_file($local_file);

		} else {

			// file hash
			$filehash = md5($url);
		}

		/**
		 * Javascript
		 */
		if ($type === 'js' ) {

			/**
			 * External file? Require proxy to be enabled
			 */
			if (!$local_file && (!isset($this->CTRL->options['js_proxy']) || !$this->CTRL->options['js_proxy'])) {
				return false; // wp_die('Proxy is disabled');
			}

			$include_key = 'js_proxy_include';
			$exclude_key = 'js_proxy_exclude';
		}

		/**
		 * Javascript
		 */
		if ($type === 'css' ) {
			
			/**
			 * External file? Require proxy to be enabled
			 */
			if (!$local_file && (!isset($this->CTRL->options['css_proxy']) || !$this->CTRL->options['css_proxy'])) {
				return false; // wp_die('Proxy is disabled');
			}

			$include_key = 'css_proxy_include';
			$exclude_key = 'css_proxy_exclude';
		}

		/**
		 * Include list, url must match include list
		 */
		if (isset($this->CTRL->options[$include_key]) && trim($this->CTRL->options[$include_key]) !== '') {
			$include = explode("\n",$this->CTRL->options[$include_key]);
		} else { $include = array(); }
		if (!empty($include)) {

			$match = false;
			foreach ($include as $str) {
				if (trim($str) === '') { continue; }
				if (strpos($url,$str) !== false) {
					$match = true;
					break;
				}
			}

			if (!$match) {
				return false; // wp_die('Proxy is disabled for file');
			}
		}

		/**
		 * Exclude list, verify if url should be ignored
		 */
		if (isset($this->CTRL->options[$exclude_key]) && trim($this->CTRL->options[$exclude_key]) !== '') {
			$exclude = explode("\n",$this->CTRL->options[$exclude_key]);
		} else { $exclude = array(); }

		if (!empty($exclude)) {

			$match = false;
			foreach ($exclude as $str) {
				if (trim($str) === '') { continue; }
				if (strpos($url,$str) !== false) {
					$match = true;
					break;
				}
			}

			if ($match) {
				return false; // wp_die('Proxy is disabled for file');
			}
		}

		// cache file
		$cache_file = $this->cache_file_path($filehash, $type);
		
		/**
		 * Download file
		 */
		if (!file_exists($cache_file)) {

			if ($local_file) {
				$file_data = file_get_contents($local_file);
			} else {
				$file_data = $this->CTRL->curl_get($url);
			}

			/**
			 * Apply optimization filters to resource content
			 */
			$file_data = apply_filters('abtf_css', $file_data);

			if ($file_data) {
				file_put_contents($cache_file,$file_data);
			} else {
				wp_die('Failed to proxy file ' . htmlentities($url,ENT_COMPAT,'utf-8'));
			}

		}

		return array($filehash,$cache_file);
	}

}

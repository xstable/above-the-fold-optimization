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
	 * Include list for javascript
	 */
	public $js_include = array();

	/**
	 * Include list for styles (CSS) 
	 */
	public $css_include = array();

	/**
	 * Exclude list for javascript
	 */
	public $js_exclude = array();

	/**
	 * Exclude list for styles (CSS) 
	 */
	public $css_exclude = array(
		'fonts.googleapis.com/css'
	);

	/**
	 * Valid javascript mimetypes
	 */
	public $js_mimetypes = array(
		'application/javascript',
		'application/x-javascript',
		'application/ecmascript',
		'text/javascript',
		'text/ecmascript',
		'text/plain'
	);

	/**
	 * Valid CSS mimetypes
	 */
	public $css_mimetypes = array(
		'text/css',
		'text/plain'
	);

	/**
	 * Absolute path with trailingslash
	 */
	private $abspath;

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

		// set include/exclude list
		$keys = array('js_include','css_include','css_include','css_exclude');
		foreach ($keys as $key) {
			$params = explode('_',$key);

			// merge default include / exclude list with settings
			$this->$key = array_unique(
				array_filter(
					array_merge($this->$key,((isset($this->CTRL->options['js_proxy_' . $params[1]]) && trim($this->CTRL->options['js_proxy_' . $params[1]]) !== '') ? explode("\n",$this->CTRL->options[$key[0] . '_proxy_' . $params[1]]) : array())),
					create_function('$value', 'return trim($value) !== "";')
				));
		}

		// sanitize array (remove empty)

		if ($this->CTRL->options['css_proxy']) {
		
			// add filter for CSS file processing
			$this->CTRL->loader->add_filter( 'abtf_cssfile_pre', $this, 'process_cssfile' );
		}

		if ($this->CTRL->options['js_proxy']) {
		
			// add filter for javascript file processing
			$this->CTRL->loader->add_filter( 'abtf_jsfile_pre', $this, 'process_jsfile' );
		}

		// WordPress root with trailingslash
		$this->abspath = trailingslashit( ABSPATH );

	}

	/**
	 * Get proxy url for an URL
	 *
	 * Returns cache url 
	 */
	public function url($url = '{PROXY:URL}', $type = '{PROXY:TYPE}', $tryCache = false, $htmlUrl = false) {

		if ($url !== '{PROXY:URL}') {
			
			// strip hash from url
			if (strpos($url,'#') !== false) {
				$url = strstr($url, '#', true);
			}

			// parse url
			$parsed = $this->parse_url($url);
			if ($parsed) {
				list($url,$filehash,$local_file) = $parsed;

				// try direct url to file
				if ($tryCache) {
					$cache_url = $this->cache_url($filehash, $type);
					if ($cache_url) {
						return $cache_url;
					}
				}

				$url = urlencode($url);
			}
		}

		// html valid ampersand
		$amp = ($htmlUrl) ? '&amp;' : '&';

		// custom proxy url
		if (isset($this->CTRL->options['proxy_url']) && $this->CTRL->options['proxy_url'] !== '') {

			$proxy_url = $this->CTRL->options['proxy_url'];
			if ($url !== '{PROXY:URL}') {
				$proxy_url = str_replace(array(
					'{PROXY:URL}',
					'{PROXY:TYPE}'
				), array(
					$url,
					$type
				),$proxy_url);
			}
		} else {

			// default WordPress PHP proxy url
			$site_url = site_url();
			$proxy_url = $site_url . ((strpos($site_url,'?') !== false) ? $amp : '?') . 'url=' . $url . $amp . 'type=' . $type . $amp . 'abtf-proxy=' . md5(SECURE_AUTH_KEY . AUTH_KEY); 
		}

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

		/**
		 * File does not match include list, ignore
		 */
		if (!$this->url_include($cssfile, 'css')) {
			return $cssfile;
		}

		/**
		 * File matches exclude list, ignore
		 */
		if ($this->url_exclude($cssfile, 'css')) {
			return $cssfile;
		}

		// External, proxify url
		return $this->url($cssfile,'css',true,true);
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

		/**
		 * File does not match include list, ignore
		 */
		if (!$this->url_include($jsfile, 'js')) {
			return $jsfile;
		}

		/**
		 * File matches exclude list, ignore
		 */
		if ($this->url_exclude($jsfile, 'js')) {
			return $jsfile;
		}

		// External, proxify url
		return $this->url($jsfile,'js',true,true);
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

		$url = (isset($_REQUEST['url'])) ? trim($_REQUEST['url']) : '';
		$type = (isset($_REQUEST['type'])) ? trim($_REQUEST['type']) : '';

		if (!in_array($type,array('js','css'))) {
			$this->forbidden();
		}

		/**
		 * Translate protocol relative url
		 * 
		 * @since  2.5.3
		 */
		if (preg_match('|^//|Ui',$url)) {

			// prefix url with protocol
			$url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https:' : 'http:') . $url;
		}

		// invalid protocol
		if (!preg_match('|^http(s)?://|Ui',$url) && preg_match('|^[a-z0-9_-]*://|Ui',$url)) {
			$this->forbidden();
		}

		// proxy resource
		list($filehash, $cache_file) = $this->proxy_resource($url, $type);

		// Proxy failed for url (potentially insecure, not a valid javascript or CSS resource, url not recognized etc)
		if (!$cache_file) {
			
			// forward request to original location
			header("Location: " . $url);
			exit;
		}

		// get last modified time
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

		// parse url
		$parsed = $this->parse_url($url);
		if (!$parsed) { return; }
		list($url,$filehash,$local_file) = $parsed;

		// verify local file
		if ($local_file) {

			/**
			 * Detect mime type of file
			 */
			$mime = mime_content_type($local_file);

			if (!$mime) {
				// failed
				// @todo test support / stability in all environments
				return false;
			}

			/**
			 * Make sure file has valid mime type
			 */
			if ($type === 'js') {

				// valid javascript mime type?
				if (!in_array($mime,$this->js_mimetypes)) {
					return false;
				}

			} else if ($type === 'css') {

				// valid CSS mime type?
				if (!in_array($mime,$this->css_mimetypes)) {
					return false;
				}
			}

		}

		/**
		 * External file? Require proxy to be enabled
		 */
		if (!$local_file && (!isset($this->CTRL->options[$type . '_proxy']) || !$this->CTRL->options[$type . '_proxy'])) {
			return false;
		}

		/**
		 * File does not match include list, ignore
		 */
		if (!$this->url_include($url, $type)) {
			return false;
		}

		/**
		 * File matches exclude list, ignore
		 */
		if ($this->url_exclude($url, $type)) {
			return false;
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

	/**
	 * Parse url
	 */
	public function parse_url($url) {

		$url = trim($url);

		/**
		 * Translate protocol relative url
		 * 
		 * @since  2.5.3
		 */
		if (substr($url,0,2) === '//') {

			// prefix url with protocol
			$url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https:' : 'http:') . $url;
		}

		/**
		 * Handle local file
		 */
		$local_file = false;

		// http(s):// based file, match host with server host
		if (stripos($url, '://') !== false) {

			$http_prefix = false;

			$prefix_match = substr($url,0,6);
			if ($prefix_match === 'https:') {
				
				// HTTPS
				$http_prefix = 'https://';

			} else if ($prefix_match === 'http:/') {

				// HTTPS
				$http_prefix = 'http://';
			} else {

				/**
				 * Invalid protocol
				 * @security
				 */
				return false;
			}

			if ($http_prefix) {
				$parsed_url = parse_url($url);
				if ($parsed_url['host'] === $_SERVER['HTTP_HOST']) {

					// local file
					$url = str_replace( $http_prefix . $parsed_url['host'], '', $url);
				}
			}
		}

		// local file
		if (stripos($url, '://') === false) {

			// get real path for url
			if (substr($url,0,1) === '/') {
				$url = substr($url,1);
			}
			$resource_path = realpath($this->abspath . $url);

			/**
			 * Make sure resource is in WordPress root
			 * @security
			 */
			if (strpos($resource_path, $this->abspath) === false || !file_exists($resource_path)) {
				return false;
			}

			// create file hash based on file contents (force browser cache update on file changes)
			$filehash = md5_file($resource_path);

			return array($url, $filehash, $resource_path);

		} else {

			// file hash based on url
			$filehash = md5($url);

			return array($url, $filehash);
		}
	}

	/**
	 * Return cache hash for url
	 */
	public function cache_hash($url, $type) {

		$parsed = $this->parse_url($url);
		if ($parsed) {
			if ($this->cache_file_path($parsed[1], $type, false)) {
				return $parsed[1];
			}
		}

		// not in cache
		return false;
	}

	/**
	 * Match url against include list
	 */
	public function url_include($url, $type) {

		/**
		 * Require proxy to be enabled
		 */
		if (!isset($this->CTRL->options[$type . '_proxy']) || !$this->CTRL->options[$type . '_proxy']) {
			return false; // wp_die('Proxy is disabled');
		}

		$include_key = $type . '_include';

		/**
		 * Include list empty, include all
		 */
		if (empty($this->$include_key)) {
			return true;
		}
		
		/**
		 * Match url against include list
		 */
		foreach ($this->$include_key as $str) {
			if (strpos($url,$str) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Match url against exclude list
	 */
	public function url_exclude($url, $type) {

		/**
		 * Require proxy to be enabled
		 */
		if (!isset($this->CTRL->options[$type . '_proxy']) || !$this->CTRL->options[$type . '_proxy']) {
			return false; // wp_die('Proxy is disabled');
		}

		$exclude_key = $type . '_exclude';

		/**
		 * Exclude list empty, exclude none
		 */
		if (empty($this->$exclude_key)) {
			
			return false;

		}

		/**
		 * Match url against exclude list
		 */
		foreach ($this->$exclude_key as $str) {
			if (strpos($url,$str) !== false) {
				return true;
			}
		}

		return false;
	}

}

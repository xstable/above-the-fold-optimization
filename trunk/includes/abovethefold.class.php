<?php

/**
 * Abovethefold optimization core class.
 *
 * This class provides the functionality for admin dashboard and WordPress hooks.
 *
 * @since      1.0
 * @package    abovethefold
 * @subpackage abovethefold/includes
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */
class Abovethefold {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0
	 * @access   public
	 * @var      Abovethefold_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	public $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0
	 * @access   public
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	public $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Disable abovethefold optimization
	 *
	 * @since    1.0
	 * @access   public
	 * @var      bool
	 */
	public $disabled = false;

	/**
	 * Special view (e.g. full css export and critical css quality tester)
	 *
	 * @since    2.5.0
	 * @access   public
	 * @var      array
	 */
	public $view = false;

	/**
	 * Options
	 *
	 * @since    2.0
	 * @access   public
	 * @var      array
	 */

	public $options;

	/**
	 * Sub-controllers
	 */
	public $admin;
	public $optimization;
	public $plugins;
	public $gwfo;

	/**
	 * cURL controller
	 *
	 * @since    2.5.0
	 */
	public $curl;

	/**
	 * Template redirect hook called
	 */
	public $template_redirect_called = false;

	/**
	 * Construct and initiated Abovethefold class.
	 *
	 * @since    1.0
	 */
	public function __construct() {
		global $show_admin_bar;

		// set plugin meta
		$this->plugin_name = 'abovethefold';
		$this->version = WPABTF_VERSION;

		/**
		 * Disable plugin in admin or for testing
		 */
		if (!$this->is_enabled()) {
			$this->disabled = true;
		}

		/**
		 * Register Activate / Deactivate hooks.
		 */
		register_activation_hook( WPABTF_SELF, array( $this, 'activate' ) );
        register_deactivation_hook( WPABTF_SELF, array( $this, 'deactivate' ) );

		/**
		 * Special Views
		 */
		
		// a hash is used to prevent random traffic or abuse
		$view_hash = md5(SECURE_AUTH_KEY . AUTH_KEY);

		// availabble views
		$views = array(

			// extract full CSS view
			'extract-css' => array( 'admin_bar' => false ), 

			// compare critical CSS with full CSS
			'compare-abtf' => array( 'admin_bar' => false ), 

			// view website with just the critical CSS
			'abtf-critical-only' => array( 'admin_bar' => false ), 

			// view website regularly, but without the admin toolbar for comparison view
			'abtf-critical-verify' => array( 'admin_bar' => false ),

			// external resource proxy
			'abtf-proxy' => array( )
		);
		foreach ($views as $viewKey => $viewSettings) {

			// check if view is active
			if (isset($_REQUEST[$viewKey]) && $_REQUEST[$viewKey] === $view_hash) {

				// set view
				$this->view = $viewKey;

				// hide admin bar
				if (isset($viewSettings['admin_bar']) && $viewSettings['admin_bar'] === false) {
					$show_admin_bar = false;
				}
			}
		}


		// load dependencies
		$this->load_dependencies();

		// Load WordPress hook/filter loader
		$this->loader = new Abovethefold_Loader();

		// set language
		$this->set_locale();

		/**
		 * Load options
		 */
		$this->options = get_option( 'abovethefold' );

		// load webfont optimization controller
		$this->gwfo = new Abovethefold_WebFonts($this);

		/**
		 * External resource proxy
		 */
		$this->proxy = new Abovethefold_Proxy($this);

		// do not load rest of plugin for proxy
		if ($this->view === 'abtf-proxy') {
			return;
		}

		/**
		 * Load admin controller
		 */
		$this->admin = new Abovethefold_Admin( $this );

		// plugin module controller
		$this->plugins = new Abovethefold_Plugins( $this );
		$this->plugins->load_modules();

		// load optimization controller
		$this->optimization = new Abovethefold_Optimization( $this );

		// load webfont optimization controller
		$this->gwfo = new Abovethefold_WebFonts($this);

		// load lazy script loading module
		$this->lazy = new Abovethefold_LazyScripts($this);

		/**
		 * Use Above The Fold Optimization standard output buffer
		 */
		$this->loader->add_action('template_redirect', $this, 'template_redirect',-10);
	}

	/**
	 * Check if optimization should be applied to output
	 *
	 * @since  2.5.0
	 */
	public function is_enabled() {

		/**
		 * Disable for Google AMP pages
		 *
		 * @since  2.5.4
		 * @link https://wordpress.org/support/topic/error-to-validate-amp-posts/
		 */
		if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
			return false;
		}

		/**
		 * Disable above the fold optimization
		 */
		if (defined('DONOTABTF') && DONOTABTF) {
			return false;
		}

		/**
         * Skip if admin
         */
        if (defined('WP_ADMIN')) {
            return false;
        }

        /**
         * Skip if doing AJAX
         */
        if (defined('DOING_AJAX')) {
            return false;
        }

        /**
         * Skip if doing cron
         */
        if (defined('DOING_CRON')) {
            return false;
        }

        /**
         * Skip if APP request
         */
        if (defined('APP_REQUEST')) {
            return false;
        }

        /**
         * Skip if XMLRPC request
         */
        if (defined('XMLRPC_REQUEST')) {
            return false;
        }

        /**
         * Check for WPMU's and WP's 3.0 short init
         */
        if (defined('SHORTINIT') && SHORTINIT) {
            return false;
        }

        /**
         * Check if we're displaying feed
         */
        if ($this->template_redirect_called && is_feed()) {
            return false;
        }

        /**
         * Register or login page
         */
        if (in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))) {
        	return false;
        }

        return true;
	}

	/**
	 * Template redirect hook (required for is_feed() enabled check)
	 */
	public function template_redirect() {

		$this->template_redirect_called = true;

		/**
		 * Disable plugin
		 */
		if (!$this->is_enabled()) {
			$this->disabled = true;
		}
	}

	/**
	 * Load the required dependencies.
	 *
	 * @since    1.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once WPABTF_PATH . 'includes/loader.class.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once WPABTF_PATH . 'includes/i18n.class.php';

		/**
		 * The class responsible for defining all actions related to Web Font optimization.
		 */
		require_once WPABTF_PATH . 'includes/webfonts.class.php';

		/**
		 * External resource proxy
		 */
		require_once WPABTF_PATH . 'includes/proxy.class.php';

		// do not load rest of plugin for proxy
		if ($this->view === 'abtf-proxy') {
			return;
		}

		/**
		 * The class responsible for defining all actions related to optimization.
		 */
		require_once WPABTF_PATH . 'includes/optimization.class.php';

		/**
		 * The class responsible for defining all actions related to lazy script loading.
		 */
		require_once WPABTF_PATH . 'includes/lazyscripts.class.php';

		/**
		 * Extract Full CSS view
		 */
		if ($this->view === 'extract-css') {

			/**
			 * The class responsible for defining all actions related to full css extraction
			 */
			require_once WPABTF_PATH . 'includes/extract-full-css.class.php';
		}

		/**
		 * Compare Critical CSS view
		 */
		if ($this->view === 'compare-abtf') {

			/**
			 * The class responsible for defining all actions related to compare critical CSS
			 */
			require_once WPABTF_PATH . 'includes/compare-abtf.class.php';
		}

		/**
		 * The class responsible plugin extension modules.
		 */
		require_once WPABTF_PATH . 'includes/plugins.class.php';
		require_once WPABTF_PATH . 'modules/plugins.class.php';

		/**
		 * The class responsible for defining all actions that occur in the Dashboard.
		 */
		require_once WPABTF_PATH . 'admin/admin.class.php';

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Abovethefold_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Abovethefold_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0
	 */
	public function run() {

		$this->loader->run();

		// output data
		if ($this->view === 'abtf-proxy') {
			$this->proxy->handle_request();
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0
	 * @return    Optimization_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Cache path
	 */
	public function cache_path() {

		$dir = wp_upload_dir();
		$path = trailingslashit($dir['basedir']) . 'abovethefold/';
		if (!is_dir($path)) {
			mkdir( $path, 0755);
		}
		return apply_filters('abovethefold_cache_path', $path);
	}

	/**
	 * Cache URL
	 */
	public function cache_dir() {
		$dir = wp_upload_dir();
		$path = trailingslashit($dir['baseurl']) . 'abovethefold/';
		return apply_filters('abtf_cache_dir', $path);
	}

	/**
	 * Check for cURL / url request support
	 */
	public function curl_support() {
		if (function_exists('curl_version') || ini_get('allow_url_fopen')) {
			return true;
		}
		return false;
	}

	/**
	 * cURL requests with file_get_contents fallback
	 */
	public function curl_get($url) {

		// load cURL
		if (!$this->curl) {
	
			/**
			 * PHP cURL library
			 */
			if (function_exists('curl_version')) {

				// load cURL contorller
				require_once(WPABTF_PATH . 'includes/curl.class.php');
				$this->curl = new Abovethefold_Curl( $this );

			} else if (ini_get('allow_url_fopen')) {

				/**
				 * file_get_contents fallback
				 */
				$this->curl = 'file_get_contents';
			} else {
				
				/**
				 * URL requests not supported
				 */
				$this->CTRL->admin->set_notice('PHP <a href="http://php.net/manual/en/book.curl.php" target="_blank">lib cURL</a> should be installed or <a href="http://php.net/manual/en/filesystem.configuration.php" target="_blank">allow_url_fopen</a> should be enabled.<br /><strong>Request failed:</strong> '.$url, 'ERROR');
				$this->curl = 'disabled';
			}
		}

		// disabled
		if ($this->curl === 'disabled') {
			return false;
		}

		// file_get_contents request
		if ($this->curl === 'file_get_contents') {
			return file_get_contents($url);
		}

		// cURL request
		return $this->curl->get($url);
	}

	/**
	 * Fired during plugin activation.
	 */
	public function activate() {

		/**
		 * Set default options
		 */
		$default_options = array( );

		/**
		 * Critical CSS
		 */
		$default_options['csseditor'] = true;

		/**
		 * CSS Delivery Optimization
		 */
		$default_options['cssdelivery'] = false;
		$default_options['loadcss_enhanced'] = true;
		$default_options['cssdelivery_position'] = 'header';

		/**
		 * Web Font Optimization
		 */
		$default_options['gwfo'] = false;
		$default_options['gwfo_loadmethod'] = 'inline';
		$default_options['gwfo_loadposition'] = 'header';

		/**
		 * Plugin related
		 */
		$default_options['clear_pagecache'] = false;

		/**
		 * Lazy Scripts Loading
		 */
		$default_options['lazyscripts_enabled'] = false;

		/**
		 * Localize javascript
		 */
		$default_options['localizejs_enabled'] = false;

		/**
		 * Other
		 */
		$default_options['debug'] = false;
		$default_options['adminbar'] = true;

		// Store default options
		$options = get_option( 'abovethefold' );
		if ( empty( $options ) ) {
			update_option( "abovethefold", $default_options );
		}

	}

	/**
	 * Fired during plugin deactivation.
	 */
	public function deactivate() {

	}

}

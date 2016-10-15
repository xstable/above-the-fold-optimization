<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @since      2.0
 * @package    abovethefold
 * @subpackage abovethefold/admin
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


class Abovethefold_Admin {

	/**
	 * Above the fold controller
	 *
	 * @since    1.0
	 * @access   public
	 * @var      object    $CTRL
	 */
	public $CTRL;

	/**
	 * Options
	 *
	 * @since    2.0
	 * @access   public
	 * @var      array
	 */
	public $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 * @var      string    $plugin_name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( &$CTRL ) {

		$this->CTRL =& $CTRL;
		$this->options =& $CTRL->options;

		// Upgrade plugin
		$this->CTRL->loader->add_action('plugins_loaded', $this, 'upgrade',10);

		// Configure admin bar menu
		if (!isset($this->CTRL->options['adminbar']) || intval($this->CTRL->options['adminbar']) === 1) {
     	   $this->CTRL->loader->add_action( 'admin_bar_menu', $this, 'admin_bar', 100 );
    	}

		/**
		 * Admin panel specific
		 */
		if (is_admin()) {

			// Hook in the admin options page
			$this->CTRL->loader->add_action('admin_menu', $this, 'admin_menu',30);

			// Hook in the admin styles and scripts
			$this->CTRL->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_scripts',30);


			// Register settings (data storage)
			$this->CTRL->loader->add_action('admin_init', $this, 'register_settings');

			// add settings link to plugin overview
			$this->CTRL->loader->add_filter('plugin_action_links_above-the-fold-optimization/abovethefold.php', $this, 'settings_link' );

			/**
			 * Handle form submissions
			 */
			$this->CTRL->loader->add_action('admin_post_abovethefold_update', $this,  'update_settings');
			$this->CTRL->loader->add_action('admin_post_abovethefold_add_ccss', $this,  'add_ccss');
			$this->CTRL->loader->add_action('admin_post_abovethefold_del_ccss', $this,  'del_ccss');
			$this->CTRL->loader->add_action('admin_post_abovethefold_extract', $this,  'download_fullcss');
			$this->CTRL->loader->add_action('admin_post_abovethefold_javascript', $this,  'update_javascript');

			// Handle admin notices
			$this->CTRL->loader->add_action( 'admin_notices', $this, 'show_notices' );

	        // Update body class
			$this->CTRL->loader->add_filter( 'admin_body_class', $this, 'admin_body_class' );

		}

	}

	/**
	 * Set body class
	 *
	 * @since    1.0
	 * @param $links
	 * @return mixed
	 */
	public function admin_body_class( $classes ) {
	    return "$classes abtf-criticalcss";
	    //return "$classes nav-menus-php";
	}

	/**
	 * Settings link on plugin overview.
	 *
	 * @since    1.0
	 * @param $links
	 * @return mixed
	 */
	public function settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=abovethefold">'.__('Settings').'</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @since	2.3.5
	 * @param 	string	$hook
	 */
	public function enqueue_scripts($hook) {

		if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== 'abovethefold') {
			return;
		}

		if (isset($_REQUEST['clear']) && $_REQUEST['clear'] === 'pagecache') {

			check_admin_referer('abovethefold');

			$this->clear_pagecache();

			wp_redirect(admin_url('admin.php?page=abovethefold'));
			exit;
		}

		$options = get_option('abovethefold');

		wp_enqueue_script( 'abtf_admincp', plugin_dir_url( __FILE__ ) . 'js/admincp.min.js', array( 'jquery' ) );

		wp_enqueue_style( 'abtf_admincp', plugin_dir_url( __FILE__ ) . 'css/admincp.min.css' );

		$tab = (isset($_REQUEST['tab'])) ? trim($_REQUEST['tab']) : 'settings';

		switch($tab) {
			case "settings":

				if (!isset($options['csseditor']) || intval($options['csseditor']) === 1) {

					/**
					 * Codemirror CSS highlighting
					 */
					wp_enqueue_style( 'abtf_codemirror', plugin_dir_url( __FILE__ ) . 'css/codemirror.min.css' );
					wp_enqueue_script( 'abtf_codemirror', plugin_dir_url( __FILE__ ) . 'js/codemirror.min.js', array( 'jquery','jquery-ui-resizable','abtf_admincp' ) );
				}

			break;
			case "extract":
			case "compare":

			break;
			default:

			break;
		}

	}

	/**
	 * Admin menu option.
	 *
	 * @since    1.0
	 */
	public function admin_menu() {
		global $submenu;

		if( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ) {

			/**
			 * Add settings link to Performance tab of W3 Total Cache
			 */
			if (is_array($submenu['w3tc_dashboard']) && !empty($submenu['w3tc_dashboard'])) {
				array_splice( $submenu['w3tc_dashboard'], 2, 0, array(
					array(__('Above The Fold', 'abovethefold'), 'manage_options',  admin_url('admin.php?page=abovethefold'), __('Above The Fold Optimization', 'abovethefold'))
				) );
			}

			add_submenu_page(null, __('Above The Fold', 'abovethefold'), __('Above The Fold Optimization', 'abovethefold'), 'manage_options', 'abovethefold', array(
				&$this,
				'settings_page'
			));

		}

		/**
		 * Add settings link to Settings tab
		 */
		add_submenu_page( 'themes.php',  __('Above The Fold Optimization', 'abovethefold'), __('Above The Fold', 'abovethefold'), 'manage_options', 'abovethefold', array(
			&$this,
			'settings_page'
		));
	}
	
	
	/**
	 * Admin bar option.
	 *
	 * @since    1.0
	 */
	public function admin_bar($admin_bar) {

		$options = get_option('abovethefold');
		if (!empty($options['adminbar']) && intval($options['adminbar']) !== 1) {
			return;
		}

		$settings_url = add_query_arg( array( 'page' => 'abovethefold' ), '/wp-admin/admin.php' );
		$nonced_url = wp_nonce_url( $settings_url, 'abovethefold' );
		$admin_bar->add_menu( array(
			'id' => 'abovethefold',
			'title' => __( 'PageSpeed', 'abovethefold' ),
			'href' => $nonced_url,
			'meta' => array( 'title' => __( 'PageSpeed', 'abovethefold' ), 'class' => 'ab-sub-secondary' )

		) );

		$admin_bar->add_group( array(
			'parent' => 'abovethefold',
	        'id'     => 'abovethefold-tests',
	        'meta'   => array(
	            'class' => 'ab-sub-secondary', // 
	        ),
	    ) );

		if (is_admin()
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			|| in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))
		) {
			$currenturl = site_url();
		} else {
			$currenturl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		/**
		 * Extract Full CSS
		 */
		$extracturl = preg_replace('|\#.*$|Ui','',$currenturl) . ((strpos($currenturl,'?') !== false) ? '&' : '?') . 'extract-css=' . md5(SECURE_AUTH_KEY . AUTH_KEY) . '&output=print';
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-tests',
			'id' => 'abovethefold-extract',
			'title' => __( 'Extract Full CSS', 'abovethefold' ),
			'href' => $extracturl,
			'meta' => array( 'title' => __( 'Extract Full CSS', 'abovethefold' ), 'target' => '_blank' )
		) );

		/**
		 * Compare Critical CSS vs Full CSS
		 */
		$compareurl = preg_replace('|\#.*$|Ui','',$currenturl) . ((strpos($currenturl,'?') !== false) ? '&' : '?') . 'compare-abtf=' . md5(SECURE_AUTH_KEY . AUTH_KEY);
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-tests',
			'id' => 'abovethefold-compare',
			'title' => __( 'Compare Critical CSS', 'abovethefold' ),
			'href' => $compareurl,
			'meta' => array( 'title' => __( 'Compare Critical CSS', 'abovethefold' ), 'target' => '_blank' )
		) );

		/**
		 * Page cache clear 
		 */
		$clear_url = add_query_arg( array( 'page' => 'abovethefold', 'clear' => 'pagecache' ), '/wp-admin/admin.php' );
		$nonced_url = wp_nonce_url( $clear_url, 'abovethefold' );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-tests',
			'id' => 'abovethefold-clear-pagecache',
			'title' => __( 'Clear Page Caches', 'abovethefold' ),
			'href' => $nonced_url,
			'meta' => array( 'title' => __( 'Clear Page Caches', 'abovethefold' ) )
		) );

		/**
		 * Google PageSpeed Score Test
		 */
		$admin_bar->add_node( array(
			'parent' => 'abovethefold',
			'id' => 'abovethefold-check-pagespeed-scores',
			'title' => __( 'Google PageSpeed Scores', 'abovethefold' ),
			'href' => 'https://testmysite.thinkwithgoogle.com/?url='.urlencode($currenturl).'',
			'meta' => array( 'title' => __( 'Google PageSpeed Scores', 'abovethefold' ), 'target' => '_blank' )
		) );

		/**
		 * Test Groups
		 */
		$admin_bar->add_node( array(
			'parent' => 'abovethefold',
			'id' => 'abovethefold-check-google',
			'title' => __( 'Google tests', 'abovethefold' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold',
			'id' => 'abovethefold-check-speed',
			'title' => __( 'Speed tests', 'abovethefold' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold',
			'id' => 'abovethefold-check-technical',
			'title' => __( 'Technical & security tests', 'abovethefold' )
		) );

		/**
		 * Google Tests
		 */
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-google',
			'id' => 'abovethefold-check-pagespeed',
			'title' => __( 'Google PageSpeed Insights', 'abovethefold' ),
			'href' => 'https://developers.google.com/speed/pagespeed/insights/?url='.urlencode($currenturl).'',
			'meta' => array( 'title' => __( 'Google PageSpeed Insights', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-google',
			'id' => 'abovethefold-check-google-mobile',
			'title' => __( 'Google Mobile Test', 'abovethefold' ),
			'href' => 'https://www.google.com/webmasters/tools/mobile-friendly/?url='.urlencode($currenturl).'',
			'meta' => array( 'title' => __( 'Google Mobile Test', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-google',
			'id' => 'abovethefold-check-google-malware',
			'title' => __( 'Google Malware & Security', 'abovethefold' ),
			'href' => 'https://www.google.com/transparencyreport/safebrowsing/diagnostic/index.html#url='.urlencode(str_replace('www.','',parse_url($currenturl, PHP_URL_HOST))),
			'meta' => array( 'title' => __( 'Google Malware & Security', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-google',
			'id' => 'abovethefold-check-google-more',
			'title' => __( 'More tests', 'abovethefold' ),
			'href' => 'https://pagespeed.pro/tests#url='.urlencode($currenturl),
			'meta' => array( 'title' => __( 'More tests', 'abovethefold' ), 'target' => '_blank' )
		) );

		/**
		 * Speed Tests
		 */
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-speed',
			'id' => 'abovethefold-check-webpagetest',
			'title' => __( 'WebPageTest.org', 'abovethefold' ),
			'href' => 'http://www.webpagetest.org/?url='.urlencode($currenturl).'',
			'meta' => array( 'title' => __( 'WebPageTest.org', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-speed',
			'id' => 'abovethefold-check-pingdom',
			'title' => __( 'Pingdom Tools', 'abovethefold' ),
			'href' => 'http://tools.pingdom.com/fpt/?url='.urlencode($currenturl).'',
			'meta' => array( 'title' => __( 'Pingdom Tools', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-speed',
			'id' => 'abovethefold-check-gtmetrix',
			'title' => __( 'GTMetrix', 'abovethefold' ),
			'href' => 'http://gtmetrix.com/?url='.urlencode($currenturl).'',
			'meta' => array( 'title' => __( 'GTMetrix', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-speed',
			'id' => 'abovethefold-check-speed-more',
			'title' => __( 'More tests', 'abovethefold' ),
			'href' => 'https://pagespeed.pro/tests#url='.urlencode($currenturl),
			'meta' => array( 'title' => __( 'More tests', 'abovethefold' ), 'target' => '_blank' )
		) );

		/**
		 * Technical & Security Tests
		 */
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-technical',
			'id' => 'abovethefold-check-securityheaders',
			'title' => __( 'SecurityHeaders.io', 'abovethefold' ),
			'href' => 'https://securityheaders.io/?q='.urlencode($currenturl).'&followRedirects=on',
			'meta' => array( 'title' => __( 'SecurityHeaders.io', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-technical',
			'id' => 'abovethefold-check-w3c',
			'title' => __( 'W3C HTML Validator', 'abovethefold' ),
			'href' => 'https://validator.w3.org/nu/?doc='.urlencode($currenturl).'',
			'meta' => array( 'title' => __( 'W3C HTML Validator', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-technical',
			'id' => 'abovethefold-check-ssllabs',
			'title' => __( 'SSL Labs', 'abovethefold' ),
			'href' => 'https://www.ssllabs.com/ssltest/analyze.html?d='.urlencode($currenturl).'',
			'meta' => array( 'title' => __( 'SSL Labs', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-technical',
			'id' => 'abovethefold-check-intodns',
			'title' => __( 'Into DNS', 'abovethefold' ),
			'href' => 'http://www.intodns.com/'.urlencode(str_replace('www.','',parse_url($currenturl, PHP_URL_HOST))).'',
			'meta' => array( 'title' => __( 'Into DNS', 'abovethefold' ), 'target' => '_blank' )
		) );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-check-technical',
			'id' => 'abovethefold-check-technical-more',
			'title' => __( 'More tests', 'abovethefold' ),
			'href' => 'https://pagespeed.pro/tests#url='.urlencode($currenturl),
			'meta' => array( 'title' => __( 'More tests', 'abovethefold' ), 'target' => '_blank' )
		) );

		$admin_bar->add_node( array(
			'parent' => 'abovethefold',
			'id' => 'abovethefold-more-tests',
			'title' => __( 'More website tests', 'abovethefold' ),
			'href' => 'https://pagespeed.pro/tests#url='.urlencode($currenturl),
			'meta' => array( 'title' => __( 'More website tests', 'abovethefold' ), 'target' => '_blank' )
		) );
	}

	public function register_settings() {

		// Register settings (data-storage)
		register_setting('abovethefold_group', 'abovethefold'); // Above the fold options

	}

	/**
	 * Clear page cache with notice
	 */
	public function clear_pagecache() {

		$this->CTRL->plugins->clear_pagecache();

		$this->set_notice('Page related caches from <a href="https://github.com/optimalisatie/above-the-fold-optimization/tree/master/trunk/modules/plugins/" target="_blank">supported plugins</a> cleared.<p><strong>Note:</strong> This plugin does not contain a page cache. The page cache clear function for multiple other plugins is a tool.', 'NOTICE');
	}

    /**
	 * Update settings
	 */
	public function update_settings() {

		check_admin_referer('abovethefold');
		$error = false;

		// stripslashes should always be called
		// @link https://codex.wordpress.org/Function_Reference/stripslashes_deep
		$_POST = array_map( 'stripslashes_deep', $_POST );

		/**
		 * Clear Page Caches
		 */
		if (isset($_POST['clear_pagecache'])) {

			$this->clear_pagecache();
			
			wp_redirect(admin_url('admin.php?page=abovethefold'));
			exit;
		}

		$options = get_option('abovethefold');
		if (!is_array($options)) {
			$options = array();
		}

		$input = $_POST['abovethefold'];
		if (!is_array($input)) {
			$input = array();
		}

		/**
		 * Critical CSS settings
		 */
		$options['csseditor'] = (isset($input['csseditor']) && intval($input['csseditor']) === 1) ? true : false;
		$options['conditionalcss_enabled'] = (isset($input['conditionalcss_enabled']) && intval($input['conditionalcss_enabled']) === 1) ? true : false;
		
		/**
		 * Optimize CSS delivery
		 */
		$options['cssdelivery'] = (isset($input['cssdelivery']) && intval($input['cssdelivery']) === 1) ? true : false;
		$options['loadcss_enhanced'] = (isset($input['loadcss_enhanced']) && intval($input['loadcss_enhanced']) === 1) ? true : false;
		$options['cssdelivery_position'] = trim($input['cssdelivery_position']);
		$options['cssdelivery_ignore'] = trim(sanitize_text_field($input['cssdelivery_ignore']));
		$options['cssdelivery_remove'] = trim(sanitize_text_field($input['cssdelivery_remove']));
		$options['cssdelivery_renderdelay'] = (isset($input['cssdelivery_renderdelay']) && is_numeric($input['cssdelivery_renderdelay']) && intval($input['cssdelivery_renderdelay']) > 0) ? intval($input['cssdelivery_renderdelay']) : false;
		$options['css_proxy'] = (isset($input['css_proxy']) && intval($input['css_proxy']) === 1) ? true : false;
		$options['css_proxy_include'] = trim(sanitize_text_field($input['css_proxy_include']));
		$options['css_proxy_exclude'] = trim(sanitize_text_field($input['css_proxy_exclude']));

		/**
		 * Web Font Optimization
		 */
		$options['gwfo'] = (isset($input['gwfo']) && intval($input['gwfo']) === 1) ? true : false;
		$options['gwfo_loadmethod'] = trim($input['gwfo_loadmethod']);
		$options['gwfo_loadposition'] = trim($input['gwfo_loadposition']);
		$options['gwfo_config'] = trim($input['gwfo_config']);

		// verify WebFontConfig
		if ($options['gwfo_config'] !== '') {
			if (substr($options['gwfo_config'], -1) === '}') {
				$options['gwfo_config'] .= ';';
			}
			if (!preg_match('|^WebFontConfig\s*=\s*\{.*;$|s',$options['gwfo_config'])) {
				$error = true;
				$this->set_notice('WebFontConfig variable is not valid. It should consist of <code>WebFontConfig = { ... };</code>.', 'ERROR');
			}
		}

		$options['gwfo_googlefonts'] = trim($input['gwfo_googlefonts']);

		// parse google fonts
		if ($options['gwfo_googlefonts'] !== '') {
			$fonts = array();
			$rows = explode("\n",$options['gwfo_googlefonts']);
			foreach ($rows as $row) {
				if (trim($row) === '') { continue 1; }
				$fonts[] = trim($row);
			}
			$options['gwfo_googlefonts'] = implode("\n",$fonts);
		}
		
		$options['gwfo_googlefonts_auto'] = (isset($input['gwfo_googlefonts_auto']) && intval($input['gwfo_googlefonts_auto']) === 1) ? true : false;


		$options['localizejs_enabled'] = (isset($input['localizejs_enabled']) && intval($input['localizejs_enabled']) === 1) ? true : false;
		

		/**
		 * Debug / admin options
		 */
		$options['debug'] = (isset($input['debug']) && intval($input['debug']) === 1) ? true : false;
		$options['clear_pagecache'] = (isset($input['clear_pagecache']) && intval($input['clear_pagecache']) === 1) ? true : false;
		$options['adminbar'] = (isset($input['adminbar']) && intval($input['adminbar']) === 1) ? true : false;
	
		/**
		 * Store global critical CSS
		 */
		$css = trim($input['css']);
		$cssfile = $this->CTRL->cache_path() . 'criticalcss_global.css';
		file_put_contents( $cssfile, $css );

		/**
		 * Store conditional critical CSS
		 */
		if (!empty($input['conditional_css'])) {
			foreach ($input['conditional_css'] as $hashkey => $data) {
				if (!isset($options['conditional_css'][$hashkey])) {
					$error = true;
					$this->set_notice('Conditional Critical CSS not configured.', 'ERROR');
				} else if (empty($data['conditions'])) {
					$error = true;
					$this->set_notice('You did not select conditions for <strong>'.htmlentities($options['conditional_css'][$hashkey]['name'],ENT_COMPAT,'utf-8').'</strong>.', 'ERROR');
				} else {
					$options['conditional_css'][$hashkey]['conditions'] = $data['conditions'];

					$css = trim(stripslashes($data['css']));
					$cssfile = $this->CTRL->cache_path() . 'criticalcss_'.$hashkey.'.css';
					file_put_contents( $cssfile, $css );
				}
			}
		}

		// store update count
		if (!isset($options['update_count'])) {
			$options['update_count'] = 0;
		}
		$options['update_count']++;

		/**
		 * Verify cURL support
		 */
		if (!$this->CTRL->curl_support()) {
			$curl_required = array();

			// localize javascript
			if ($options['localizejs_enabled']) {
				$curl_required[] = 'Localize Javascript';
				$options['localizejs_enabled'] = false;
			}

			// proxy
			if ($options['css_proxy'] || $options['js_proxy']) {
				$curl_required[] = 'External Resource Proxy';
				$options['css_proxy'] = $options['js_proxy'] = false;
			}

			if (!empty($curl_required)) {

				$last = array_pop($curl_required);
				$curl_required = count($curl_required) ? implode("</strong>, <strong>", $curl_required) . "</strong> and <strong>" . $last : $last;
				$this->set_notice('PHP <a href="http://php.net/manual/en/book.curl.php" target="_blank">lib cURL</a> should be installed or <a href="http://php.net/manual/en/filesystem.configuration.php" target="_blank">allow_url_fopen</a> should be enabled for <strong>'.$curl_required.'</strong>.<br /><span style="font-weight:bold;color:red;">The selected options have been disabled.</span>', 'ERROR');
			}
			
		}

		update_option('abovethefold',$options);

		/**
		 * Clear full page cache
		 */
		if ($options['clear_pagecache']) {
			$this->clear_pagecache();
		}

		if ($error) {
			wp_redirect(admin_url('admin.php?page=abovethefold'));
			exit;
		}

		wp_redirect(admin_url('admin.php?page=abovethefold'));
		exit;
    }


    /**
	 * Update javascript settings
	 */
	public function update_javascript() {

		check_admin_referer('abovethefold');
		$error = false;

		// stripslashes should always be called
		// @link https://codex.wordpress.org/Function_Reference/stripslashes_deep
		$_POST = array_map( 'stripslashes_deep', $_POST );

		$options = get_option('abovethefold');
		if (!is_array($options)) {
			$options = array();
		}

		$input = $_POST['abovethefold'];
		if (!is_array($input)) {
			$input = array();
		}

		/**
		 * Optimize Javascript delivery
		 */
		//$options['cssdelivery'] = (isset($input['cssdelivery']) && intval($input['cssdelivery']) === 1) ? true : false;

		$options['js_proxy'] = (isset($input['js_proxy']) && intval($input['js_proxy']) === 1) ? true : false;
		$options['js_proxy_include'] = trim(sanitize_text_field($input['js_proxy_include']));
		$options['js_proxy_exclude'] = trim(sanitize_text_field($input['js_proxy_exclude']));

		// Lazy Load Scripts
		$options['lazyscripts_enabled'] = (isset($input['lazyscripts_enabled']) && intval($input['lazyscripts_enabled']) === 1) ? true : false;

		// store update count
		if (!isset($options['update_count'])) {
			$options['update_count'] = 0;
		}
		$options['update_count']++;

		/**
		 * Verify cURL support
		 */
		if (!$this->CTRL->curl_support()) {
			$curl_required = array();

			// proxy
			if ($options['css_proxy'] || $options['js_proxy']) {
				$curl_required[] = 'External Resource Proxy';
				$options['css_proxy'] = $options['js_proxy'] = false;
			}

			if (!empty($curl_required)) {

				$last = array_pop($curl_required);
				$curl_required = count($curl_required) ? implode("</strong>, <strong>", $curl_required) . "</strong> and <strong>" . $last : $last;
				$this->set_notice('PHP <a href="http://php.net/manual/en/book.curl.php" target="_blank">lib cURL</a> should be installed or <a href="http://php.net/manual/en/filesystem.configuration.php" target="_blank">allow_url_fopen</a> should be enabled for <strong>'.$curl_required.'</strong>.<br /><span style="font-weight:bold;color:red;">The selected options have been disabled.</span>', 'ERROR');
			}
			
		}

		update_option('abovethefold',$options);

		/**
		 * Clear full page cache
		 */
		if ($options['clear_pagecache']) {
			$this->clear_pagecache();
		}

		if ($error) {
			wp_redirect(admin_url('admin.php?page=abovethefold&tab=javascript'));
			exit;
		}

		wp_redirect(admin_url('admin.php?page=abovethefold&tab=javascript'));
		exit;
    }

    /**
	 * Add conditional critical CSS
	 */
	public function add_ccss() {
		check_admin_referer('abovethefold');

		if ( get_magic_quotes_gpc() ) {
			$_POST = array_map( 'stripslashes_deep', $_POST );
			$_GET = array_map( 'stripslashes_deep', $_GET );
			$_COOKIE = array_map( 'stripslashes_deep', $_COOKIE );
			$_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );
		}

		$options = get_option('abovethefold');
		if (!is_array($options)) {
			$options = array();
		}

		if (!isset($options['conditional_css'])) {
			$options['conditional_css'] = array();
		}

		$form_error = new WP_Error;

		$name = trim(stripslashes($_POST['name']));

		if ($name === '') {
			$this->set_notice('You did not enter a name.', 'ERROR');
			wp_redirect(admin_url('admin.php?page=abovethefold') );
			exit;
		}

		$id = md5($name);
		if (isset($options['conditional_css'][$id])) {
			$this->set_notice('A conditional critical CSS configuration with the name <strong>'.htmlentities($name,ENT_COMPAT,'utf-8').'</strong> already exists.', 'ERROR');
			wp_redirect(admin_url('admin.php?page=abovethefold') );
			exit;
		}

		$_conditions = (isset($_POST['conditions']) && !empty($_POST['conditions'])) ? $_POST['conditions'] : array();

		$conditions = array();
		foreach ($_conditions as $condition) {
			if (trim($condition) === '') { continue 1; }
			$conditions[] = trim($condition);
		}

		if (empty($conditions)) {
			$this->set_notice('You did not select conditions.', 'ERROR');
			wp_redirect(admin_url('admin.php?page=abovethefold') );
			exit;
		}

		$options['conditional_css'][$id] = array(
			'name' => $name,
			'conditions' => $conditions,
			'css' => ''
		);

		update_option('abovethefold',$options);

		$this->set_notice('Conditional Critical CSS created.', 'NOTICE');

		wp_redirect(admin_url('admin.php?page=abovethefold') . '#conditional' );
		exit;
    }

    /**
	 * Delete conditional critical CSS
	 */
	public function del_ccss() {

		check_admin_referer('abovethefold');

		if ( get_magic_quotes_gpc() ) {
			$_POST = array_map( 'stripslashes_deep', $_POST );
			$_GET = array_map( 'stripslashes_deep', $_GET );
			$_COOKIE = array_map( 'stripslashes_deep', $_COOKIE );
			$_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );
		}

		$options = get_option('abovethefold');
		if (!is_array($options)) {
			$options = array();
		}

		if (!isset($options['conditional_css'])) {
			$options['conditional_css'] = array();
		}

		$form_error = new WP_Error;

		$id = trim(stripslashes($_POST['id']));

		// verify hash
		if (!preg_match('|^[a-z0-9]{32}|Ui',$id)) {
			wp_die('Invalid conditional critical CSS ID.');
		}

		/**
		 * Delete critical CSS entry
		 */
		if (isset($options['conditional_css'][$id])) {
			unset($options['conditional_css'][$id]);
		}

		/**
		 * Delete critical CSS file
		 */
		$cssfile = $this->CTRL->cache_path() . 'criticalcss_'.$id.'.css';
		if (file_exists($cssfile)) {

			// empty it if deletion fails
			file_put_contents( $cssfile, '' );
			
			// delete file
			@unlink( $cssfile );
		}

		update_option('abovethefold',$options);

		$this->set_notice('Conditional Critical CSS deleted.', 'NOTICE');

		wp_redirect(admin_url('admin.php?page=abovethefold') . '#conditional' );
		exit;
    }

    /**
	 * Download Full CSS
	 */
    public function download_fullcss() {

    	$options = get_option('abovethefold');
		if (!is_array($options)) {
			$options = array();
		}

		if ( get_magic_quotes_gpc() ) {
			$_POST = array_map( 'stripslashes_deep', $_POST );
			$_GET = array_map( 'stripslashes_deep', $_GET );
			$_COOKIE = array_map( 'stripslashes_deep', $_COOKIE );
			$_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );
		}

		$input = $_POST['abovethefold'];
		if (!is_array($input)) {
			$input = array();
		}

		$urls = array();
		$_urls = explode("\n",$input['genurls']);
		foreach ($_urls as $url) {
			if (trim($url) === '') { continue 1; }

			$url = str_replace(get_option('siteurl'),'',$url);

			if (preg_match('|^http(s)?:|Ui',$url)) {
				add_settings_error(
					'abovethefold',                     // Setting title
					'urls_texterror',            // Error ID
					'Invalid URL: ' . $url,     // Error message
					'error'                         // Type of message
				);
				$error = true;
			} else {
				if (!preg_match('|^/|Ui',$url)) {
					$url = '/' . $url;
				}
				$urls[] = $url;
			}
		}
		if (empty($urls)) {
			add_settings_error(
				'abovethefold',                     // Setting title
				'urls_texterror',            // Error ID
				'You did not enter any paths.',     // Error message
				'error'                         // Type of message
			);
			$error = true;
		} else {
			$options['genurls'] = implode("\n",$urls);
		}

		update_option('abovethefold',$options);

		$this->options = $options;

		if ($error) {
			return;
		}

		/**
		 * Generate Crtical CSS
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/penthouse.class.php';

		$this->generator = new Abovethefold_Generator_Penthouse( $this );

		$fullCSS = $this->generator->extract_fullcss();

		ob_end_clean();

		header('Content-disposition: attachment; filename=full-css-'.date('c').'.css');
        header('Content-type: text/plain');
        header('Content-length: '.strlen($fullCSS).'');

        die($fullCSS);
    }

	public function settings_tabs( $current = 'homepage' ) {
        $tabs = array(
        	'settings' => 'Critical CSS &amp; Settings',
			'compare' => 'Quality Test',
			'extract' => 'Extract Full CSS',
			'javascript' => 'Javascript Optimization'
        );
        echo '<div id="icon-themes" class="icon32"><br></div>';
        echo '<h1 class="nav-tab-wrapper">';
        foreach( $tabs as $tab => $name ){
            $class = ( $tab == $current ) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=abovethefold&tab=$tab'>$name</a>";

        }
        echo '</h1>';
    }

	public function settings_page() {
		global $pagenow, $wp_query;

		$options = get_option('abovethefold');
		if (!is_array($options)) {
			$options = array();
		}

		/**
		 * Load default paths
		 */
		$default_paths = array(
			'/' // root
		);

		// Get random post
		$args = array( 'post_type' => 'post', 'numberposts' => 1, 'orderby' => 'rand' );
		query_posts($args);
		if (have_posts()) {
			while (have_posts()) {
				the_post();
				$default_paths[] = str_replace(get_option('siteurl'),'',get_permalink($wp_query->post->ID));
				break;
			}
		}

		// Get random page
		$post = false;
		$args = array( 'post_type' => 'page', 'numberposts' => 1, 'orderby' => 'rand' );
		query_posts($args);
		if (have_posts()) {
			while (have_posts()) {
				the_post();
				$default_paths[] = str_replace(get_option('siteurl'),'',get_permalink($wp_query->post->ID));
				break;
			}
		}

		// Random category
		$taxonomy = 'category';
        $terms = get_terms($taxonomy);
        shuffle ($terms);
        if ($terms) {
        	foreach($terms as $term) {
        		$default_paths[] = str_replace(get_option('siteurl'),'',get_category_link( $term->term_id ));
        		break;
        	}
        }

?>
<div class="wrap">
<h1><?php _e('Above The Fold Optimization', 'abovethefold') ?></h1>
</div>
<?php

		if ( !isset ( $_GET['tab'] ) ) {
			$_GET['tab'] = 'settings';
		}

		$this->settings_tabs($_GET['tab']);

		switch(strtolower(trim($_GET['tab']))) {

			case "settings":

				require_once('admin.settings.class.php');

			break;

			case "extract":

				require_once('admin.extract.class.php');

			break;

			case "compare":

				require_once('admin.compare.class.php');

			break;

			case "javascript":

				require_once('admin.javascript.class.php');

			break;
		}

	}

	/**
	 * Show admin notices
	 *
	 * @since     1.0
	 * @return    string    The version number of the plugin.
	 */
	public function show_notices() {

		settings_errors( 'abovethefold' );

		$notices = get_option( 'abovethefold_notices', '' );
		$persisted_notices = array();
		if ( ! empty( $notices ) ) {

			$noticerows = array();
			foreach ($notices as $notice) {
				switch(strtoupper($notice['type'])) {
					case "ERROR":
						$noticerows[] = '<div class="error">
							<p>
								'.__($notice['text'], 'abovethefold').'
							</p>
						</div>';

						/**
						 * Error notices remain visible for 1 minute
						 */
						if (isset($notice['date']) && $notice['date'] > (time() - 60)) {
							$persisted_notices[] = $notice;
						}

					break;
					default:
						$noticerows[] = '<div class="updated"><p>
							'.__($notice['text'], 'abovethefold').'
						</p></div>';
					break;
				}
			}
			?>
			<div>
				<?php print implode('',$noticerows); ?>
			</div>
			<?php

			update_option( 'abovethefold_notices', $persisted_notices );
		}

	}

	/**
	 * Set notice
	 *
	 * @since     1.0
	 * @return    string    The version number of the plugin.
	 */
	public function set_notice($notice,$type = 'NOTICE') {

		$notices = get_option( 'abovethefold_notices', '' );
		if (!is_array($notices)) {
			$notices = array();
		}
		if ( empty( $notice ) ) {
			delete_option( 'abovethefold_notices' );
		} else {
			array_unshift($notices,array(
				'text' => $notice,
				'type' => $type
			));
			update_option( 'abovethefold_notices', $notices );
		}

	}

    /**
	 * Upgrade plugin
	 *
	 * @since     2.3.10
	 */
	public function upgrade() {

		$current_version = get_option( 'wpabtf_version' );
		$options = get_option( 'abovethefold' );
		$update_options = false;

		if (!defined('WPABTF_VERSION') || WPABTF_VERSION !== $current_version) {

			update_option( 'wpabtf_version', WPABTF_VERSION );

			/**
			 * Pre 2.5.0 update functions
			 * 
			 * @since  2.5.0
			 */
			if (version_compare($current_version, '2.5.0', '<')) {

				/**
				 * Move global critical CSS to new location
				 */

				$global_cssfile = $this->CTRL->cache_path() . 'criticalcss_global.css';

				if (!file_exists($global_cssfile)) {
					
					// Check old location
					$old_cssfile = $this->CTRL->cache_path() . 'inline.min.css';
					if (file_exists($old_cssfile)) {

						/**
						 * Move file to new location
						 */
						$old_css = file_get_contents( $old_cssfile );
						
						// store contents of old css to new location
						file_put_contents( $global_cssfile, $old_css );
						if (!file_exists($global_cssfile) || file_get_contents( $global_cssfile ) !== $old_css) {
							wp_die('Failed to move critical CSS file to new location (v2.5+). Please check the write permissions for file:<br /><br /><strong>' . $global_cssfile . '</strong><br /><br />Old critical css file location:<br /><br />'.$old_cssfile.' ');
						}

						@unlink( $old_cssfile );
					}
				}

				/**
				 * Disable Google Web Font Optimizer plugin if ABTF Webfont Optimization is enabled
				 */
				if ($options['gwfo']) {
					@deactivate_plugins( 'google-webfont-optimizer/google-webfont-optimizer.php' );

					$options['gwfo_loadmethod'] = 'inline';
					$options['gwfo_loadposition'] = 'header';
					$update_options = true;
				}

				/**
				 * Enable external resource proxy if Localize Javascript is enabled
				 */
				if ($options['localizejs_enabled']) {

					unset($options['localizejs_enabled']);
					unset($options['localizejs']);

					$options['js_proxy'] = true;
					$options['css_proxy'] = true;
					$update_options = true;
				}

				// remove old options
				$old_options = array(
					'dimensions',
					'phantomjs_path',
					'cleancss_path',
					'remove_datauri',
					'urls'
				);
				foreach ($old_options as $opt) {
					unset($option[$opt]);
				}
			}

			if ($update_options) {
				update_option('abovethefold',$options);
			}

			/**
			 * Clear full page cache
			 */
			$this->CTRL->plugins->clear_pagecache();

		}
    }



}
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
	 * Critical CSS controller
	 *
	 * @since    2.5.4
	 * @access   public
	 * @var      object
	 */
	public $criticalcss;

	/**
	 * Tabs
	 *
	 * @since    2.5.4
	 * @access   public
	 * @var      array
	 */
	public $tabs = array(
    	'criticalcss' => 'Critical CSS',
    	'css' => 'CSS',
    	'javascript' => 'Javascript',
    	'proxy' => 'Proxy',
    	'settings' => 'Settings',
		'compare' => 'Quality Test'
    );

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


			// add settings link to plugin overview
			$this->CTRL->loader->add_filter('plugin_action_links_above-the-fold-optimization/abovethefold.php', $this, 'settings_link' );

			// Handle admin notices
			$this->CTRL->loader->add_action( 'admin_notices', $this, 'show_notices' );

	        // Update body class
			$this->CTRL->loader->add_filter( 'admin_body_class', $this, 'admin_body_class' );

			/**
			 * Load dependencies
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/admin.criticalcss.class.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/admin.css.class.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/admin.javascript.class.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/admin.proxy.class.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/admin.settings.class.php';

			/**
			 * Load critical CSS management
			 */
			$this->criticalcss = new Abovethefold_Admin_CriticalCSS( $CTRL );

			/**
			 * Load CSS management
			 */
			$this->css = new Abovethefold_Admin_CSS( $CTRL );

			/**
			 * Load Javascript management
			 */
			$this->javascript = new Abovethefold_Admin_Javascript( $CTRL );

			/**
			 * Load proxy management
			 */
			$this->proxy = new Abovethefold_Admin_Proxy( $CTRL );

			/**
			 * Load settings management
			 */
			$this->settings = new Abovethefold_Admin_Settings( $CTRL );

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
	 * Get active tab
	 */
	public function active_tab( $default = 'criticalcss' ) {

		// get tab from query string
		$tab = (isset($_REQUEST['tab'])) ? trim(strtolower($_REQUEST['tab'])) : $default;

		// invalid tab
		if (!isset($this->tabs[$tab])) {
			$tab = $default;
		}

		return $tab;
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

		/**
		 * Clear page cache
		 */
		if ((isset($_REQUEST['clear']) && $_REQUEST['clear'] === 'pagecache') || isset($_POST['clear_pagecache'])) {

			check_admin_referer('abovethefold');

			$this->clear_pagecache();

			wp_redirect(admin_url('admin.php?page=abovethefold&tab=settings'));
			exit;
		}

		// add general admin javascript
		wp_enqueue_script( 'abtf_admincp', plugin_dir_url( __FILE__ ) . 'js/admincp.min.js', array( 'jquery' ) );

		// add general admin CSS
		wp_enqueue_style( 'abtf_admincp', plugin_dir_url( __FILE__ ) . 'css/admincp.min.css' );

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
	        'id'     => 'abovethefold-top',
	        'meta'   => array(
	            'class' => 'ab-sub-secondary', // 
	        )
	    ) );

		$admin_bar->add_node( array(
			'parent' => 'abovethefold-top',
			'id' => 'abovethefold-tools',
			'title' => __( 'ABTF Tools', 'abovethefold' )
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
		 * Compare Critical CSS vs Full CSS
		 */
		$compareurl = preg_replace('|\#.*$|Ui','',$currenturl) . ((strpos($currenturl,'?') !== false) ? '&' : '?') . 'compare-abtf=' . md5(SECURE_AUTH_KEY . AUTH_KEY);
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-tools',
			'id' => 'abovethefold-tools-compare',
			'title' => __( 'Critical CSS Quality Test (mirror view)', 'abovethefold' ),
			'href' => $compareurl,
			'meta' => array( 'title' => __( 'Critical CSS Quality Test (mirror view)', 'abovethefold' ), 'target' => '_blank' )
		) );

		/**
		 * Extract Full CSS
		 */
		$extracturl = preg_replace('|\#.*$|Ui','',$currenturl) . ((strpos($currenturl,'?') !== false) ? '&' : '?') . 'extract-css=' . md5(SECURE_AUTH_KEY . AUTH_KEY) . '&output=print';
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-tools',
			'id' => 'abovethefold-tools-extract',
			'title' => __( 'Extract Full CSS', 'abovethefold' ),
			'href' => $extracturl,
			'meta' => array( 'title' => __( 'Extract Full CSS', 'abovethefold' ), 'target' => '_blank' )
		) );
		/**
		 * Page cache clear 
		 */
		$clear_url = add_query_arg( array( 'page' => 'abovethefold', 'clear' => 'pagecache' ), '/wp-admin/admin.php' );
		$nonced_url = wp_nonce_url( $clear_url, 'abovethefold' );
		$admin_bar->add_node( array(
			'parent' => 'abovethefold-tools',
			'id' => 'abovethefold-tools-clear-pagecache',
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
			'title' => __( 'GTmetrix', 'abovethefold' ),
			'href' => 'http://gtmetrix.com/?url='.urlencode($currenturl).'',
			'meta' => array( 'title' => __( 'GTmetrix', 'abovethefold' ), 'target' => '_blank' )
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
	}

	/**
	 * Clear page cache with notice
	 */
	public function clear_pagecache( $notice = true ) {

		$this->CTRL->plugins->clear_pagecache();

		if ($notice) {
			$this->set_notice('Page related caches from <a href="https://github.com/optimalisatie/above-the-fold-optimization/tree/master/trunk/modules/plugins/" target="_blank">supported plugins</a> cleared.<p><strong>Note:</strong> This plugin does not contain a page cache. The page cache clear function for multiple other plugins is a tool.', 'NOTICE');
		}
	}

	/**
	 * Save settings
	 */
	public function save_settings( $options, $notice ) {

		if (!is_array($options) || empty($options)) {
			wp_die('No settings to save');
		}

		// store update count
		if (!isset($options['update_count'])) {
			$options['update_count'] = 0;
		}
		$options['update_count']++;

		// update settings
		update_option('abovethefold',$options);

		// add notice
		$saved_notice = '<div style="font-size:18px;line-height:20px;margin:0px;">'.$notice.'</div>';

		/**
		 * Clear full page cache
		 */
		if ($options['clear_pagecache']) {
			$this->CTRL->admin->clear_pagecache(false);

			$saved_notice .= '<p style="font-style:italic;font-size:14px;line-height:16px;">Page related caches from <a href="https://github.com/optimalisatie/above-the-fold-optimization/tree/master/trunk/modules/plugins/" target="_blank">supported plugins</a> cleared.</p>';
		}

		$this->CTRL->admin->set_notice($saved_notice, 'NOTICE');
	}

    /**
     * Display settings page
     */
	public function settings_page() {
		global $pagenow, $wp_query;

		// load options
		$options = get_option('abovethefold');
		if (!is_array($options)) { $options = array(); }

?>
<div class="wrap">
<h1><?php _e('Above The Fold Optimization', 'abovethefold') ?></h1>
</div>
<?php

		// active tab
		$tab = (isset($_REQUEST['tab'])) ? trim($_REQUEST['tab']) : 'criticalcss';

		// invalid tab
		if (!isset($this->tabs[$tab])) {
			$tab = 'criticalcss';
		}

		// print tabs
        echo '<div id="icon-themes" class="icon32"><br></div>';
        echo '<h1 class="nav-tab-wrapper">';
        foreach( $this->tabs as $tabkey => $name ){
            $class = ( $tabkey == $tab ) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='?page=abovethefold&amp;tab=$tabkey'>$name</a>";

        }
        echo '</h1>';

		// author info
		require_once('admin.author.inc.php');
		 
        // print tab content
		switch($tab) {
			case "criticalcss":
			case "css":
			case "javascript":
			case "proxy":
			case "settings":
			case "extract":
			case "compare":
				require_once('admin.'.$tab.'.inc.php');
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
			 * Pre 2.5.0 update
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
			}

			/**
			 * Pre 2.5.4 update
			 */
			if (version_compare($current_version, '2.5.4', '<=')) {

				/**
				 * Convert preload list strings to array
				 */
				if (isset($options['css_proxy_preload']) && is_string($options['css_proxy_preload']) && $options['css_proxy_preload'] !== '') {
					$options['css_proxy_preload'] = explode("\n",$options['css_proxy_preload']);
					$update_options = true;
				}
				if (isset($options['js_proxy_preload']) && is_string($options['js_proxy_preload']) && $options['js_proxy_preload'] !== '') {
					$options['js_proxy_preload'] = explode("\n",$options['js_proxy_preload']);
					$update_options = true;
				}
			}

			/**
			 * Pre 2.5.5 update
			 */
			if (version_compare($current_version, '2.5.5', '<=')) {

				/**
				 * Convert Google Web Font list string to array
				 */
				if (isset($options['gwfo_googlefonts']) && is_string($options['gwfo_googlefonts']) && $options['gwfo_googlefonts'] !== '') {

					// convert url list to array
					$fonts = explode("\n",$options['gwfo_googlefonts']);
					$options['gwfo_googlefonts'] = array();
					if (!empty($fonts)) {
						foreach ($fonts as $font) {
							$font = trim($font);
							if ($font === '') { continue; }
							$options['gwfo_googlefonts'][] = $font;
						}
					}
					$update_options = true;
				}
			}

			// remove old options
			$old_options = array(
				'dimensions',
				'phantomjs_path',
				'cleancss_path',
				'remove_datauri',
				'urls',
				'genurls',
				'localizejs_enabled'
			);
			foreach ($old_options as $opt) {
				if (isset($options[$opt])) {
					unset($option[$opt]);
					$update_options = true;
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
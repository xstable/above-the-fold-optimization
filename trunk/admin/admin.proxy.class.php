<?php

/**
 * Proxy admin controller
 *
 * @since      2.5.4
 * @package    abovethefold
 * @subpackage abovethefold/admin
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */

class Abovethefold_Admin_Proxy {

	/**
	 * Above the fold controller
	 *
	 * @access   public
	 */
	public $CTRL;

	/**
	 * Options
	 *
	 * @access   public
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

		/**
		 * Admin panel specific
		 */
		if (is_admin()) {

			/**
			 * Handle form submissions
			 */
			$this->CTRL->loader->add_action('admin_post_abtf_proxy_update', $this,  'update_settings');

		}

	}

    /**
	 * Update settings
	 */
	public function update_settings() {

		check_admin_referer('abovethefold');

		// stripslashes should always be called
		// @link https://codex.wordpress.org/Function_Reference/stripslashes_deep
		$_POST = array_map( 'stripslashes_deep', $_POST );

		$options = get_option('abovethefold');
		if (!is_array($options)) { $options = array(); }

		// input
		$input = (isset($_POST['abovethefold']) && is_array($_POST['abovethefold'])) ? $_POST['abovethefold'] : array();

		/**
		 * Proxy settings
		 */
		$options['proxy_url'] = (isset($input['proxy_url'])) ? trim($input['proxy_url']) : '';
		if ($options['proxy_url']) {

			if (!preg_match('|^http(s)?://|Ui',$options['proxy_url'])) {
				$this->CTRL->admin->set_notice('<p style="font-size:18px;">Invalid proxy url.</p>', 'ERROR');

				wp_redirect(admin_url('admin.php?page=abovethefold&tab=proxy'));
				exit;
			}

			if (strpos($options['proxy_url'],'{PROXY:URL}') === false) {
				$this->CTRL->admin->set_notice('<p style="font-size:18px;">Proxy url does not contain <code>{PROXY:URL}</code>.</p>', 'ERROR');

				wp_redirect(admin_url('admin.php?page=abovethefold&tab=proxy'));
				exit;	
			}
		}
		
		// CSS proxy
		$options['css_proxy'] = (isset($input['css_proxy']) && intval($input['css_proxy']) === 1) ? true : false;
		$options['css_proxy_include'] = trim($input['css_proxy_include']);
		$options['css_proxy_exclude'] = trim($input['css_proxy_exclude']);
		$options['css_proxy_preload'] = trim($input['css_proxy_preload']);

		// Javascript proxy
		$options['js_proxy'] = (isset($input['js_proxy']) && intval($input['js_proxy']) === 1) ? true : false;
		$options['js_proxy_include'] = trim($input['js_proxy_include']);
		$options['js_proxy_exclude'] = trim($input['js_proxy_exclude']);
		$options['js_proxy_preload'] = trim($input['js_proxy_preload']);

		// update settings
		$this->CTRL->admin->save_settings($options, 'Proxy settings saved.');

		wp_redirect(admin_url('admin.php?page=abovethefold&tab=proxy'));
		exit;
    }

}
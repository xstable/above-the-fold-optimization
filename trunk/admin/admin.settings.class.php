<?php

/**
 * Settings admin controller
 *
 * @since      2.5.4
 * @package    abovethefold
 * @subpackage abovethefold/admin
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */

class Abovethefold_Admin_Settings {

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
			$this->CTRL->loader->add_action('admin_post_abtf_settings_update', $this,  'update_settings');

		}

	}

    /**
	 * Update settings
	 */
	public function update_settings() {

		check_admin_referer('abovethefold');

		/**
		 * Clear page cache
		 */
		if (isset($_POST['clear_pagecache'])) {

			check_admin_referer('abovethefold');

			$this->CTRL->admin->clear_pagecache();

			wp_redirect(admin_url('admin.php?page=abovethefold&tab=settings'));
			exit;
		}

		// stripslashes should always be called
		// @link https://codex.wordpress.org/Function_Reference/stripslashes_deep
		$_POST = array_map( 'stripslashes_deep', $_POST );

		$options = get_option('abovethefold');
		if (!is_array($options)) { $options = array(); }

		// input
		$input = (isset($_POST['abovethefold']) && is_array($_POST['abovethefold'])) ? $_POST['abovethefold'] : array();

		/**
		 * Debug / admin options
		 */
		$options['debug'] = (isset($input['debug']) && intval($input['debug']) === 1) ? true : false;
		$options['clear_pagecache'] = (isset($input['clear_pagecache']) && intval($input['clear_pagecache']) === 1) ? true : false;
		$options['adminbar'] = (isset($input['adminbar']) && intval($input['adminbar']) === 1) ? true : false;
	
		// update settings
		$this->CTRL->admin->save_settings($options, 'Settings saved.');

		wp_redirect(admin_url('admin.php?page=abovethefold&tab=settings'));
		exit;
    }

}
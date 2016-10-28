<?php

/**
 * CSS admin controller
 *
 * @since      2.5.4
 * @package    abovethefold
 * @subpackage abovethefold/admin
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */

class Abovethefold_Admin_CSS {

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
			$this->CTRL->loader->add_action('admin_post_abtf_css_update', $this,  'update_settings');

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
		 * Optimize CSS delivery
		 */
		$options['cssdelivery'] = (isset($input['cssdelivery']) && intval($input['cssdelivery']) === 1) ? true : false;
		$options['loadcss_enhanced'] = (isset($input['loadcss_enhanced']) && intval($input['loadcss_enhanced']) === 1) ? true : false;
		$options['cssdelivery_position'] = trim($input['cssdelivery_position']);
		$options['cssdelivery_ignore'] = trim(sanitize_text_field($input['cssdelivery_ignore']));
		$options['cssdelivery_remove'] = trim(sanitize_text_field($input['cssdelivery_remove']));
		$options['cssdelivery_renderdelay'] = (isset($input['cssdelivery_renderdelay']) && is_numeric($input['cssdelivery_renderdelay']) && intval($input['cssdelivery_renderdelay']) > 0) ? intval($input['cssdelivery_renderdelay']) : false;

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
				$this->CTRL->admin->set_notice('WebFontConfig variable is not valid. It should consist of <code>WebFontConfig = { ... };</code>.', 'ERROR');
			}
		}

		/**
		 * Google Fonts
		 */
		$options['gwfo_googlefonts'] = array();

		$input['gwfo_googlefonts'] = trim($input['gwfo_googlefonts']);
		if ($input['gwfo_googlefonts'] !== '') {
			$fonts = explode("\n",$input['gwfo_googlefonts']);
			if (!empty($fonts)) {
				foreach ($fonts as $font) {
					$font = trim($font);
					if ($font === '') { continue; }
					$options['gwfo_googlefonts'][] = $font;
				}
				$options['gwfo_googlefonts'] = array_unique($options['gwfo_googlefonts']);
			}
		}

		/**
		 * Google Fonts Remove List
		 */
		$options['gwfo_googlefonts_remove'] = array();

		$input['gwfo_googlefonts_remove'] = trim($input['gwfo_googlefonts_remove']);
		if ($input['gwfo_googlefonts_remove'] !== '') {
			$fonts = explode("\n",$input['gwfo_googlefonts_remove']);
			if (!empty($fonts)) {
				foreach ($fonts as $font) {
					$font = trim($font);
					if ($font === '') { continue; }
					$options['gwfo_googlefonts_remove'][] = $font;
				}
				$options['gwfo_googlefonts_remove'] = array_unique($options['gwfo_googlefonts_remove']);
			}
		}


		// update settings
		$this->CTRL->admin->save_settings($options, 'CSS optimization settings saved.');

		wp_redirect(admin_url('admin.php?page=abovethefold&tab=css'));
		exit;
    }

}
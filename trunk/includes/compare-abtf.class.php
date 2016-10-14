<?php

/**
 * Abovethefold optimization functions and hooks.
 *
 * This class provides the functionality for optimization functions and hooks.
 *
 * @since      1.0
 * @package    abovethefold
 * @subpackage abovethefold/includes
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


class Abovethefold_CompareABTF {

	/**
	 * Above the fold controller
	 *
	 * @since    1.0
	 * @access   public
	 * @var      object    $CTRL
	 */
	public $CTRL;

	/**
	 * CSS buffer started
	 */
	public $buffer_started = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 * @var      object    $Optimization       The Optimization class.
	 */
	public function __construct( &$CTRL ) {

		$this->CTRL =& $CTRL;

		// output buffer
		$this->CTRL->loader->add_action('init', $this, 'start_output_buffer',99999);
	}

	/**
	 * Init output buffering
	 *
	 * @since    2.5.0
	 */
	public function start_output_buffer( ) {

		// prevent double buffer
		if ($this->buffer_started) {
			return;
		}
		$this->buffer_started = true;

		// start buffer
		ob_start(array($this, 'end_buffering'));

	}

	/**
	 * End compare critical CSS output buffer
	 *
	 * @since    2.5.0
	 */
	public function end_buffering($HTML) {
		if (is_feed() || is_admin()) {
			return $HTML;
		}
		if ( stripos($HTML,"<html") === false || stripos($HTML,"<xsl:stylesheet") !== false ) {
			// Not valid HTML
			return $HTML;
		}

		$url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

		$parsed = array();
		parse_str(substr($url, strpos($url, '?') + 1), $parsed);
		$extractkey = $parsed['extract-css'];
		unset($parsed['compare-abtf']);
		unset($parsed['output']);
		$url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'].'/';
		if(!empty($parsed))
		{
			$url .= '?' . http_build_query($parsed);
		}

		/**
		 * Print compare critical CSS page
		 */

		require_once(plugin_dir_path( realpath(dirname( __FILE__ ) . '/') ) . 'includes/compare-abtf.inc.php');

		return $cssoutput;
	}

}

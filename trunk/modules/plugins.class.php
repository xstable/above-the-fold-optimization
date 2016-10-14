<?php

/**
 * Base optimization plugin controller
 *
 * @since      2.5
 * @package    abovethefold
 * @subpackage abovethefold/modules
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


abstract class Abovethefold_OPP {

	/**
	 * Above the fold controller.
	 * 
	 * @access   public
	 * @var      object    $CTRL
	 */
	public $CTRL;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct( &$CTRL ) {

		$this->classname = str_replace('Abovethefold_OPP_','',get_called_class());
		if (!$this->name) {
			$this->name = $this->classname;
		}

		// above the fold controller
		$this->CTRL =& $CTRL;
	}
	
	/**
	 * Process / optimize (minified) Javascript
	 */
	 public function process_js($js) {
		return $js;
		/**
		 * Localize Javascript
		 */
		//if ($this->CTRL->options['localizejs_enabled']) {
			//$js = $this->CTRL->localizejs->parse_js($js);
		//}

	 	//return apply_filters('abtf_js', $js);
	 }

	 // Disable CSS minification
	 public function disable_css_minify() {}

	 // Disable js minification
	 public function disable_js_minify() {}

	 // Disable HTML minification
	 public function disable_html_minify() {}

	 // Clear page cache
	 public function clear_pagecache() {}

}
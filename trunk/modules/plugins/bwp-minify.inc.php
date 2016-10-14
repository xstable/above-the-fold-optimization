<?php

/**
 * Better WordPress Minify module
 *
 * @link       https://wordpress.org/plugins/bwp-minify/
 *
 * @since      2.5.0
 * @package    abovethefold
 * @subpackage abovethefold/modules/plugins
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */

class Abovethefold_OPP_BwpMinify extends Abovethefold_OPP {

	/**
	 * Plugin file reference
	 */
	public $plugin_file = 'bwp-minify/bwp-minify.php';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @var      object    $CTRL       The above the fold admin controller..
	 */
	public function __construct( &$CTRL ) {
		parent::__construct( $CTRL );

		// Is the plugin enabled?
		if ( !$this->active() ) {
			return;
		}
	}

	/**
	 * Is plugin active?
	 */
	public function active($type = false) {

		if ( $this->CTRL->plugins->active( $this->plugin_file ) ) {

			// plugin is active
			if (!$type) {
				return true;
			}
		}

		return false; // not active for special types (css, js etc.)
	}


	/**
	 * Clear full page cache
	 */
	public function clear_pagecache() {

		/**
		 * Mimic private BWP_MINIFY::_flush_cache function
		 *
		 * This method works since @version 1.3.3
		 */
		global $bwp_minify;

		if (isset($bwp_minify) && method_exists($bwp_minify,'get_cache_dir')) {
			$cache_dir = $bwp_minify->get_cache_dir();

			$deleted = 0;
			$cache_dir = trailingslashit($cache_dir);

			if (is_dir($cache_dir))
			{
				if ($dh = opendir($cache_dir))
				{
					while (($file = readdir($dh)) !== false)
					{
						if (preg_match('/^minify_[a-z0-9\\.=_,]+(\.gz)?$/ui', $file)
							|| preg_match('/^minify-b\d+-[a-z0-9-_.]+(\.gz)?$/ui', $file)
						) {
							$deleted += true === @unlink($cache_dir . $file)
								? 1 : 0;
						}
					}
					closedir($dh);
				}
			}

		}
	}


}
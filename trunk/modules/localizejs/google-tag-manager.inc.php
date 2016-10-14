<?php

/**
 * Google Tag Manager
 *
 * @since      2.3.3
 * @package    abovethefold
 * @subpackage abovethefold/admin
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


class Abovethefold_LocalizeJSModule_GoogleTagManager extends Abovethefold_LocalizeJSModule {


	// The name of the module
	public $name = 'Google Tag Manager (gtm.js)';
	public $link = 'http://www.google.com/tagmanager/';

	public $update_interval = 86400; // once per day
	public $script_source = 'http://www.googletagmanager.com/gtm.js?id=%%ID%%';

	public $snippets = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0
	 * @var      object    $CTRL       The above the fold admin controller..
	 */
	public function __construct( &$CTRL ) {

		parent::__construct( $CTRL );

		$this->options = array_merge(array(
			'incmethod' => '','id' => ''
		),$this->options);

		if (isset($this->CTRL->options['localizejs'][$this->classname]['id'])) {
			$this->id = $this->CTRL->options['localizejs'][$this->classname]['id'];
		} else { $this->id = ''; }

		$this->source_variables = array(
			'%%ID%%' => $this->id
		);

		if ($this->CTRL->options['localizejs'][$this->classname]['enabled']) {
			switch ($this->CTRL->options['localizejs'][$this->classname]['incmethod']) {
				case "replace":

				break;
				default:
					$this->CTRL->loader->add_action('wp_head', $this, 'setup_analytics_script', -1);
					$this->CTRL->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_script', -1);
				break;
			}
		}

	}

	/**
	 * Include script
	 */
	public function enqueue_script( ) {

		if (!$this->source_variables['%%ID%%']) {
			return false;
		}

		list($script_url, $script_time) = $this->get_script( true );

		wp_enqueue_script( 'google-tag-manager', $script_url , array(), date('Ymd', $script_time) );

	}


	/**
	 * Get script filename
	 */
	public function get_script_filename( ) {

		// hash from module file
		$hash = md5(__FILE__);
		$script_file = $this->cachepath . $hash . '-'.$this->classname.'-id'.$this->id.'.js';

		return $script_file;

	}

	/**
	 * Download script
	 */
	public function download_script( $script_source, $script_file ) {

		$script = $this->CTRL->localizejs->download_script( $script_source, $script_file );

		/**
		 * Parse scripts within Google Tag Manager from modules
		 */
		$modules = $this->CTRL->localizejs->get_modules( true );

		foreach ($modules as $module_file) {
			if ($module_file === __FILE__) {
				continue 1;
			}
			$mod = $this->CTRL->localizejs->load_module( $module_file );
			$script = $mod->parse_js( $script );
		}

		return $script;

	}

	/**
	 * Parse Google Analytics javascript and return original code (to replace) and file-URL.
	 *
	 * @since 2.3
	 */
	public function parse_analytics_js( $code ) {

		/**
		 * analytics.js
		 *
		 * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/
		 * @since 2.3
		 */

		 if (strpos($code,'googletagmanager.com/gtm.js') !== false && preg_match_all('|googletagmanager\.com/gtm\.js[^\}]+\}\)\([^\)]+\'([^\']+)\'\);|Ui',$code,$out)) {

			$options = get_option( 'abovethefold' );
			$options['localizejs'][$this->classname]['id'] = $out[1][0];
			$this->source_variables['%%ID%%'] = $out[1][0];
			update_option( 'abovethefold', $options );

			$regex = array();

			$replace = '';
			if ($this->CTRL->options['localizejs'][$this->classname]['incmethod'] === 'replace') {

				$regex[] = '#([\'|"])[^\'"]+googletagmanager\.com/gtm\.js\?id=[^\;]+\;#Ui';

				list($script_url,$script_time) = $this->get_script( true );
				$replace = '$1' . $script_url . '$1;';

			} else {

				/**
				 * Remove async snippet
				 */
				$regex[] = '#\(\s*function\s*\([^\)]+\)((?!</script).)*googletagmanager\.com/gtm\.js((?!</script).)*\([^\)]+dataLayer[^\)]+\);#is';
			}

			foreach ($regex as $str) {
				$code = preg_replace($str,$replace,$code);
			}

		}

		return $code;

	}

	/**
	 * Admin configuration options
	 */
	public function admin_config( $extra_config = '' ) {

		$config = '<div class="inside">';

		$config .= 'Include method: <select name="'.$this->option_name('incmethod').'">
			'.$this->select_options(array(
				'replace' => 'Replace URL in original code',
				'native' => 'WordPress native script include'
			),$this->options['incmethod']).'
		</select>';

		$config .= '</div>';

		$config .= '&nbsp; ID: <input type="text" size="20" name="'.$this->option_name('id').'" placeholder="(detect)" value="'.htmlentities($this->options['id']).'" />';

		parent::admin_config( $config );

	}

	/**
	 * Parse HTML
	 */
	public function parse_html( $html ) {

		$html = $this->parse_analytics_js( $html );

		return parent::parse_html( $html );

	}

	/**
	 * Parse Javascript
	 */
	public function parse_js( $js ) {

		$js = $this->parse_analytics_js( $js );

		return parent::parse_js( $js );

	}

}
<?php

/**
 * DoubleClick Google Analytics dc.js
 *
 * @since      2.3
 * @package    abovethefold
 * @subpackage abovethefold/admin
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


class Abovethefold_LocalizeJSModule_DoubleclickDc extends Abovethefold_LocalizeJSModule {


	// The name of the module
	public $name = 'DoubleClick Google Analytics (dc.js)';
	public $link = 'https://support.google.com/analytics/answer/2444872';

	public $update_interval = 86400; // once per day
	public $script_source = 'https://stats.g.doubleclick.net/dc.js';

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
			'incmethod' => ''
		),$this->options);

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

		list($script_url, $script_time) = $this->get_script( true );

		wp_enqueue_script( 'google-analytics-doubleclick', $script_url , array(), date('Ymd', $script_time) );

	}

	/**
	 * Analytics object setup
	 */
	public function setup_analytics_script( ) {

?>
<script>
(function(d,w,c){
w['GoogleAnalyticsObject']=c;w[c]=w[c]||function(){ (w[c].q=w[c].q||[]).push(arguments); },w[c].l=1*new Date(); })(document,window,'ga');
</script>
<?php

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
		if (strpos($code,'doubleclick.net/dc.js') !== false) {

			$regex = array();

			$replace = array();
			if ($this->CTRL->options['localizejs'][$this->classname]['incmethod'] === 'replace') {

				list($script_url,$script_time) = $this->get_script( true );

				// ga.src regex
				$regex[] = '#ga\.src\s*=\s*\([^\)]*\)\s*\+\s*[\'|"]stats\.g\.doubleclick\.net/dc\.js[\'|"];#Ui';
				$replace[] = 'ga.src = \'' . $script_url . '\';';

			} else {

				/**
				 * Remove async snippet
				 */
				$regex[] = '#\(\s*function\(\s*\)((?!</script).)*doubleclick\.net/dc\.js((?!</script).)*\(\s*\);#is';
				$replace[] = '';
			}

			$code = preg_replace($regex,$replace,$code);

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
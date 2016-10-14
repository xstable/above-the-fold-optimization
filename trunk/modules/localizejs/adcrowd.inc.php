<?php

/**
 * Adcrowd (Retargeting)
 *
 * @since      2.3.4
 * @package    abovethefold
 * @subpackage abovethefold/admin
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


class Abovethefold_LocalizeJSModule_Adcrowd extends Abovethefold_LocalizeJSModule {


	// The name of the module
	public $name = 'Adcrowd Retargeting (adcrowd.js)';
	public $link = 'https://www.adcrowd.com/';

	public $update_interval = 86400; // once per day
	public $script_source = '//pixel.adcrowd.com/smartpixel/%%ID%%.js';

	public $id = '';

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
		}

		$this->source_variables = array(
			'%%ID%%' => $this->id
		);

		if ($this->CTRL->options['localizejs'][$this->classname]['enabled']) {
			switch ($this->CTRL->options['localizejs'][$this->classname]['incmethod']) {
				default:

				break;
			}
		}

	}


	/**
	 * Get script filename
	 */
	public function get_script_filename( ) {

		// hash from module file
		$hash = md5(__FILE__);
		$script_file = $this->cachepath . $hash . '-'.$this->classname.'.js';

		return $script_file;

	}


	/**
	 * Parse javascript code
	 *
	 * @since 2.3.4
	 */
	public function parse_adcrowd_js( $code ) {

		$current_sv = $this->sv;

		if (strpos($code, '.adcrowd.com') !== false && preg_match_all('|adcrowd\.com/smartpixel/([a-z0-9]{32})\.js|Ui',$code,$out)) {

			$options = get_option( 'abovethefold' );

			if (empty($options['localizejs'][$this->classname]['id'])) {
				$options['localizejs'][$this->classname]['id'] = trim($out[1][0]);
				$this->source_variables['%%ID%%'] = $out[1][0];
				update_option( 'abovethefold', $options );
			}

			$regex = array();

			$replace = '';

			/**
			 * Remove async snippet
			 */
			$regex[] = '#([\'|"])[^\'"]+pixel\.adcrowd\.com/smartpixel/([a-z0-9]{32})\.js([^\'"]+)?([\'|"])#Ui';

			list($script_url,$script_time) = $this->get_script( true );

			$replace = '$1' . $script_url . '$1;';

			foreach ($regex as $str) {
				$code = preg_replace($str,$replace,$code);
			}

		}

		return $code;

	}

	/**
	 * Download script
	 */
	public function download_script( $script_source, $script_file ) {
		return $this->CTRL->localizejs->download_script( $script_source, $script_file );
	}

	/**
	 * Admin configuration options
	 */
	public function admin_config( $extra_config = '' ) {

		$config = '<div class="inside">';

		$config .= 'Include method: <select name="'.$this->option_name('incmethod').'">
			'.$this->select_options(array(
				'native' => 'Replace URL in original code'
			),$this->options['incmethod']).'
		</select>';

		$config .= '&nbsp; ID: <input type="text" size="20" name="'.$this->option_name('id').'" placeholder="(detect)" value="'.htmlentities($this->options['id']).'" />';

		$config .= '</div>';

		parent::admin_config( $config );

	}

	/**
	 * Parse HTML
	 */
	public function parse_html( $html ) {

		$html = $this->parse_adcrowd_js( $html );

		return parent::parse_html( $html );

	}

	/**
	 * Parse Javascript
	 */
	public function parse_js( $js ) {

		$js = $this->parse_adcrowd_js( $js );

		return parent::parse_js( $js );

	}

}
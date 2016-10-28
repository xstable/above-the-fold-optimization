<?php

/**
 * Abovethefold Web Font optimization functions and hooks.
 *
 * This class provides the functionality for Web Font optimization functions and hooks.
 *
 * @since      2.5.0
 * @package    abovethefold
 * @subpackage abovethefold/includes
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


class Abovethefold_WebFonts {

	/**
	 * Above the fold controller
	 *
	 * @since    1.0
	 * @access   public
	 * @var      object    $CTRL
	 */
	public $CTRL;

	/**
	 * webfont.js CDN url
	 */
	public $cdn_url = 'https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js';

	/**
	 * webfont.js CDN version
	 */
	public $cdn_version = '1.6.26';

	/**
	 * Google fonts
	 */
	public $googlefonts = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 * @var      object    $Optimization       The Optimization class.
	 */
	public function __construct( &$CTRL ) {

		$this->CTRL =& $CTRL;

		if ($this->CTRL->disabled) {
			return; // above the fold optimization disabled for area / page
		}

		// load google fonts from settings
		if (isset($this->CTRL->options['gwfo_googlefonts']) && is_array($this->CTRL->options['gwfo_googlefonts'])) {
			$this->googlefonts = $this->CTRL->options['gwfo_googlefonts'];
		}

		// define default settings
		if (!isset($this->CTRL->options['gwfo_loadmethod'])) {
			$this->CTRL->options['gwfo_loadmethod'] = 'inline';
		}
		if (!isset($this->CTRL->options['gwfo_loadposition'])) {
			$this->CTRL->options['gwfo_loadposition'] = 'header';
		}

		/**
		 * Google Web Font Optimizer enabled
		 */
		if (isset($this->CTRL->options['gwfo']) && $this->CTRL->options['gwfo']) {

			// add filter for CSS minificaiton output
			$this->CTRL->loader->add_filter( 'abtf_css', $this, 'process_css' );

			// add filter for CSS file processing
			$this->CTRL->loader->add_filter( 'abtf_cssfile_pre', $this, 'process_cssfile' );

			// add filter for HTML output
			$this->CTRL->loader->add_filter( 'abtf_html', $this, 'process_html' );

			// add filter for HTML output
			$this->CTRL->loader->add_filter( 'abtf_html_replace', $this, 'replace_html' );

			if (isset($this->CTRL->options['gwfo_loadmethod']) && $this->CTRL->options['gwfo_loadmethod'] === 'wordpress') {

				/**
				 * load webfont.js via WordPress include
				 */
				$this->CTRL->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_webfontjs', 10);
			}

		}

	}

	/**
	 * Extract fonts from CSS
	 */
	public function process_css($CSS) {

		/**
		 * Regex search replace on CSS
		 */
		$search = array();
		$replace = array();

		/**
		 * Parse Google Fonts
		 */
		$googlefonts = array();

		// find @import with Google Font CSS
		if (preg_match_all('#(?:@import)(?:\\s)(?:url)?(?:(?:(?:\\()(["\'])?(?:[^"\')]+)\\1(?:\\))|(["\'])(?:.+)\\2)(?:[A-Z\\s])*)+(?:;)#Ui',$CSS,$out) && !empty($out[0])) {

			foreach ($out[0] as $n => $fontLink) {
				if (substr_count($fontLink, "fonts.googleapis.com/css") > 0) {
					$fontLink = preg_replace('|^.*(//fonts\.[^\s\'\"\)]+)[\s\|\'\|\"\|\)].*|is','$1',$fontLink);

					// parse fonts
					$fonts = $this->fonts_from_url($fontLink);

					// font contains Google Fonts
					if ($fonts && !empty($fonts['google'])) {
						foreach ($fonts['google'] as $googlefont) {
							if (!in_array(trim($googlefont),$this->googlefonts)) {
								$googlefonts[] = trim($googlefont);
							}
						}

						/**
						 * Remove @import from CSS
						 */
						$search[] = '|'.preg_quote($out[0][$n]).'|Ui';
						$replace[] = ' ';
					}
				}
			}

			if (!empty($googlefonts)) {
				$this->update_google_fonts($googlefonts);
			}
		}

		// perform search/replace
		if (!empty($search)) {
			$CSS = preg_replace($search,$replace,$CSS);
		}

		return trim($CSS);
	}

	/**
	 * Parse CSS file in CSS file loop
	 */
	public function process_cssfile($cssfile) {
		
		// ignore
		if (!$cssfile || in_array($cssfile,array('delete','ignore'))) {
			return $cssfile;
		}

		// Google font?
		if (strpos($cssfile,'fonts.googleapis.com/css') !== false) {

			$googlefonts = array();

			$fonts = $this->fonts_from_url($cssfile);
			if ($fonts && !empty($fonts['google'])) {
				foreach ($fonts['google'] as $googlefont) {
					if (!in_array(trim($googlefont),$this->googlefonts)) {
						$googlefonts[] = trim($googlefont);
					}
				}

				// google fonts
				if (!empty($googlefonts)) {
					$this->update_google_fonts($googlefonts);
				}

				// delete file from HTML
				return 'delete';
			}
		}

		return $cssfile;
	}

	/**
	 * Extract fonts from HTML
	 */
	public function process_html($HTML) {

		/**
		 * Regex search replace on CSS
		 */
		$search = array();
		$replace = array();

		/**
		 * Parse Google Fonts in WebFontConfig
		 */
		if (strpos($HTML,'WebFontConfig') !== false) {

			$googlefonts = array();

			// parse json
			if (preg_match_all('#WebFontConfig\s*=\s*\{[^;]+\}[;|<]#s',$HTML,$out)) {

				foreach ($out[0] as $wfc) {

					// Extract Google fonts
					if (strpos($wfc,'google') !== false) {
						if (preg_match('#google[\'|"]?\s*:\s*\{[^\}]+families\s*:\s*(\[[^\]]+\])#is',$wfc,$gout)) {
							$gfonts = @json_decode($this->fixJSON($gout[1]),true);
							if (is_array($gfonts) && !empty($gfonts)) {
								$googlefonts = array_unique(array_merge($googlefonts,$gfonts));
							}
						}
					}
				}
			}

			// google fonts
			if (!empty($googlefonts)) {
				$this->update_google_fonts($googlefonts);
			}
		}

		/**
		 * Parse stylesheets
		 */
		if (strpos($HTML,'fonts.googleapis.com/css') !== false) {

			$stylesheet_regex = '#(<\!--\[if[^>]+>)?([\s|\n]+)?<link([^>]+)href=[\'|"]([^\'|"]+)[\'|"]([^>]+)?>#is';

			if (preg_match_all($stylesheet_regex,$HTML,$out)) {

				foreach ($out[4] as $n => $file) {

					// verify if file is valid styleshet
					if (trim($out[1][$n]) != '' || strpos($out[3][$n] . $out[5][$n],'stylesheet') === false) {
						$i[] = array($out[3][$n] . $out[5][$n],$file);
						continue;
					}

					// apply css file  filter pre processing
					$filterResult = $this->process_cssfile($file);

					// delete file
					if ($filterResult === 'delete') {

						// delete from HTML
						$search[] = '|<link[^>]+'.preg_quote($file).'[^>]+>|Ui';
						$replace[] = '';
						continue;
					}
				}
			}
		
		}

		if (!empty($search)) {
			$HTML = preg_replace($search,$replace,$HTML);
		}

		return $HTML;
	}

	/**
	 * Replace HTML
	 */
	public function replace_html($searchreplace) {

		list($search, $replace) = $searchreplace;


		/**
		 * Inline Web Font Loading
		 */
		if (isset($this->CTRL->options['gwfo_loadmethod']) && $this->CTRL->options['gwfo_loadmethod'] !== 'inline') {

			/**
			 * Update Web FOnt configuration
			 */
			$search[] = '#' . preg_quote($this->CTRL->optimization->webfont_replacement_string) . '#Ui';
			$replace[] = $this->webfontconfig();
		}

		return array($search, $replace); 
	}

	/**
	 * Update Google fonts
	 */
	public function update_google_fonts($googlefonts) {

		/**
		 * Get current google font configuration
		 */
		$options = get_option( 'abovethefold' );
		$current_googlefonts = $this->googlefonts;

		$new = false; // new fonts?

		foreach ($googlefonts as $googlefont) {
			$googlefont = trim($googlefont);
			if (!in_array($googlefont,$current_googlefonts)) {
				$new = true;
				$current_googlefonts[] = $googlefont;
			}
		}

		/**
		 * Update Google Web Font Configuration
		 */
		if ($new) {
			$options['gwfo_googlefonts'] = array_unique($current_googlefonts);
			update_option('abovethefold',$options);

			$this->CTRL->options['gwfo_googlefonts'] = $options['gwfo_googlefonts'];
			$this->googlefonts = $options['gwfo_googlefonts'];
		}
	}

	/**
	 * Parse Webfont Fonts from link
	 */
	public function fonts_from_url($fontLink) {

		// fonts found in url
		$fonts = array();

		// parse querystring of url
		parse_str(parse_url($fontLink, PHP_URL_QUERY), $urlParameters);

		/**
		 * Custom font
		 */
        if (isset($urlParameters['text'])) {

        	/**
        	 * @todo custom fonts
        	 */

        } else {

        	/**
        	 * Google Font Family config
        	 */
            foreach (explode('|', $urlParameters['family']) as $fontFamilies) {
                $fontFamily = explode(':', $fontFamilies);

                if (isset($urlParameters['subset'])) {
                    # Use the subset parameter for a subset
                    $subset = $urlParameters['subset'];
                } else {
                    if (isset($fontFamily[2])) {
                        # Use the subset in the family string
                        $subset = $fontFamily[2];
                    } else {
                        # Use a default subset
                        $subset = "latin";
                    }
                }

                
                if (strlen($fontFamily[0]) > 0) {

					// initiate google fonts array
					if (!isset($fonts['google'])) {
						$fonts['google'] = array();
					}
					$fonts['google'][] = $fontFamily[0] . ":" . $fontFamily[1] . ":" . $subset;
                }
            }
        }

        return (!empty($fonts)) ? $fonts : false;
	}

	/**
	 * Return WebFontConfig variable
	 */
	public function webfontconfig($json = false) {

		$WebFontConfig = '';

		if (isset($this->CTRL->options['gwfo_config']) && $this->CTRL->options['gwfo_config']) {

			if (!preg_match('|^WebFontConfig\s*=\s*|Ui',$this->CTRL->options['gwfo_config'])) {
				// not valid
			} else {

				$WebFontConfig = trim($this->CTRL->options['gwfo_config']);
			}
		}

		/**
		 * Apply Google Font Remove List
		 * 
		 * @since 2.5.6
		 */
		if (!empty($this->googlefonts) && isset($this->CTRL->options['gwfo_googlefonts_remove']) && !empty($this->CTRL->options['gwfo_googlefonts_remove'])) {

			$googlefonts = array();
			foreach ($this->googlefonts as $font) {

				$remove = false;
				foreach ($this->CTRL->options['gwfo_googlefonts_remove'] as $removeFont) {
					if (strpos($font,$removeFont) !== false) {
						// remove
						$remove = true;
					}
				}
				if (!$remove) {
					$googlefonts[] = $font;
				}
			}

			$this->googlefonts = $googlefonts;
		}

		/**
		 * Add Google Fonts to config
		 */
		if (!empty($this->googlefonts)) {

			$googlefontconfig = array(
				'families' => $this->googlefonts
			);

			if (trim($WebFontConfig) !== '') {

				// WebFontConfig has Google fonts, merge
				if (preg_match('|google\s*:\s*(\{[^\}]+\})|is',$WebFontConfig,$out)) {

					$config = @json_decode($this->fixJSON($out[1]),true);
					if (is_array($config) && isset($config['families'])) {
						$googlefontconfig['families'] = array_unique(array_merge($googlefontconfig['families'],$config['families']));
					}

					$WebFontConfig = preg_replace('|google\s*:\s*(\{[^\}]+\})|is','google:' . json_encode($googlefontconfig),$WebFontConfig);
				}
			} else {
				$WebFontConfig = 'WebFontConfig={google:' . json_encode($googlefontconfig) . '};';
			}
		}

		// no webfont config
		if (empty($WebFontConfig)) {
			return null;
		}

		// return parsed json
		if ($json) {
			return @json_decode(preg_replace('|;$|Ui','',trim($this->fixJSON(preg_replace('|^WebFontConfig\s*=|Ui','',trim($WebFontConfig))))),true);
		}

		$WebFontConfig = trim($WebFontConfig);
		if (!preg_match('|;$|Ui',$WebFontConfig)) {
			$WebFontConfig .= ';';
		}

		// compress code
		$WebFontConfig = preg_replace(array('|\s*=\s*\{\s*|is','|\s*:\s*\{\s*|is','|\s*\{\s*\{\s*|is','|\s*\}\s*\}\s*|is'),array('={',':{','{{','}}'),$WebFontConfig);

		return $WebFontConfig;

	}

	/**
	 * Fix invalid json (single quotes vs double quotes)
	 */
	public function fixJSON($json) {

		$json = preg_replace('#([\s|\{])([a-z\_]+)\s*:\s*([\'|"|\{|\[])#Ui','$1"$2" : $3',$json);
		$json = preg_replace('#([\s|\{])([a-z\_]+)\s*:\s*(false)#Ui','$1"$2" : $3',$json);
		$json = preg_replace('#([\s|\{])([a-z\_]+)\s*:\s*(true)#Ui','$1"$2" : $3',$json);

		$regex = <<<'REGEX'
~
    "[^"\\]*(?:\\.|[^"\\]*)*"
    (*SKIP)(*F)
  | '([^'\\]*(?:\\.|[^'\\]*)*)'
~x
REGEX;
	    return preg_replace_callback($regex, function($matches) {
	        return '"' . preg_replace('~\\\\.(*SKIP)(*F)|"~', '\\"', $matches[1]) . '"';
	    }, $json);
	}

	/**
	 * Enqueue webfont.js
	 */
	public function enqueue_webfontjs() {

		/**
		 * Google Web Font Loader WordPress inlcude
		 */
		$in_footer = (isset($this->CTRL->options['gwfo_loadposition']) && $this->CTRL->options['gwfo_loadposition'] === 'footer') ? true : false;
		wp_enqueue_script( 'abtf_webfontjs', WPABTF_URI . 'public/js/webfont.js', array(), $this->CTRL->gwfo->package_version(), $in_footer );
	}

	/**
	 * Get package version
	 */
	public function package_version($reset = false) {

		if (!$reset) {
			$version = get_option('abtf_webfontjs_version');
			if ($version) {
				return $version;
			}
		}

		// check existence of package file
		$webfont_package = WPABTF_PATH . 'public/js/src/webfontjs_package.json';
		if (!file_exists($webfont_package)) {
			$this->CTRL->admin->set_notice('PLUGIN INSTALLATION NOT COMPLETE, MISSING public/js/src/webfontjs_package.json', 'ERROR');
			return false;
		} else {

			$package = @json_decode(file_get_contents($webfont_package),true);
			if (!is_array($package)) {
				$this->CTRL->admin->set_notice('failed to parse public/js/src/webfontjs_package.json', 'ERROR');
				return false;
			} else {

				$version = update_option('abtf_webfontjs_version', $package['version']);

				// return version
				return $package['version'];
			}
		}
	}

}

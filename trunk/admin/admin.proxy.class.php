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

		// verify preload urls
		if (trim($input['css_proxy_preload']) !== '') {

			$preload_urls = explode("\n",trim($input['css_proxy_preload']));
			$options['css_proxy_preload'] = array();

			if (!empty($preload_urls)) {
				foreach ($preload_urls as $url) {
					$url = trim($url);
					if ($url === '') { continue; }

					// JSON config
					if (substr($url,0,1) === '{') {
						$url_config = @json_decode($url,true);
						if (!is_array($url_config)) {
							$this->CTRL->admin->set_notice('The CSS preload JSON <code>'.htmlentities($url,ENT_COMPAT,'utf-8').'</code> was not recognized as valid JSON.', 'ERROR');
							continue;
						}
						if (!isset($url_config['url'])) {
							$this->CTRL->admin->set_notice('The CSS preload JSON <code>'.htmlentities($url,ENT_COMPAT,'utf-8').'</code> does not contain a target url.', 'ERROR');
							// no target url
							continue 1;
						}

						if (isset($url_config['expire']) && $url_config['expire'] !== '' && !(!is_numeric($url_config['expire']) || intval($url_config['expire']) <= 0)) {
							$this->CTRL->admin->set_notice('The CSS preload JSON <code>'.htmlentities($url,ENT_COMPAT,'utf-8').'</code> contains an invalid expire time.', 'ERROR');
							// invalid expire time
							
							$url_config['expire'] = 86400;
						}

						$options['css_proxy_preload'][] = $url_config;
					} else {

						$options['css_proxy_preload'][] = $url;
					}
				}
			}
		}

		// Javascript proxy
		$options['js_proxy'] = (isset($input['js_proxy']) && intval($input['js_proxy']) === 1) ? true : false;
		$options['js_proxy_include'] = trim($input['js_proxy_include']);
		$options['js_proxy_exclude'] = trim($input['js_proxy_exclude']);

		// verify preload urls
		if (trim($input['js_proxy_preload']) !== '') {

			$preload_urls = explode("\n",trim($input['js_proxy_preload']));
			$options['js_proxy_preload'] = array();

			if (!empty($preload_urls)) {
				foreach ($preload_urls as $url) {
					$url = trim($url);
					if ($url === '') { continue; }

					// JSON config
					if (substr($url,0,1) === '{') {
						$url_config = @json_decode($url,true);
						
						if (!is_array($url_config)) {
							$this->CTRL->admin->set_notice('The Javascript preload JSON <code>'.htmlentities($url,ENT_COMPAT,'utf-8').'</code> was not recognized as valid JSON.', 'ERROR');
							continue;
						}
						if (!isset($url_config['url'])) {
							$this->CTRL->admin->set_notice('The Javascript preload JSON <code>'.htmlentities($url,ENT_COMPAT,'utf-8').'</code> does not contain a target url.', 'ERROR');
							// no target url
							continue 1;
						}

						if (isset($url_config['expire']) && $url_config['expire'] !== '' && !(!is_numeric($url_config['expire']) || intval($url_config['expire']) <= 0)) {
							$this->CTRL->admin->set_notice('The Javascript preload JSON <code>'.htmlentities($url,ENT_COMPAT,'utf-8').'</code> contains an invalid expire time.', 'ERROR');
							// invalid expire time
							$url_config['expire'] = 86400;
						}

						$options['js_proxy_preload'][] = $url_config;
					} else {

						$options['js_proxy_preload'][] = $url;
					}
				}
			}

		}

		// update settings
		$this->CTRL->admin->save_settings($options, 'Proxy settings saved.');

		wp_redirect(admin_url('admin.php?page=abovethefold&tab=proxy'));
		exit;
    }

}
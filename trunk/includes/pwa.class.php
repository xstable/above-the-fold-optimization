<?php

/**
 * Google PWA optimization functions and hooks.
 *
 * This class provides the functionality for Google PWA optimization functions and hooks.
 *
 * @since      2.8.3
 * @package    abovethefold
 * @subpackage abovethefold/includes
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */


class Abovethefold_PWA
{

    /**
     * Above the fold controller
     */
    public $CTRL;

    /**
     * Initialize the class and set its properties
     */
    public function __construct(&$CTRL)
    {
        $this->CTRL =& $CTRL;

        if ($this->CTRL->disabled) {
            return; // above the fold optimization disabled for area / page
        }
    }

    /**
     * Return service worker path and scope
     */
    public function get_sw()
    {
        $path = trailingslashit(ABSPATH);
        $scope = trailingslashit(parse_url(site_url(), PHP_URL_PATH));

        $sw_filename = 'abtf-pwa.js';
        $sw_filename_debug = 'abtf-pwa.debug.js';
        $sw_policy_filename = 'abtf-pwa-policy.json';

        return array(
            'filename' => $sw_filename,
            'filename_debug' => $sw_filename_debug,
            'file' => $path . $sw_filename,
            'file_debug' => $path . $sw_filename_debug,
            'file_policy' => $path . $sw_policy_filename,
            'scope' => $scope
        );
    }

    /**
     * Get Service Worker cache policy
     */
    public function get_sw_policy()
    {
        $cache_policy = array();

        // asset cache
        if (isset($this->CTRL->options['pwa_cache_assets']) && $this->CTRL->options['pwa_cache_assets'] && is_array($this->CTRL->options['pwa_cache_assets_policy'])) {
            $cache_policy = $this->CTRL->options['pwa_cache_assets_policy'];
        } else {
            $cache_policy = array(); // $this->get_sw_default_policy();
        }

        // page cache
        if (isset($this->CTRL->options['pwa_cache_pages']) && $this->CTRL->options['pwa_cache_pages']) {

            // create page cache policy
            $page_cache_policy = array(
                'title' => 'Match pages',
                'match' => array(
                    array( 'type' => 'header', 'name' => 'Accept', 'pattern' => 'text/html')
                ),
                'strategy' => $this->CTRL->options['pwa_cache_pages_strategy'],
                'cache' => array(
                    'store' => 'pages',
                    'conditions' => array(
                        array(
                            'type' => 'header',
                            'name' => 'content-type',
                            'pattern' => 'text/html'
                        )
                    )
                )
            );

            // add URL match based on include list
            if (isset($this->CTRL->options['pwa_cache_pages_include']) && $this->CTRL->options['pwa_cache_pages_include']) {
                $page_cache_policy['match'][] = array(
                    'type' => 'url', 'pattern' => $this->CTRL->options['pwa_cache_pages_include']
                );
            }

            // offline page
            if (isset($this->CTRL->options['pwa_cache_pages_offline']) && $this->CTRL->options['pwa_cache_pages_offline']) {
                $page_cache_policy['offline'] = $this->CTRL->options['pwa_cache_pages_offline'];
            }

            // cache strategy
            if ($this->CTRL->options['pwa_cache_pages_strategy'] === 'cache') {
                if (!isset($page_cache_policy['cache'])) {
                    $page_cache_policy['cache'] = array();
                }

                if (isset($this->CTRL->options['pwa_cache_pages_update_interval']) && $this->CTRL->options['pwa_cache_pages_update_interval']) {
                    $page_cache_policy['cache']['update_interval'] = intval($this->CTRL->options['pwa_cache_pages_update_interval']);
                }
                $page_cache_policy['cache']['head_update'] = (isset($this->CTRL->options['pwa_cache_pages_head_update']) && $this->CTRL->options['pwa_cache_pages_head_update']) ? true : false;

                $page_cache_policy['cache']['notify'] = (isset($this->CTRL->options['pwa_cache_pages_update_notify']) && $this->CTRL->options['pwa_cache_pages_update_notify']) ? true : false;
            }
/*
            if (isset($this->CTRL->options['pwa_cache_pages_preload']) && $this->CTRL->options['pwa_cache_pages_preload']) {
                $pwasettings['pages']['preload'] = $this->CTRL->options['pwa_cache_pages_preload'];
            }
            */

            $cache_policy[] = $page_cache_policy;
        }

        return $cache_policy;
    }

    /**
     * Return default asset cache policy
     */
    public function get_sw_default_policy()
    {

        // default cache policy
        return array(
            array(
                'title' => 'Match images',
                'match' => array(
                    array(
                        'type' => 'header',
                        'name' => 'Accept',
                        'pattern' => 'image/'
                    ),
                    array(
                        'not' => true,
                        'type' => 'header',
                        'name' => 'Accept',
                        'pattern' => 'text/html'
                    ),
                    array(
                        "not" => true,
                        "type" => "url",
                        "pattern" => "google-analytics.com/collect"
                    )
                ),
                'strategy' => 'cache',
                'cache' => array(
                    'store' => 'images',
                    'update_interval' => 3600,
                    'head_update' => true,
                    'conditions' => array(
                        array(
                            'type' => 'header',
                            'name' => 'content-length',
                            'pattern' => array( 'operator' => '<',  'value' => 35840 )
                        )
                    )
                ),
                'offline' => '/path/to/offline.png'
            ),
            array(
                'title' => 'Match assets',
                'match' => array(
                    array(
                        'type' => 'url',
                        'pattern' => '/\.(css|js|woff|woff2|ttf|otf|eot)(\?.*)?$/i',
                        'regex' => true
                    )
                ),
                'strategy' => 'cache',
                'cache' => array(
                    'store' => 'assets',
                    'update_interval' => 300,
                    'head_update' => true,
                    'max_age' => 86400
                )
            )
        );
    }


    /**
     * Javascript client settings
     */
    public function client_jssettings(&$jssettings, &$jsfiles, &$inlineJS, $jsdebug)
    {
        if (!isset($this->CTRL->options['pwa']) || !$this->CTRL->options['pwa']) {
            // disabled
            return;
        }

        // verify if service worker exists
        $sw = $this->get_sw();

        $swfile = ($jsdebug) ? $sw['filename_debug'] : $sw['filename'];
        if (!file_exists($swfile)) {

            // debug file missing, fallback to regular
            if ($jsdebug && file_exists($sw['filename'])) {
                $swfile = $sw['filename'];
            } else {
                // disable
                return;
            }
        }

        // no cache policy file
        if (!file_exists($sw['file_policy'])) {
            // disable
            return;
        }

        $pwaindex = $this->CTRL->optimization->client_config_ref['pwa'];

        if (isset($this->CTRL->options['pwa_scope']) && trim($this->CTRL->options['pwa_scope']) !== '') {
            $scope = $this->CTRL->options['pwa_scope'];
        } else {
            $scope = $sw['scope'];
        }

        $pwasettings = array(
            'path' => $sw['scope'] . $swfile,
            'scope' => $scope,
            'policy' => filemtime($sw['file_policy'])
        );

        // offline class
        if (isset($this->CTRL->options['pwa_offline_class']) && $this->CTRL->options['pwa_offline_class']) {
            $pwasettings['offline_class'] = true;
        }

        // version
        if (isset($this->CTRL->options['pwa_cache_version']) && $this->CTRL->options['pwa_cache_version'] !== '') {
            $pwasettings['version'] = $this->CTRL->options['pwa_cache_version'];
        }

        // version
        if (isset($this->CTRL->options['pwa_cache_max_size']) && $this->CTRL->options['pwa_cache_max_size'] !== '') {
            $pwasettings['max_size'] = $this->CTRL->options['pwa_cache_max_size'];
        }


        $jssettings[$pwaindex] = array();
        foreach ($pwasettings as $key => $value) {
            if (!isset($this->CTRL->optimization->client_config_ref['pwa-sub'][$key])) {
                continue;
            }

            $jssettings[$pwaindex][$this->CTRL->optimization->client_config_ref['pwa-sub'][$key]] = $value;
        }

        ksort($jssettings[$pwaindex]);
        $max = max(array_keys($jssettings[$pwaindex]));
        for ($i = 0; $i <= $max; $i++) {
            if (!isset($jssettings[$pwaindex][$i])) {
                $jssettings[$pwaindex][$i] = 'ABTF-NULL';
            }
        }
        ksort($jssettings[$pwaindex]);
  
        $jsfiles[] = WPABTF_PATH . 'public/js/abovethefold-pwa'.$jsdebug.'.min.js';
    }
}

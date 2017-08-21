/**
 * Above the fold optimization Javascript
 *
 * This javascript handles the CSS delivery optimization.
 *
 * @package    abovethefold
 * @subpackage abovethefold/public
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */
(function(Abtf) {

    if (ABTFDEBUG) {
        console.warn('Abtf', 'debug notices visible to admin only');
    };

    /**
     * Header init
     */
    Abtf.h = function(css) {

        if (Abtf.proxy) {
            Abtf.ps(Abtf.proxy);
        }
        // load scripts in header
        if (Abtf.js && !Abtf.js[1]) {
            Abtf.j(Abtf.js[0]);
        }

        // Google Web Font Loader
        if (typeof Abtf.gwf !== 'undefined') {
            if (Abtf.gwf[0] && !Abtf.gwf[1]) {

                if (Abtf.gwf[0] === 'a') {
                    Abtf.a(Abtf.gwf[2], 'webfont');

                    if (ABTFDEBUG) {
                        console.log('Abtf.fonts()', 'async', WebFontConfig);
                    }

                } else if (typeof WebFont !== 'undefined') {

                    // Convert WebFontConfig object string
                    if (typeof Abtf.gwf[0] === 'string') {
                        Abtf.gwf[0] = eval('(' + Abtf.gwf[0] + ')');
                    }

                    // load WebFontConfig
                    WebFont.load(Abtf.gwf[0]);

                    if (ABTFDEBUG) {
                        console.log('Abtf.fonts()', Abtf.gwf[0]);
                    }
                }
            }
        }
    };

    /**
     * Footer init
     */
    Abtf.f = function(css) {

        // Load CSS
        if (css && Abtf.c) {

            if (ABTFDEBUG) {
                console.log('Abtf.css()', 'footer start');
            }

            Abtf.c();
        }

        // load scripts in footer
        if (Abtf.js && Abtf.js[1]) {

            if (ABTFDEBUG) {
                console.log('Abtf.js()', 'footer start');
            }

            Abtf.j(Abtf.js[0]);
        }

        // Google Web Font Loader
        if (typeof Abtf.gwf !== 'undefined') {
            if (Abtf.gwf[0] && Abtf.gwf[1]) {

                /**
                 * Async
                 */
                if (Abtf.gwf[0] === 'a') {
                    this.a(Abtf.gwf[2], 'webfont');

                    if (ABTFDEBUG) {
                        console.log('Abtf.fonts() [footer]', 'async', WebFontConfig);
                    }

                } else if (typeof WebFont !== 'undefined') {

                    // load WebFontConfig
                    WebFont.load(Abtf.gwf[0]);

                    if (ABTFDEBUG) {
                        console.log('Abtf.fonts() [footer]', Abtf.gwf[0]);
                    }
                }
            }
        }
    };

    /**
     * DomReady
     */
    /*Abtf.r = function(a, b, c) {
        b = document;
        c = 'addEventListener';
        b[c] ? b[c]('DocumentContentLoaded', a) : window.attachEvent('onload', a);
    };*/

    /**
     * Async load script
     */
    Abtf.a = function(scriptFile, id) {
        (function(d) {
            var wf = d.createElement('script');
            wf.src = scriptFile;
            if (id) {
                wf.id = id;
            }
            wf.async = true;
            var s = d.getElementsByTagName('script')[0];
            if (s) {
                s.parentNode.insertBefore(wf, s);
            } else {
                var h = document.head || document.getElementsByTagName("head")[0];
                h.appendChild(wf);
            }
        })(document);
    }

    if (ABTFDEBUG) {

        var SITE_URL = document.createElement('a');
        SITE_URL.href = document.location.href;
        var BASE_URL_REGEX = new RegExp('^(https?:)?//' + SITE_URL.host.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'i');

        /**
         * Return local url for debug notices
         */
        Abtf.localUrl = function(url) {
            return url.replace(BASE_URL_REGEX, '');
        }
    }

})(window.Abtf);
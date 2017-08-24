/**
 * Above the fold optimization Javascript
 *
 * This javascript handles the CSS delivery optimization.
 *
 * @package    abovethefold
 * @subpackage abovethefold/public
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */
(function(window, Abtf, undefined) {

    if (ABTFDEBUG) {
        console.warn('Abtf', 'debug notices visible to admin only');
    };

    // requestAnimationFrame
    var raf = (window.requestAnimationFrame ||
        window.webkitRequestAnimationFrame ||
        window.mozRequestAnimationFrame ||
        window.msRequestAnimationFrame ||
        function(callback) {
            window.setTimeout(callback, 1000 / 60);
        });
    Abtf[CONFIG.RAF] = function() {
        raf.apply(window, arguments);
    };

    // requestIdleCallback, run tasks in CPU idle time
    var id = (window.requestIdleCallback) ? window.requestIdleCallback : false;
    Abtf[CONFIG.IDLE] = (id) ? function() {
        id.apply(window, arguments);
    } : false;

    /**
     * Header init
     */
    Abtf[CONFIG.HEADER] = function(css) {

        if (Abtf[CONFIG.PROXY]) {
            Abtf[CONFIG.PROXY_SETUP](Abtf[CONFIG.PROXY]);
        }
        // load scripts in header
        if (Abtf[CONFIG.JS] && !Abtf[CONFIG.JS][1]) {
            Abtf[CONFIG.LOAD_JS](Abtf[CONFIG.JS][0]);
        }

        // Google Web Font Loader
        if (typeof Abtf[CONFIG.GWF] !== 'undefined') {
            if (Abtf[CONFIG.GWF][0] && !Abtf[CONFIG.GWF][1]) {

                if (Abtf[CONFIG.GWF][0] === 'a') {
                    Abtf[CONFIG.ASYNC](Abtf[CONFIG.GWF][2], 'webfont');

                    if (ABTFDEBUG) {
                        console.log('Abtf.fonts()', 'async', WebFontConfig);
                    }

                } else if (typeof WebFont !== 'undefined') {

                    // Convert WebFontConfig object string
                    if (typeof Abtf[CONFIG.GWF][0] === 'string') {
                        Abtf[CONFIG.GWF][0] = eval('(' + Abtf[CONFIG.GWF][0] + ')');
                    }

                    // load WebFontConfig
                    WebFont.load(Abtf[CONFIG.GWF][0]);

                    if (ABTFDEBUG) {
                        console.log('Abtf.fonts()', Abtf[CONFIG.GWF][0]);
                    }
                }
            }
        }
    };

    /**
     * Footer init
     */
    Abtf[CONFIG.FOOTER] = function(css) {

        // Load CSS
        if (css && Abtf[CONFIG.LOAD_CSS]) {

            if (ABTFDEBUG) {
                console.log('Abtf.css()', 'footer start');
            }

            Abtf[CONFIG.LOAD_CSS]();
        }

        // load scripts in footer
        if (Abtf[CONFIG.JS] && Abtf[CONFIG.JS][1]) {

            if (ABTFDEBUG) {
                console.log('Abtf.js()', 'footer start');
            }

            Abtf[CONFIG.LOAD_JS](Abtf[CONFIG.JS][0]);
        }

        // Google Web Font Loader
        if (typeof Abtf[CONFIG.GWF] !== 'undefined') {
            if (Abtf[CONFIG.GWF][0] && Abtf[CONFIG.GWF][1]) {

                /**
                 * Async
                 */
                if (Abtf[CONFIG.GWF][0] === 'a') {
                    Abtf[CONFIG.ASYNC](Abtf[CONFIG.GWF][2], 'webfont');

                    if (ABTFDEBUG) {
                        console.log('Abtf.fonts() [footer]', 'async', WebFontConfig);
                    }

                } else if (typeof WebFont !== 'undefined') {

                    // load WebFontConfig
                    WebFont.load(Abtf[CONFIG.GWF][0]);

                    if (ABTFDEBUG) {
                        console.log('Abtf.fonts() [footer]', Abtf[CONFIG.GWF][0]);
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
    Abtf[CONFIG.ASYNC] = function(scriptFile, id) {
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
        Abtf[CONFIG.LOCALURL] = function(url) {
            return url.replace(BASE_URL_REGEX, '');
        }
    }

})(window, window.Abtf);
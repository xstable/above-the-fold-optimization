/**
 * loadCSS (installed from bower module)
 *
 * @link https://github.com/filamentgroup/loadCSS/
 *
 * @package    abovethefold
 * @subpackage abovethefold/public
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */

(function(Abtf) {

    Abtf[CONFIG.LOADCSS] = (typeof window.loadCSS !== 'undefined') ? function(href, before, media, callback) {

        if (ABTFDEBUG) {
            console.info('Abtf.css() ➤ loadCSS() async download start', Abtf[CONFIG.LOCALURL](href));
        }
        window.loadCSS(href, before, media, function() {
            if (ABTFDEBUG) {
                console.info('Abtf.css() ➤ loadCSS() render', Abtf[CONFIG.LOCALURL](href));
            }
            if (callback) {
                callback();
            }
        });

    } : function() {};

})(window.Abtf);
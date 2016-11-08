/**
 * Load Javascript asynchronicly
 *
 * @package    abovethefold
 * @subpackage abovethefold/public
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */

(function(window, Abtf, Object) {

    window['Abtf'].js = function(files) {

        if (files === 'ABTF_JS') {
            if (ABTFDEBUG) {
                console.error('Abtf.js()','output buffer failed to apply Javascript optimization');
            }
            return;
        }

        if (ABTFDEBUG) {
            if (!files) {
                return;
            } else {
                console.log('Abtf.js()', files);
            }
        }
        if (!files) {
            return;
        }

        // target for inserting CSS
        var target = (document.getElementById('AbtfCSS')) ? document.getElementById('AbtfCSS').nextSibling : false;

        // load script
        var loadScript = function(scriptPos) {
            if (typeof files[scriptPos] === 'undefined') {
                return;
            }

            if (typeof files[scriptPos] !== 'object') {
                if (ABTFDEBUG) {
                    console.error('Abtf.js()','Invalid Javascript file configuration',scriptPos,files);
                }
                return;
            }

            var script = files[scriptPos];

            // load script
            (function(script,async,scriptPos) {


                if (ABTFDEBUG) {
                    console.info('Abtf.js() > '+((async) ? 'async ' : '') + 'download start', script);
                }

                // load script
                Abtf.loadScript(script, function scriptReady() {

                    if (ABTFDEBUG) {
                        console.info('Abtf.js() > loaded', script);
                    }

                    if (!async) {

                        // continue with next script
                        loadScript(++scriptPos);
                    }
                });

                if (async) {

                    // continue with next script
                    loadScript(++scriptPos);
                }

            })(script[0],((script[1]) ? true : false),scriptPos);

        };

        // start with first script
        loadScript(0);
    };

    /**
     * Object Watch Polyfill
     */
    // object.watch
    if (!Object.prototype.watch) {
        Object.defineProperty(Object.prototype, "watch", {
              enumerable: false
            , configurable: true
            , writable: false
            , value: function (prop, handler) {
                var
                  oldval = this[prop]
                , newval = oldval
                , getter = function () {
                    return newval;
                }
                , setter = function (val) {
                    oldval = newval;
                    return newval = handler.call(this, prop, oldval, val);
                }
                ;
                
                if (delete this[prop]) { // can't watch constants
                    Object.defineProperty(this, prop, {
                          get: getter
                        , set: setter
                        , enumerable: true
                        , configurable: true
                    });
                }
            }
        });
    }

    // object.unwatch
    if (!Object.prototype.unwatch) {
        Object.defineProperty(Object.prototype, "unwatch", {
              enumerable: false
            , configurable: true
            , writable: false
            , value: function (prop) {
                var val = this[prop];
                delete this[prop]; // remove accessors
                this[prop] = val;
            }
        });
    }

})(window, window['Abtf'], Object);
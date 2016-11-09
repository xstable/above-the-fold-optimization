/**
 * Load Javascript asynchronicly
 *
 * @package    abovethefold
 * @subpackage abovethefold/public
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */

(function(window, Abtf, Object) {

    // dependency references
    var DEPENDENCIES = [];

    // dependency group references
    var DEPENDENCY_GROUPS = [];

    // dependency group references
    var DEPENDENCY_GROUP_REFERENCES = {};

    // dependency loaded state
    var DEPENDENCY_LOADED = {};

    if (ABTFDEBUG) {
        var DEPENDENCY_WAIT_NOTICE = {};
    }

    // wait for dependencies to execute callback
    var WAIT_FOR_DEPENDENCIES = function(script,deps,callback) {

        // no dependencies
        if (deps === false || !(deps instanceof Array) || deps.length === 0) {
            callback();
            return;
        }

        var dependencies_ready = true;

        if (ABTFDEBUG) {
            var wait_for = false;
            var wait_for_group = false;
        }

        var l = deps.length;
        for (var i = 0; i < l; i++) {

            // dependency group
            if (DEPENDENCY_GROUPS && DEPENDENCY_GROUPS[deps[i]]) {

                var gl = DEPENDENCY_GROUPS[deps[i]].length;
                for (var gi = 0; gi < gl; gi++) {
                    if (typeof DEPENDENCY_LOADED[DEPENDENCY_GROUPS[deps[i]][gi]] === 'undefined') {
                        dependencies_ready = false;

                        if (ABTFDEBUG) {
                            wait_for = DEPENDENCY_GROUPS[deps[i]][gi];
                            wait_for_group = deps[i];
                        }

                        break;
                    }
                }

                if (!dependencies_ready) {
                    break;
                }

            } else {
                if (typeof DEPENDENCY_LOADED[deps[i]] === 'undefined') {
                    dependencies_ready = false;

                    if (ABTFDEBUG) {
                        wait_for = deps[i];
                    }

                    break;
                }
            }

        }

        if (dependencies_ready === false) {

            if (ABTFDEBUG) {
                if (typeof DEPENDENCY_WAIT_NOTICE[script + ':' + wait_for] === 'undefined') {
                    DEPENDENCY_WAIT_NOTICE[script + ':' + wait_for] = true;

                    var depnames = [];
                    var l = deps.length;
                    for (var i = 0; i < l; i++) {
                        depnames.push((DEPENDENCIES[deps[i]] || deps[i]));
                    }

                    console.info('Abtf.js() > wait for dependency', (DEPENDENCIES[wait_for] || wait_for) + ((DEPENDENCIES[wait_for_group]) ? ' ('+DEPENDENCIES[wait_for_group]+')' : ''), script, depnames);
               }
            }

            // try again
            setTimeout(WAIT_FOR_DEPENDENCIES,0,script,deps,callback);
        } else {
            callback();
        }
    }

    window['Abtf'].js = function(config) {

        if (config === 'ABTF_JS') {
            if (ABTFDEBUG) {
                console.error('Abtf.js()','output buffer failed to apply Javascript optimization');
            }
            return;
        }

        if (typeof config !== 'object' || typeof config[0] === 'undefined' || !config[0]) {
            return;
        }

        var files = config[0];

        // set dependency references
        DEPENDENCIES = (config[1] && config[1] instanceof Array) ? config[1] : [];

        // set dependency group references
        DEPENDENCY_GROUPS = (config[2] && typeof config[2] === 'object') ? config[2] : [];

        if (ABTFDEBUG) {
            console.log('Abtf.js()', files);

            if (DEPENDENCIES) {
                if (DEPENDENCY_GROUPS) {
                    var depgroups = [];
                    for (var group in DEPENDENCY_GROUPS) {
                        if (!DEPENDENCY_GROUPS.hasOwnProperty(group)) { continue; }
                        depgroups.push(DEPENDENCIES[group]);
                    }
                } else { depgroups = false; }

                console.log('Abtf.js() > abide dependencies', DEPENDENCIES, depgroups);
            }
        }

        // target for inserting CSS
        //var target = (document.getElementById('AbtfCSS')) ? document.getElementById('AbtfCSS').nextSibling : false;

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

            var scriptData = files[scriptPos];
            var script = scriptData[0];
            var async = ((scriptData[1]) ? true : false);
            var handle = ((typeof scriptData[2] !== 'undefined') ? scriptData[2] : false);
            var deps = ((scriptData[3]) ? scriptData[3] : false);

            // load script
            var startLoad = function(script,async,handle,deps,scriptPos) {

                if (ABTFDEBUG) {
                    if (deps.length > 0) {
                        var depnames = [];
                        var l = deps.length;
                        for (var i = 0; i < l; i++) {
                            depnames.push((DEPENDENCIES[deps[i]] || deps[i]));
                        }
                        console.info('Abtf.js() > '+((async) ? 'async ' : '') + 'download start', script, (DEPENDENCIES[handle] || handle), depnames);
                    } else {
                        console.info('Abtf.js() > '+((async) ? 'async ' : '') + 'download start', script);
                    }
                }

                // load script
                Abtf.loadScript(script, function scriptReady() {

                    if (ABTFDEBUG) {
                        if (deps.length > 0) {
                            console.info('Abtf.js() > loaded', script, (DEPENDENCIES[handle] || handle), depnames);
                        } else {
                            console.info('Abtf.js() > loaded', script);
                        }
                    }

                    // register dependency load state
                    if (handle !== false) {
                        DEPENDENCY_LOADED[handle] = true;
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

            };

            var handleName = (DEPENDENCIES[handle] || handle);

            // force jquery core dependency for jquery-migrate
            if (handleName === 'jquery-migrate') {
                if (!(deps instanceof Array)) { deps = []; }
                deps.push(DEPENDENCIES.indexOf('jquery-core'));
            }
            if (handleName === 'admin-bar') {
                if (!(deps instanceof Array)) { deps = []; }
                deps.push(DEPENDENCIES.indexOf('jquery'));
            }

            if (deps) {

                WAIT_FOR_DEPENDENCIES(script,deps,function callback() {
                    startLoad(script,async,handle,deps,scriptPos);
                });
            } else {
                startLoad(script,async,handle,deps,scriptPos);
            }

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
/**
 * HTML5 Web Worker and Fetch API based script loader with indexedDB cache
 *
 * Inspired by basket.js
 * @link https://addyosmani.com/basket.js/
 *
 * Using async-local-storage (indexedDB) for storage
 * @link https://github.com/slightlyoff/async-local-storage
 *
 * @since 2.6.5
 * Moved away from localStorage due to pageload blocking, render performance issues and failing on large scripts from +1MB.
 * 
 * @package    abovethefold
 * @subpackage abovethefold/public
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */

(function(window, Abtf) {
    'use strict';

    // test availability of indexedDB
    if (typeof window.indexedDB === "undefined") {
        return false;
    }

    // test availability Web Workers
    if (typeof(window.Worker) === "undefined" || !window.Worker) {
        return false;
    }

    // load promises polyfill
    if (typeof window._promisesPolyfill === 'undefined') {
        return false;
    }
    window._promisesPolyfill(window);

    // load async local storage
    if (typeof window._asyncLocalStorage === 'undefined') {
        return false;
    }
    window._asyncLocalStorage(window, window.navigator||{});

    /**
     * localStorage controller
     */
    var LS = {

        // storage controller
        storage: navigator.storage || navigator.alsPolyfillStorage,

        // Prefix for cache entries
        prefix: 'abtf-',

        // Default expire time in seconds
        default_expire: 86400, // 1 day

        isValidItem: null,

        timeout: 5000,

        // return current time in seconds
        now: function() {
            return (+new Date() / 1000);
        },

        /**
         * Save script to localStorage cache
         */
        saveScript: function( url, scriptData, expire ) {

            var scriptObj = {};

            var now = this.now();
            scriptObj.data = scriptData;
            scriptObj.date = now;
            scriptObj.expire = now + ( expire || LS.default_expire );

            this.add( url, scriptObj );
        },

        /**
         * Get script from localStorage cache
         */
        getScript: function(url) {

            return new Promise(function(resolve, reject) {

                console.log('x2');

                // get from localStorage
                LS.get(url)
                    .then(function(cachedObject) {

                        console.log('x3', cachedObject);

                        if (!cacheObject) {
                            return reject();
                        }

                        // verify expire time
                        if (typeof cacheObject.expire !== 'undefined' && (cacheObject.expire - this.now()) < 0) {
                            return reject();
                        }

                        // create blob url
                        resolve(createBlobUrl(cacheObject.data,'application/javascript'));

                    },reject);
            });

        },

        /**
         * Add data to localStorage cache
         */
        add: function( key, storeObj ) {

            return new Promise(function(resolve, reject) {
                try {
                    LS.storage.set( LS.prefix + key, storeObj )
                        .then(resolve,reject);
                    return true;
                } catch( e ) {

                    reject();
                }
            });
        },

        /**
         * Remove from localStorage
         */
        remove: function( key ) {
            LS.storage.delete( LS.prefix + key );
        },

        /**
         * Get from localStorage
         */
        get: function( key ) {

            // return promise
            return LS.storage.get(LS.prefix + key);
        },

        /**
         * Clear expired entries in localStorage
         */
        clear: function( expired ) {
            var item, key;
            var now = this.now();

            LS.storage.forEach(function(value, key) {

                console.log(key,value,typeof value,value.expire);

                if ( key && ( !expired || value.expire <= now ) ) {
                    LS.storage.delete( key );
                }
            });
        }

    };

    /**
     * Create javascript blob url
     */
    var createBlobUrl = function(fileData,mimeType) {
        var blob;

        /**
         * Create blob
         */
        try {
            blob = new Blob([fileData], {type: mimeType});
        } catch (e) { // Backwards-compatibility
            window.BlobBuilder = window.BlobBuilder || window.WebKitBlobBuilder || window.MozBlobBuilder;
            blob = new BlobBuilder();
            blob.append(fileData);
            blob = blob.getBlob(mimeType);
        }

        /**
         * Return blob url
         */
        return URL.createObjectURL(blob);
    };


    console.log('start');
     LS.storage.set( 'test' ).then(function() {
        console.log(123);

        LS.storage.get('test').then(function(v) {
            console.log('get:',v);
        }, function() {
            console.error('x');
        });
     });

    /**
     * Web Worker source code
     */
    var WORKER_CODE = ((function() {

        // Fetch API
        self.FETCH = self.fetch || false;

        // default xhr timeout
        self.DEFAULT_TIMEOUT = 5000;

        // promises polyfill
        !("{{PROMISES}}")(self);

        // async local storage
        !("{{IDB}}")(self,self.navigator||{});

        /**
         * Method for loading resource
         */
        var LOAD_RESOURCE = function(file) {

            // resource loaded flag
            var resourceLoaded = false;

            // onload callback
            var resourceOnload = function(error,returnData) {
                if (resourceLoaded) {
                    return;  // already processed
                }

                resourceLoaded = true;

                if (request_timeout) {
                    clearTimeout(request_timeout);
                    request_timeout = false;
                }

                RESOURCE_LOAD_COMPLETED(file,error,returnData);
            };

            /**
             * Use Fetch API
             */
            if(FETCH) {

                // fetch configuration
                var fetchInit = { 
                    method: 'GET',
                    mode: 'cors',
                    cache: 'default'
                };

                // fetch request
                FETCH(file.url, fetchInit)
                    .then(function(response) {
                        if (resourceLoaded) {
                            return;  // already processed
                        }

                        // handle response
                        if(response.ok) {

                            // get text data
                            response.text().then(function(data) {
                                resourceOnload(false,data);
                            });

                        } else {

                            // error
                            resourceOnload(response.error());
                        }

                    }).catch(function(error) {
                        if (resourceLoaded) {
                            return;  // already processed
                        }

                        // error
                        resourceOnload(error);
                    });

                // Fetch API does not support abort or cancel or timeout
                // simply ignore the request on timeout
                var timeout = file.timeout || DEFAULT_TIMEOUT;
                if (isNaN(timeout)) {
                    timeout = DEFAULT_TIMEOUT;
                }
                var request_timeout = setTimeout( function requestTimeout() {
                    if (resourceLoaded) {
                        return; // already processed
                    }
                    
                   resourceOnload('timeout');
                }, timeout );
            } else {

                // start XHR request
                var xhr = new XMLHttpRequest();
                xhr.open('GET', file.url, true);

                /**
                 * Set XHR response type
                 */
                xhr.responseType = 'text';

                // watch state change
                xhr.onreadystatechange = function () {
                    if (resourceLoaded) {
                        return;  // already processed
                    }

                    // handle response
                    if (xhr.readyState === 4) {

                        if (xhr.status !== 200) {

                            // error
                            resourceOnload(xhr.statusText);
                        } else {

                            /**
                             * Return text
                             */
                            resourceOnload(false,xhr.responseText);

                        }
                    }
                }
                /**
                 * Resource load completed
                 */
                xhr.onerror = function resourceError() {
                    if (resourceLoaded) {
                        return; // already processed
                    }

                    resourceOnload(xhr.statusText);
                };

                // By default XHRs never timeout, and even Chrome doesn't implement the
                // spec for xhr.timeout. So we do it ourselves.
                var timeout = file.timeout || DEFAULT_TIMEOUT;
                if (isNaN(timeout)) {
                    timeout = DEFAULT_TIMEOUT;
                }
                var request_timeout = setTimeout( function requestTimeout() {
                    if (resourceLoaded) {
                        return; // already processed
                    }
                    try {
                        xhr.abort();
                    } catch(e) {

                    }
                    resourceOnload('timeout');
                }, timeout );

                xhr.send(null);

            }
        };

        /**
         * Post back to UI after completion of specific resource
         */
        self.RESOURCE_LOAD_COMPLETED = function(file,error,returnData) {

            if (error) {
                // return error
                self.postMessage([2,file.i,error]);
            } else {

                // send back data to save in localStorage
                self.postMessage([1,file.i,returnData]);                
            }

        };

        /**
         * Handle load request for web worker
         */
        self.onmessage = function (oEvent) {

            var files = oEvent.data;

            // load multiple files
            if (files instanceof Array) {
                var l = files.length;
                for (var i = 0; i < l; i++) {
                    if (typeof files[i] === 'object' && typeof files[i].url !== 'undefined' && typeof files[i].i !== 'undefined') {
                        LOAD_RESOURCE(files[i]);
                    }
                }
            } else if (typeof files === 'object' && typeof files.url !== 'undefined' && typeof files.i !== 'undefined') {
                LOAD_RESOURCE(files);
            } else {
                throw new Error('Web Worker Script Loader: Invalid resource object');
            }
        }

    }).toString()
        .replace(/"\{\{PROMISES\}\}"/, window._promisesPolyfill.toString())
        .replace(/"\{\{IDB\}\}"/, window._asyncLocalStorage.toString())
        .replace(/^function\s*\(\s*\)\s*\{/,'')
        .replace(/\}$/,'')
    );

    /**
     * Web Worker Script Loader
     */
    var WEBWORKER = {

        // web worker code
        workerUri: createBlobUrl(WORKER_CODE,'application/javascript'),

        // web worker
        worker: false,

        scriptIndex: 0,
        scriptQueue: [],

        // start web worker
        start: function() {

            this.worker = new Worker(this.workerUri);

            // listen for messages from worker
            this.worker.addEventListener('message', this.handleMessage);

            // listen for errors
            this.worker.addEventListener('error',this.handleError);
        },

        /**
         * Stop web worker
         */
        stop: function() {
            if (this.worker) {

                // remove listeners
                this.worker.removeEventListener('message', this.handleMessage);

                // listen for errors
                this.worker.removeEventListener('error',this.handleError);

                // terminate worker
                this.worker.terminate();

                this.worker = false;

                if (ABTFDEBUG) {
                    console.warn('Abtf.js() ➤ web worker terminated');
                }
            }
        },

        /**
         * Handle response from Web Worker
         */
        handleMessage: function(event) {
            var response = event.data;

            var scriptIndex = response[1];
            if (typeof WEBWORKER.scriptQueue[scriptIndex] === 'undefined') {

                // script not in queue
                if (ABTFDEBUG) {
                    console.error('Abtf.js() ➤ web worker script loader invalid response',response);
                }
                return;
            }

            // data is returned
            if (parseInt(response[0]) === 1) {
                WEBWORKER.scriptQueue[scriptIndex].onData(response[2]);
                return;
            }

            // error
            if (parseInt(response[0]) === 2) {
                if (ABTFDEBUG) {
                    console.error('Abtf.js() ➤ web worker script loader error',response[2]);
                }
                return;
            }
        },

        /**
         * Handle error response
         */
        handleError: function(error) {

            // output error to console
            if (ABTFDEBUG) {
                console.error('Abtf.js() ➤ web worker script loader error',error);
            }
        },

        /**
         * Load script
         */
        loadScript: function(url) {

            // start worker
            if (!this.worker) {
                this.start();
            }

            // return promise
            return new Promise(function(resolve, reject) {

                url = window['Abtf'].proxifyScript(url);

                var scriptIndex = parseInt(WEBWORKER.scriptIndex);
                WEBWORKER.scriptIndex++;
                
                // add to queue
                WEBWORKER.scriptQueue[scriptIndex] = {
                    url: url,
                    onData: resolve,
                    onError: reject
                };

                // send to web worker 
                WEBWORKER.worker.postMessage({
                    url: url,
                    i: scriptIndex
                });

            });
        }
    };

    // start web worker
    WEBWORKER.start();

    /**
     * Terminate worker on unload
     */
    window.addEventListener("beforeunload", function (e) {
        WEBWORKER.stop();
    });

    /**
     * Clear expired entries
     */
    LS.clear( true );

    /**
     * Load cached script
     */
    window['Abtf'].loadCachedScript = function (src, callback, context) {

        console.log('logad 1');

        /**
         * Try cache
         */
        LS.getScript(src)
            .then(function cached(cachedUrl) {
        console.log('logad 2');

                // load cache url
                Abtf.loadScript(cachedUrl, callback, context);

            }, function notCached() {

        console.log('logad 3');
                /**
                 * Not in cache, start regular request
                 */
                Abtf.loadScript(src, function scriptLoaded() {

                    /**
                     * Load script into cache in the background
                     */
                    WEBWORKER.loadScript(src)
                        .then(function onData(scriptData) {

                        if (!scriptData) {
                            if (ABTFDEBUG) {
                                console.error('Abtf.js() ➤ web worker script loader no data',Abtf.localUrl(src));
                            }
                            return;
                        }

                        if (ABTFDEBUG) {
                            console.info('Abtf.js() ➤ web worker script loader saved',Abtf.localUrl(src));
                        }

                        // save script to local storage
                        LS.saveScript(src,scriptData);

                    });

                }, context);

            });
        
    };

    /**
     * Load cached script url
     */
    window['Abtf'].cachedScriptUrl = function (src) {

        // return promise
        return new Promise(function(resolve, reject) {

            /**
             * Try cache
             */
            LS.getScript(src)
                .then(resolve,function notCached() {

                    /**
                     * Load script into cache in the background
                     */
                    WEBWORKER.loadScript(src)
                        .then(function onData(scriptData) {

                        if (!scriptData) {
                            if (ABTFDEBUG) {
                                console.error('Abtf.js() ➤ web worker script loader no data',Abtf.localUrl(src));
                            }
                            return;
                        }

                        if (ABTFDEBUG) {
                            console.info('Abtf.js() ➤ web worker script loader saved',Abtf.localUrl(src));
                        }

                        // save script to local storage
                        LS.saveScript(src,scriptData);

                    });

                });
        });
        
    };

})(window, window['Abtf']);
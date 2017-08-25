/**
 * Google PWA + Service Worker caching and offline availability
 *
 * @link https://developers.google.com/web/tools/lighthouse/
 * @link https://developers.google.com/web/fundamentals/getting-started/primers/service-workers
 *
 * @package    abovethefold
 * @subpackage abovethefold/public
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */

(function(window, Abtf) {

    // test availability of serviceWorker
    if (!('serviceWorker' in window.navigator)) {
        return;
    }

    if (!Abtf[CONFIG.PWA] || !Abtf[CONFIG.PWA][CONFIG.PWA_PATH]) {
        return;
    }

    // Google PWA config
    var PWA_CONFIG = Abtf[CONFIG.PWA];

    // service worker ready state
    var READY = false;

    /**
     * Mark online/offline status by CSS class on <body>
     */
    if (PWA_CONFIG[CONFIG.PWA_OFFLINE_CLASS]) {

        var ONLINE;
        var UPDATE_ONLINE_STATUS = function() {
            Abtf[CONFIG.RAF](function() {
                if (ONLINE === navigator.onLine) {
                    return;
                }

                // update class
                if (navigator.onLine) {
                    if (typeof ONLINE === 'undefined') {
                        return;
                    }

                    if (ABTFDEBUG) {
                        console.info('Abtf.offline() ➤ connection restored');
                    }

                    window.jQuery('body').removeClass('offline');
                } else {

                    if (ABTFDEBUG) {
                        console.warn('Abtf.offline() ➤ connection offline');
                    }

                    window.jQuery('body').addClass('offline');
                }
                ONLINE = (navigator.onLine) ? true : false;
            });
        };
        window.addEventListener('online', UPDATE_ONLINE_STATUS);
        window.addEventListener('offline', UPDATE_ONLINE_STATUS);
        UPDATE_ONLINE_STATUS();
    }

    /**
     * Post config to service worker
     */
    var POST_CONFIG = function() {
        navigator.serviceWorker.controller.postMessage([1,
            Abtf[CONFIG.PWA][CONFIG.PWA_POLICY],
            Abtf[CONFIG.PWA][CONFIG.PWA_VERSION],
            Abtf[CONFIG.PWA][CONFIG.PWA_MAX_SIZE]
        ]);

        // preload assets
        if (PWA_CONFIG[CONFIG.PWA_PRELOAD]) {
            if (ABTFDEBUG) {
                console.info('Abtf.pwa() ➤ preload', PWA_CONFIG[CONFIG.PWA_PRELOAD]);
            }

            navigator.serviceWorker.controller.postMessage([2, PWA_CONFIG[CONFIG.PWA_PRELOAD]]);
        }
    }

    /**
     * Wait for Service Worker controller
     */
    navigator.serviceWorker.ready.then(function() {
        if (navigator.serviceWorker.controller) {
            POST_CONFIG();
        } else {
            navigator.serviceWorker.addEventListener('controllerchange', function() {
                POST_CONFIG();
            });
        }
    });

    /**
     * Register Service Worker
     */
    navigator.serviceWorker.register(PWA_CONFIG[CONFIG.PWA_PATH], {
            scope: PWA_CONFIG[CONFIG.PWA_SCOPE]
        })
        .then(function waitUntilInstalled(registration) {
            return new Promise(function(resolve, reject) {
                if (registration.installing) {
                    registration.installing.addEventListener('statechange', function(e) {
                        if (e.target.state == 'installed') {
                            resolve();
                        } else if (e.target.state == 'redundant') {
                            reject();
                        }
                    });
                } else {
                    resolve();
                }
            });
        })
        .then(function() {
            if (ABTFDEBUG) {
                console.info('Abtf.pwa() ➤ service worker loaded');
            }
            READY = true;
        })
        .catch(function(error) {
            throw error;
        });

    /**
     * Listen for messages from Service Worker
     */
    navigator.serviceWorker.addEventListener('message', function(event) {

        // command data from PWA SW
        if (event && event.data && event.data instanceof Array) {

            // asset updated
            if (event.data[0] === 2) {
                window.jQuery('body').trigger('sw-update', event.data[1]);
            }
        }

    });


    /**
     * Install offline
     */
    var OFFLINE = function(url) {
        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage([2, url]);
        } else {
            navigator.serviceWorker.ready.then(function() {
                OFFLINE(url);
            });
        }
    }

    // public method
    Abtf.offline = function(url) {
        OFFLINE(url);
    }

})(window, window.Abtf);
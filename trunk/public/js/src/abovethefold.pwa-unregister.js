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

    // PWA is disabled, try to unregister an installed service worker
    if (!Abtf[CONFIG.PWA]) {

        var UNREGISTER = function() {
            try {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    for (var registration in registrations) {
                        if (registrations.hasOwnProperty(registration) && typeof registration.unregister === 'function') {

                            if (ABTFDEBUG) {
                                console.warn('Abtf.pwa() ➤ unregister Service Worker', registration);
                            }
                            registration.unregister();
                        }
                    }
                });
            } catch (e) {

            }
        }

        window.addEventListener('load', function() {
            if (Abtf[CONFIG.IDLE]) {
                Abtf[CONFIG.IDLE](UNREGISTER);
            } else {
                UNREGISTER();
            }
        });

    }

})(window, window.Abtf);
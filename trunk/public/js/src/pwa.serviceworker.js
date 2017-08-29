/**
 * Above the fold optimization Service Worker / Google PWA
 *
 * @package    abovethefold
 * @subpackage abovethefold/public
 * @author     PageSpeed.pro <info@pagespeed.pro>
 */
(function(self, fetch, Cache) {

    // ABTF Service Worker / PWA config
    var PWA_POLICY = false;
    var PWA_CONFIG_TIMESTAMP = false;
    var PWA_CONFIG_URL = './abtf-pwa-config.json';
    var PWA_CACHE;
    var PWA_CACHE_MAX_SIZE = 1000; // default

    // Via https://github.com/coonsta/cache-polyfill/blob/master/dist/serviceworker-cache-polyfill.js
    // Adds in some functionality missing in Chrome 40.
    if (!Cache.prototype.add) {
        Cache.prototype.add = function add(request) {
            return this.addAll([request]);
        };
    }

    if (!Cache.prototype.addAll) {
        Cache.prototype.addAll = function addAll(requests) {
            var cache = this;

            // Since DOMExceptions are not constructable:
            function NetworkError(message) {
                this.name = 'NetworkError';
                this.code = 19;
                this.message = message;
            }
            NetworkError.prototype = Object.create(Error.prototype);

            return Promise.resolve().then(function() {
                if (arguments.length < 1) throw new TypeError();

                // Simulate sequence<(Request or USVString)> binding:
                var sequence = [];

                requests = requests.map(function(request) {
                    if (request instanceof Request) {
                        return request;
                    } else {
                        return String(request); // may throw TypeError
                    }
                });

                return Promise.all(
                    requests.map(function(request) {
                        if (typeof request === 'string') {
                            request = new Request(request);
                        }

                        var scheme = new URL(request.url).protocol;

                        if (scheme !== 'http:' && scheme !== 'https:') {
                            throw new NetworkError("Invalid scheme");
                        }

                        return fetch(request.clone()).catch(function(error) {
                            throw error;
                        });;
                    })
                );
            }).then(function(responses) {
                // TODO: check that requests don't overwrite one another
                // (don't think this is possible to polyfill due to opaque responses)
                return Promise.all(
                    responses.map(function(response, i) {
                        return cache.put(requests[i], response);
                    })
                );
            }).then(function() {
                return undefined;
            });
        };
    }

    if (!CacheStorage.prototype.match) {
        // This is probably vulnerable to race conditions (removing caches etc)
        CacheStorage.prototype.match = function match(request, opts) {
            var caches = this;

            return this.keys().then(function(cacheNames) {
                var match;

                return cacheNames.reduce(function(chain, cacheName) {
                    return chain.then(function() {
                        return match || caches.open(cacheName).then(function(cache) {
                            return cache.match(request, opts);
                        }).then(function(response) {
                            match = response;
                            return match;
                        });
                    });
                }, Promise.resolve());
            });
        };
    }

    // Install
    self.addEventListener('install', function(event) {

        // fetch policy config
        event.waitUntil(
            UPDATE_CONFIG().then(function() {
                self.skipWaiting();
            }).catch(function() {
                self.skipWaiting();
            })
        );
    });

    // Activate
    self.addEventListener('activate', function(event) {
        event.waitUntil(self.clients.claim());
    });

    /* 
     * Get policy config
     */
    var PWA_CONFIG_UPDATE_RUNNING;
    var GET_POLICY = function(timestamp) {
        return new Promise(function(resolve, reject) {

            // Update config
            if (!PWA_POLICY || !PWA_CONFIG_TIMESTAMP || (timestamp && timestamp > PWA_CONFIG_TIMESTAMP)) {

                // resolve after update?
                var doResolve = (PWA_POLICY) ? false : true;

                UPDATE_CONFIG().then(function() {
                    if (doResolve) {
                        if (PWA_POLICY) {
                            resolve(PWA_POLICY);
                        } else {
                            resolve(false);
                        }
                    }
                }).catch(function() {
                    if (doResolve) {
                        resolve(false);
                    }
                });

            } else if (!PWA_CONFIG_UPDATE_RUNNING && PWA_CONFIG_TIMESTAMP < (TIMESTAMP() - 300)) {

                // HTTP HEAD based update

                PWA_CONFIG_UPDATE_RUNNING = true;

                // verify last-modified header once per 5 minutes
                // HEAD request
                var headRequest = new Request(PWA_CONFIG_URL + '?' + Math.round(Date.now() / 1000), {
                    method: 'HEAD',
                    mode: 'no-cors'
                });

                fetch(headRequest)
                    .then(function(headResponse) {
                        PWA_CONFIG_UPDATE_RUNNING = false;

                        var update = true;
                        if (headResponse && headResponse.ok) {
                            var lastModified = UNIX_TIMESTAMP(headResponse.headers.get('last-modified'));
                            if (lastModified && lastModified <= PWA_CONFIG_TIMESTAMP) {
                                update = false;
                            }
                        }

                        if (update) {
                            // config modified, update
                            UPDATE_CONFIG();
                        }
                    }).catch(function(error) {
                        PWA_CONFIG_UPDATE_RUNNING = false;
                        UPDATE_CONFIG();
                    });
            } else {

                // instantly resolve
                resolve(PWA_POLICY);
            }

        }).catch(function(error) {
            setTimeout(function() {
                throw error;
            });
        });
    }

    /* 
     * Update config
     */
    var UPDATE_CONFIG_LAST = false;
    var UPDATE_CONFIG = function() {

        // retry once per 10 seconds
        if (PWA_CONFIG_UPDATE_RUNNING || (UPDATE_CONFIG_LAST && UPDATE_CONFIG_LAST > (TIMESTAMP() - 10))) {
            return Promise.resolve();
        }

        PWA_CONFIG_UPDATE_RUNNING = true;

        return fetch(PWA_CONFIG_URL + '?' + Math.round(Date.now() / 1000), {
                mode: 'no-cors'
            })
            .then(function(response) {
                PWA_CONFIG_UPDATE_RUNNING = false;
                if (response && response.ok && response.status < 400) {
                    return response.json().then(function(pwaConfig) {

                        if (ABTFDEBUG) {
                            console.info('Abtf.sw() ➤ config ' + ((!PWA_POLICY) ? 'loaded' : 'updated'), pwaConfig);
                        }

                        if (!pwaConfig) {
                            return;
                        }

                        // < v2.8.5 abtf-pwa-policy.json
                        if (pwaConfig instanceof Array) {
                            pwaConfig = {
                                policy: pwaConfig
                            };
                        }

                        // setup policy
                        if (pwaConfig.policy) {
                            PWA_POLICY = pwaConfig.policy;
                            PWA_CONFIG_TIMESTAMP = TIMESTAMP();
                        }

                        // preload
                        var preload = [];

                        // start url
                        if (pwaConfig.start_url) {
                            preload.push(pwaConfig.start_url);
                        }

                        // precache offline pages
                        if (pwaConfig.policy) {
                            pwaConfig.policy.forEach(function(policy) {
                                if (!policy.offline) {
                                    return;
                                }
                                if (preload.indexOf(policy.offline) === -1) {
                                    preload.push(policy.offline);
                                }
                            });
                        }

                        // preload list
                        if (pwaConfig.preload) {
                            pwaConfig.preload.forEach(function(url) {
                                if (preload.indexOf(url) === -1) {
                                    preload.push(url);
                                }
                            });
                        }

                        // preload resources
                        preloadPromises = [];
                        preload.forEach(function(url) {
                            preloadPromises.push(CACHE_PRELOAD(url));
                        });

                    });
                }

                PWA_POLICY = false;

                throw new Error('service worker config not found: ' + PWA_CONFIG_URL);

            }).catch(function(error) {
                PWA_CONFIG_UPDATE_RUNNING = false;
                PWA_POLICY = false;
                setTimeout(function() {
                    throw error;
                });
            });
    }

    // return timestamp
    var TIMESTAMP = function() {
        return Math.round(Date.now() / 1000);
    }

    // parse last-modified header
    var UNIX_TIMESTAMP = function(value) {
        if (!value) {
            return;
        }
        if (isNaN(parseInt(value))) {
            value = Date.parse(value);
            if (!isNaN(value)) {
                return Math.round(value / 1000);
            } else {
                return;
            }
        }
        return value;
    }

    /**
     * Return regular expression from string
     */
    var REGEX_MATCH_PATTERN = /^\/(.*)\/([gimuy]+)?$/;
    var REGEX = function(string) {
        var match = string.match(REGEX_MATCH_PATTERN);
        if (!match) {
            return;
        }
        try {
            var regex = new RegExp(match[1], match[2]);
        } catch (err) {}
        return regex || false;
    }

    /**
     * Clean caches
     */
    var CLEAN_CACHE_LAST_TIMESTAMP = false;
    var CLEAN_CACHE_RUNNING = false;
    var CLEAN_CACHE_TIMEOUT;

    // init
    var INIT_CLEAN_CACHE = function() {
        if (CLEAN_CACHE_TIMEOUT) {
            try {
                clearTimeout(CLEAN_CACHE_TIMEOUT);
            } catch (e) {}
        }
        CLEAN_CACHE_TIMEOUT = setTimeout(CLEAN_CACHE, 500);
    }

    var CLEAN_CACHE = function() {

        if (CLEAN_CACHE_RUNNING) {
            return;
        }

        // start cache cleanup
        if (!CLEAN_CACHE_LAST_TIMESTAMP || CLEAN_CACHE_LAST_TIMESTAMP < (TIMESTAMP() - 10)) {
            CLEAN_CACHE_RUNNING = true;
            CLEAN_CACHE_LAST_TIMESTAMP = TIMESTAMP();

            // open all caches
            caches.keys()
                .then(function(cacheNames) {
                    if (!cacheNames || cacheNames.length === 0) {
                        return Promise.resolve();
                    }
                    return Promise.all(
                        cacheNames.map(function(cacheName) {

                            // delete old cache
                            if (cacheName.indexOf(PWA_CACHE) !== 0) {
                                if (ABTFDEBUG) {
                                    console.info('Abtf.sw() ➤ old cache deleted', cacheName);
                                }
                                return caches.delete(cacheName);
                            } else {

                                // prune cache
                                caches.open(cacheName)
                                    .then(function(cache) {
                                        cache.keys()
                                            .then(function(keys) {

                                                if (ABTFDEBUG) {
                                                    console.info('Abtf.sw() ➤ prune cache', cacheName, 'size:', keys.length, PWA_CACHE_MAX_SIZE);
                                                }

                                                // prune cache when over max size
                                                if (keys.length < PWA_CACHE_MAX_SIZE) {
                                                    return;
                                                }

                                                var sorted = [];

                                                var cacheRequests = [];
                                                var cacheResponses = [];

                                                // read responses from cache for header verification
                                                keys.forEach(function(request) {
                                                    cacheRequests.push(request);
                                                    cacheResponses.push(cache.match(request));
                                                });

                                                // process response data
                                                return Promise.all(cacheResponses)
                                                    .then(function(responses) {

                                                        var now = TIMESTAMP();

                                                        responses.forEach(function(response, key) {

                                                            if (response && response.headers) {
                                                                var timestamp = response.headers.get('x-abtf-sw');

                                                                if (timestamp) {
                                                                    var max_age = response.headers.get('x-abtf-sw-expire');
                                                                    if (max_age) {
                                                                        if (timestamp && timestamp < (TIMESTAMP() - max_age)) {
                                                                            if (ABTFDEBUG) {
                                                                                console.info('Abtf.sw() ➤ cache ➤ expired', response.url);
                                                                            }
                                                                            cache.delete(cacheRequests[key]);
                                                                            return;
                                                                        }
                                                                    }
                                                                } else {
                                                                    timestamp = now;
                                                                }
                                                                if (sorted !== false) {
                                                                    sorted.push({
                                                                        t: timestamp,
                                                                        r: cacheRequests[key]
                                                                    });
                                                                }
                                                            }
                                                        });

                                                        if (sorted && sorted.length > PWA_CACHE_MAX_SIZE) {

                                                            // sort based on timestamp
                                                            sorted.sort(function(a, b) {
                                                                if (a.t > b.t) {
                                                                    return -1
                                                                } else if (a.t < b.t) {
                                                                    return 1
                                                                }
                                                                return 0;
                                                            });
                                                            var prune = sorted.slice(PWA_CACHE_MAX_SIZE);
                                                            prune.forEach(function(obj) {
                                                                cache.delete(obj.r);
                                                            });
                                                        }

                                                        return;
                                                    });
                                            });
                                    });
                            }

                        })
                    ).then(function() {
                        CLEAN_CACHE_RUNNING = false;
                    });
                });
        }
    }

    /**
     * Fetch asset
     */
    var FETCH = function(request, cachePolicy, fallback) {

        return fetch(request)
            .then(function(response) {

                // valid response
                if (response.ok && response.status < 400) {

                    // update cache
                    if (cachePolicy) {

                        var shouldCache = true;

                        // cache conditions
                        if (cachePolicy.conditions) {
                            cachePolicy.conditions.forEach(function(rule) {
                                if (!shouldCache) {
                                    return;
                                }

                                switch (rule.type) {
                                    case "url":
                                        if (rule.regex) {
                                            var regex = REGEX(rule.pattern);
                                            if (!regex) {
                                                shouldCache = false;
                                            } else {
                                                var match = regex.test(request.url);
                                                if (rule.not) {
                                                    if (match) {
                                                        shouldCache = false;
                                                    }
                                                } else if (!match) {
                                                    shouldCache = false;
                                                }
                                            }
                                        } else {
                                            var match = (request.url.indexOf(rule.pattern) !== -1);
                                            if (rule.not) {
                                                if (match) {
                                                    shouldCache = false;
                                                }
                                            } else if (!match) {
                                                shouldCache = false;
                                            }
                                        }
                                        break;
                                    case "header":

                                        var value = response.headers.get(rule.name);
                                        if (!value) {
                                            shouldCache = false;
                                        } else {
                                            if (rule.regex) {
                                                var regex = REGEX(rule.pattern);
                                                if (!regex) {
                                                    shouldCache = false;
                                                } else {
                                                    var match = regex.test(value);
                                                    if (rule.not) {
                                                        if (match) {
                                                            shouldCache = false;
                                                        }
                                                    } else if (!match) {
                                                        shouldCache = false;
                                                    }
                                                }
                                            } else if (typeof rule.pattern === 'object') {

                                                // comparison match
                                                if (rule.pattern.operator) {

                                                    value = parseFloat(value);
                                                    var pattern = parseFloat(rule.pattern.value);

                                                    if (isNaN(value) || isNaN(pattern)) {
                                                        shouldCache = false;
                                                    } else {

                                                        // numeric operator comparison
                                                        switch (rule.pattern.operator) {
                                                            case "<":
                                                                var match = (value < pattern);
                                                                break;
                                                            case ">":
                                                                var match = (value > pattern);
                                                                break;
                                                            case "=":
                                                                var match = (value === pattern);
                                                                break;
                                                            default:
                                                                shouldCache = false;
                                                                break;
                                                        }

                                                        // process match
                                                        if (shouldCache) {
                                                            if (rule.not) {
                                                                if (match) {
                                                                    shouldCache = false;
                                                                }
                                                            } else if (!match) {
                                                                shouldCache = false;
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    shouldCache = false;
                                                }
                                            } else if (value.indexOf(rule.pattern) === -1) {
                                                shouldCache = false;
                                            }
                                        }
                                        break;
                                }
                            });

                            if (ABTFDEBUG) {
                                if (!shouldCache) {
                                    console.info('Abtf.sw() ➤ cache condition ➤ no cache', request.url, cachePolicy.conditions);
                                } else {
                                    console.info('Abtf.sw() ➤ cache condition ➤ cache', request.url, cachePolicy.conditions);
                                }
                            }
                        }

                        if (shouldCache) {
                            CACHE_SET(request, response.clone(), cachePolicy);
                        }
                    }
                }

                return response; // return response
            })
            .catch(function(error) {
                return (fallback) ? fallback(request, null, error) : null;
            });
    }

    /**
     * Fetch asset
     */
    var HEAD_UPDATE = function(request, cachePolicy, cacheResponse, afterUpdate) {

        // verify if cache entry has verifyable headers
        var etag = cacheResponse.headers.get('etag');
        var lastmodified = UNIX_TIMESTAMP(cacheResponse.headers.get('last-modified'));
        if (!etag && !lastmodified) {

            if (ABTFDEBUG) {
                console.warn('Abtf.sw() ➤ HEAD ➤ no etag or last-modified', request.url);
            }

            // initiate request
            var fetchRequest = FETCH(request, cachePolicy);

            // process update
            if (afterUpdate) {
                fetchRequest = fetchRequest.then(afterUpdate);
            }
            return fetchRequest;
        }

        // HEAD request
        var headRequest = new Request(request.url, {
            method: 'HEAD',
            headers: request.headers,
            mode: 'no-cors'
        });

        fetch(headRequest)
            .then(function(headResponse) {

                var update = false;

                // verify headers
                var headEtag = headResponse.headers.get('etag');
                var headLastmodified = UNIX_TIMESTAMP(headResponse.headers.get('last-modified'));
                if (headEtag && headEtag !== etag) {
                    update = true;
                } else if (headLastmodified && headLastmodified !== lastmodified) {
                    update = true;
                }

                // update cache
                if (update) {

                    if (ABTFDEBUG) {
                        console.info('Abtf.sw() ➤ HEAD ➤ update', request.url);
                    }

                    // initiate request
                    var fetchRequest = FETCH(request, cachePolicy);

                    // process update
                    if (afterUpdate) {
                        fetchRequest = fetchRequest.then(afterUpdate);
                    }
                    return fetchRequest;
                } else {
                    return null;
                }

            }).catch(function(error) {

                // fallback to regular fetch
                var fetchRequest = FETCH(request, cachePolicy);

                // process update
                if (afterUpdate) {
                    fetchRequest = fetchRequest.then(afterUpdate);
                }
                return fetchRequest;
            });
    };

    /**
     * Return offline page
     */
    var OFFLINE = function(offline, originalRequest) {
        offline = new Request(offline);
        return CACHE_GET(offline).then(function(response) {
            if (response) {
                return response.blob().then(function(body) {
                    return new Response(body, {
                        status: 503,
                        statusText: 'Offline',
                        headers: response.headers
                    });
                });
            }
            return fetch(originalRequest).catch(function(error) {
                throw error;
            });;
        });
    };

    /**
     * Store response in cache
     */
    var CACHE_GET = function(request) {

        // open cache
        return caches.open(PWA_CACHE)
            .then(function(cache) {

                // return cached response
                return cache.match(request)
                    .then(function(cacheResponse) {

                        // verify if cache is expired
                        if (cacheResponse) {
                            var maxAge = cacheResponse.headers.get('x-abtf-sw-expire');
                            if (maxAge) {
                                var cacheAge = cacheResponse.headers.get('x-abtf-sw');
                            }
                            var expire = cacheResponse.headers.get('expire');
                            if (expire) {
                                expire = UNIX_TIMESTAMP(expire);
                            }
                            if (maxAge && cacheAge < (TIMESTAMP() - maxAge)) {
                                cacheResponse = false;

                                if (ABTFDEBUG) {
                                    console.info('Abtf.sw() ➤ cache expired by policy', request.url, 'max age:', maxAge);
                                }
                            } else if (expire && expire < TIMESTAMP()) {
                                cacheResponse = false;

                                if (ABTFDEBUG) {
                                    console.info('Abtf.sw() ➤ cache expired by HTTP expire', request.url, cacheResponse.headers.get('expire'));
                                }
                            }
                        }

                        return cacheResponse;
                    });
            });
    }

    /**
     * Verify if cache is expired
     */
    var CACHE_EXPIRED = function(cacheResponse) {

        // open cache
        return caches.open(PWA_CACHE)
            .then(function(cache) {

                // return cached response
                return cache.match(request);
            });
    }

    /**
     * Preload cache
     */
    var CACHE_PRELOAD = function(request) {
        if (!request) {
            return;
        }
        if (typeof request === 'string') {
            request = new Request(request, {
                mode: 'no-cors'
            });
        }

        // open cache
        return CACHE_GET(request)
            .then(function(response) {
                if (!response) {

                    if (ABTFDEBUG) {
                        console.info('Abtf.sw() ➤ preload', request.url);
                    }

                    return FETCH(request, {
                        conditions: null
                    });
                }
                return response;
            });
    }

    /**
     * Delete cache
     */
    var CACHE_DELETE = function(requests) {

        // open cache
        return caches.open(PWA_CACHE)
            .then(function(cache) {

                if (!(requests instanceof Array)) {
                    requests = [requests];
                }

                var deletePromises = [];
                requests.forEach(function(request) {
                    deletePromises.push(cache.delete(request));
                });

                return Promise.all(deletePromises);
            });
    }

    /**
     * Store response in cache
     */
    var CACHE_SET = function(request, response, cachePolicy) {

        // open cache
        caches.open(PWA_CACHE)
            .then(function(cache) {

                // parse headers
                var headers = {};
                response.headers.forEach(function(value, key) {
                    headers[key] = value;
                });

                // add timestamp
                headers['x-abtf-sw'] = TIMESTAMP();
                if (cachePolicy && cachePolicy.max_age) {
                    headers['x-abtf-sw-expire'] = cachePolicy.max_age;
                }

                // read response
                response.blob().then(function(body) {

                    // create cache response with modified headers
                    var cacheResponse = new Response(body, {
                        status: response.status,
                        statusText: response.statusText,
                        headers: headers
                    });

                    cache.put(request, cacheResponse);
                });

            });
    }

    // process fetch request
    self.addEventListener('fetch', function(event) {

        // don't touch non-get requests
        if (event.request.method !== 'GET') {
            return;
        }

        // ignore WordPress admin / login pages
        var wp_ignore = false;

        // paths to ignore from root
        var root_paths = ['wp-admin/', 'wp-login.php'];
        root_paths.forEach(function(path) {
            if (wp_ignore) {
                return;
            }
            var regex = new RegExp('^([^/]+)?//' + self.location.host + '(:[0-9]+)?/' + path);
            if (regex.test(event.request.url) || (event.request.referrer && regex.test(event.request.referrer))) {
                wp_ignore = true;
            }
        });

        // ignore
        if (
            wp_ignore ||

            // preview pages
            event.request.url.match(/\&preview=true/) ||
            event.request.url.match(/\&preview_nonce=/)
        ) {
            return;
        }

        // init config
        GET_POLICY();

        // not yet configured / get policy config
        if (!PWA_POLICY || !PWA_CACHE) {
            return;
        }

        // match request against policy list
        var matchPolicy = function(event, policyList) {

            // no cache policy
            if (!policyList || policyList.length === 0) {
                return false;
            }

            // cache maintenance
            INIT_CLEAN_CACHE();

            // matched policy
            var policyMatch = false;

            // match policies to request
            policyList.forEach(function(policy) {
                if (policyMatch || !policy.match || policy.match.length === 0) {
                    return;
                }

                var isMatch = true;

                policy.match.forEach(function(rule) {

                    if (!isMatch) {
                        return;
                    }

                    switch (rule.type) {
                        case "url":
                            if (rule.regex) {
                                var regex = REGEX(rule.pattern);
                                if (!regex) {
                                    isMatch = false;
                                } else {
                                    var match = regex.test(event.request.url);
                                    if (rule.not) {
                                        if (match) {
                                            isMatch = false;
                                        }
                                    } else if (!match) {
                                        isMatch = false;
                                    }
                                }
                            } else {

                                // multiple URL match (page include list)
                                if (rule.pattern instanceof Array) {
                                    var matchingUrl = false;
                                    rule.pattern.forEach(function(pattern) {
                                        if (matchingUrl) {
                                            return;
                                        }
                                        var match = (event.request.url.indexOf(pattern) !== -1);
                                        if (match) {
                                            matchingUrl = true;
                                        }
                                    });
                                    if (rule.not) {
                                        if (matchingUrl) {
                                            isMatch = false;
                                        }
                                    } else if (!matchingUrl) {
                                        isMatch = false;
                                    }
                                } else {

                                    var match = (event.request.url.indexOf(rule.pattern) !== -1);
                                    if (rule.not) {
                                        if (match) {
                                            isMatch = false;
                                        }
                                    } else if (!match) {
                                        isMatch = false;
                                    }
                                }
                            }
                            break;
                        case "header":

                            // process special headers
                            switch (rule.name.toLowerCase()) {

                                // allow HTTP referer matching
                                case "referer":
                                case "referrer":
                                    var value = event.request.referrer;
                                    break;
                                default:
                                    var value = event.request.headers.get(rule.name);
                                    break;
                            }

                            if (!value) {
                                if (!rule.not) {
                                    isMatch = false;
                                }
                            } else {
                                if (rule.regex) {
                                    var regex = REGEX(rule.pattern);
                                    if (!regex) {
                                        isMatch = false;
                                    } else {
                                        var match = regex.test(value);
                                        if (rule.not) {
                                            if (match) {
                                                isMatch = false;
                                            }
                                        } else if (!match) {
                                            isMatch = false;
                                        }
                                    }
                                } else {
                                    var match = (value.indexOf(rule.pattern) !== -1);
                                    if (rule.not) {
                                        if (match) {
                                            isMatch = false;
                                        }
                                    } else if (!match) {
                                        isMatch = false;
                                    }
                                }
                            }

                            break;
                    }
                });

                if (isMatch) {
                    policyMatch = policy;
                }
            });

            if (!policyMatch) {
                if (ABTFDEBUG) {
                    console.info('Abtf.sw() ➤ policy ➤ no match', event.request.url);
                }
                return false;
            }

            if (ABTFDEBUG) {
                console.info('Abtf.sw() ➤ policy ➤ match', event.request.url, policyMatch);
            }

            switch (policyMatch.strategy) {
                case "never":
                    return false;
                    break;
                case "cache":
                    // try cache
                    return CACHE_GET(event.request)
                        .then(function(cacheResponse) {

                            // return cache
                            if (cacheResponse) {

                                var updateCache = true;

                                // update interval
                                if (policyMatch.cache.update_interval) {
                                    var interval = (isNaN(parseInt(policyMatch.cache.update_interval))) ? false : parseInt(policyMatch.cache.update_interval);
                                } else {
                                    var interval = false;
                                }
                                if (interval) {

                                    // verify cache date
                                    var cache_time = cacheResponse.headers.get('x-abtf-sw');
                                    if (cache_time && parseInt(cache_time) > (TIMESTAMP() - interval)) {

                                        // do not update
                                        updateCache = false;
                                    }
                                }

                                // update cache in background
                                if (updateCache) {
                                    (function(request, cacheResponse) {

                                        // @todo
                                        // fetch update queue? 
                                        setTimeout(function() {

                                            // notify client when update completes
                                            var afterUpdate;
                                            if (policyMatch.cache.head_update) {
                                                afterUpdate = function() {

                                                    clients.matchAll().then(function(clients) {
                                                        clients.forEach(function(client) {
                                                            client.postMessage([2, request.url]);
                                                        });
                                                    });
                                                };
                                            }
                                            if (policyMatch.cache.head_update) {

                                                if (ABTFDEBUG) {
                                                    console.info('Abtf.sw() ➤ HEAD ➤ verify', request.url);
                                                }

                                                HEAD_UPDATE(request, policyMatch.cache, cacheResponse, afterUpdate);
                                            } else {

                                                if (ABTFDEBUG) {
                                                    console.info('Abtf.sw() ➤ update cache', request.url);
                                                }

                                                var fetchRequest = FETCH(request, policyMatch.cache);
                                                if (afterUpdate) {
                                                    fetchRequest.then(afterUpdate);
                                                }
                                            }
                                        }, 10);

                                    })(event.request.clone(), cacheResponse.clone());
                                }

                                if (ABTFDEBUG) {
                                    console.info('Abtf.sw() ➤ from cache', event.request.url);
                                }
                                return cacheResponse;
                            } else {

                                return FETCH(event.request, policyMatch.cache, function(request, fetchResponse, error) {

                                    // return offline page
                                    if (policyMatch.offline) {

                                        if (ABTFDEBUG) {
                                            console.warn('Abtf.sw() ➤ no cache ➤ network failed ➤ offline page', request.url);
                                        }

                                        return OFFLINE(policyMatch.offline, request.clone());
                                    } else {

                                        if (ABTFDEBUG) {
                                            console.warn('Abtf.sw() ➤ no cache ➤ network failed ➤ empty 404 response', request.url, fetchResponse, error);
                                        }
                                        // return 404 response
                                        if (!fetchResponse) {
                                            return fetch(event.request.clone()).catch(function(error) {
                                                throw error;
                                            });
                                        }
                                        return fetchResponse;
                                    }

                                });
                            }
                        });
                    break;

                    // try cache but do not add to cache
                case "event":
                    // try cache
                    return CACHE_GET(event.request)
                        .then(function(cacheResponse) {

                            // return cache
                            if (cacheResponse) {

                                if (ABTFDEBUG) {
                                    console.info('Abtf.sw() ➤ from cache', event.request.url);
                                }
                                return cacheResponse;
                            } else {

                                return FETCH(event.request, null, function(request, fetchResponse, error) {

                                    // return offline page
                                    if (policyMatch.offline) {

                                        if (ABTFDEBUG) {
                                            console.warn('Abtf.sw() ➤ no cache ➤ network failed ➤ offline page', request.url);
                                        }

                                        return OFFLINE(policyMatch.offline, request.clone());
                                    } else {

                                        if (ABTFDEBUG) {
                                            console.warn('Abtf.sw() ➤ no cache ➤ network failed ➤ empty 404 response', request.url, fetchResponse);
                                        }
                                        // return 404 response
                                        if (!fetchResponse) {
                                            return fetch(event.request).catch(function(error) {
                                                throw error;
                                            });;
                                        }
                                        return fetchResponse;
                                    }

                                });
                            }
                        });
                    break;

                    // Network request with cache as backup
                case "network":
                default:
                    return FETCH(event.request, policyMatch.cache, function(request, fetchResponse, error) {

                        if (ABTFDEBUG) {
                            console.warn('Abtf.sw() ➤ network failed', request.url, (fetchResponse || error));
                        }

                        // try cache
                        return CACHE_GET(request)
                            .then(function(response) {

                                // return cache
                                if (response) {
                                    if (ABTFDEBUG) {
                                        console.info('Abtf.sw() ➤ fallback from cache', request.url);
                                    }
                                    return response;
                                }

                                // return offline page
                                if (policyMatch.offline) {

                                    if (ABTFDEBUG) {
                                        console.warn('Abtf.sw() ➤ no cache ➤ offline page', request.url);
                                    }

                                    return OFFLINE(policyMatch.offline, request.clone());
                                } else {

                                    if (ABTFDEBUG) {
                                        console.warn('Abtf.sw() ➤ no cache ➤ empty 404 response', request.url);
                                    }
                                    // return 404 response
                                    if (!fetchResponse) {
                                        return fetch(event.request).catch(function(error) {
                                            throw error;
                                        });;
                                    }
                                    return fetchResponse;
                                }
                            });
                    });
                    break;
            }
        };

        // policy is available
        var policyRequest = matchPolicy(event, PWA_POLICY);
        if (policyRequest === false) {
            return; // request should be handled by client
        }
        return event.respondWith(policyRequest); // request is processed by service worker

    });

    // process messages from client
    self.addEventListener('message', function(event) {

        // PWA uses array data
        if (event && event.data && event.data instanceof Array) {

            // CONFIG EVENT
            if (event.data[0] === 1) {

                // cache policy timestamp
                if (event.data[1] && !isNaN(parseInt(event.data[1]))) {
                    GET_POLICY(parseInt(event.data[1]));
                }

                // max cache size
                if (event.data[3] && !isNaN(parseInt(event.data[3]))) {
                    PWA_CACHE_MAX_SIZE = parseInt(event.data[3]);
                }

                // update prefix (using cache version setting)
                var prefix = 'abtf:' + ((event.data[2]) ? event.data[2] + ':' : '');
                if (prefix !== PWA_CACHE) {
                    PWA_CACHE = prefix;
                    if (ABTFDEBUG) {
                        console.info('Abtf.sw() ➤ cache prefix changed', PWA_CACHE);
                    }
                }

                // cache maintenance
                CLEAN_CACHE();
            }

            if (event.data[0] === 2 || event.data[0] === 3) {
                if (event.ports[0]) {
                    var resolve = function(err, status) {
                        event.ports[0].postMessage({
                            error: err,
                            status: status
                        });
                    }
                } else {
                    var resolve = false;
                }
            }

            // PRELOAD EVENT
            if (event.data[0] === 2) {

                if (event.data[1]) {
                    var preload;
                    if (typeof event.data[1] === 'string' || event.data[1] instanceof Request) {
                        preload = [event.data[1]];
                    } else if (event.data[1] instanceof Array) {
                        preload = event.data[1];
                    }
                    if (preload) {
                        var promises = [];
                        preload.forEach(function(url) {
                            promises.push(CACHE_PRELOAD(url));
                        });
                        if (resolve) {
                            Promise.all(promises).then(function(responses) {
                                var status = [];
                                responses.forEach(function(response) {
                                    var resourceStatus = {
                                        url: response.url,
                                        status: response.status,
                                        statusText: response.statusText
                                    };
                                    var size = response.headers.get('content-length');
                                    resourceStatus.size = (!isNaN(parseInt(size))) ? parseInt(size) : -1;
                                    status.push(resourceStatus);
                                });
                                resolve(null, status);
                            }).catch(function(err) {
                                if (ABTFDEBUG) {
                                    console.error('Abtf.sw() ➤ preload', err);
                                }
                            });
                        }
                    } else {
                        if (resolve) {
                            resolve('invalid-data');
                        }
                    }
                } else {
                    if (resolve) {
                        resolve('no-urls');
                    }
                }
            }

            // PUSH EVENT
            if (event.data[0] === 3) {
                self.registration.showNotification(event.data[1], event.data[2]);
                if (resolve) {
                    resolve(null, 'sent');
                }
            }
        }

    });


})(self, self.fetch, Cache);
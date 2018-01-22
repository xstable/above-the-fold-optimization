/**
 * Simple Critical CSS extraction widget
 */
(function() {

    /**
     *
     * Based on a concept by PaulKinlan
     * @link https://gist.github.com/PaulKinlan/6284142
     *
     * Made cross browser using 
     * @link https://github.com/ovaldi/getMatchedCSSRules
     */
    var CSSCriticalPath = function(w, d, opts) {
        var opt = opts || {};
        var css = {};
        var inlineCount = 0;
        var pushCSS = function(r) {

            var stylesheetFile = r.parentStyleSheet.href;
            if (!stylesheetFile) {
                inlineCount++;
                stylesheetFile = 'Inline';
            } else {
                stylesheetFile = 'File: ' + stylesheetFile;
            }
            if (!!css[stylesheetFile] === false) {
                css[stylesheetFile] = {
                    media: r.parentStyleSheet.media,
                    css: {}
                };
            }

            if (!!css[stylesheetFile].css[r.selectorText] === false) {
                css[stylesheetFile].css[r.selectorText] = {};
            }

            var styles = r.style.cssText.split(/;(?![A-Za-z0-9])/);
            for (var i = 0; i < styles.length; i++) {
                if (!!styles[i] === false) continue;
                var pair = styles[i].split(": ");
                pair[0] = pair[0].trim();
                pair[1] = pair[1].trim();
                css[stylesheetFile].css[r.selectorText][pair[0]] = pair[1];
            }
        };

        var parseTree = function() {
            // Get a list of all the elements in the view.
            var height = w.innerHeight;
            var walker = d.createTreeWalker(d, NodeFilter.SHOW_ELEMENT, function(node) {
                return NodeFilter.FILTER_ACCEPT;
            }, true);

            while (walker.nextNode()) {
                var node = walker.currentNode;
                var rect = node.getBoundingClientRect();
                if (rect.top < height || opt.scanFullPage) {
                    var rules = w.getMatchedCSSRules(node);
                    if (!!rules) {
                        for (var r = 0; r < rules.length; r++) {
                            pushCSS(rules[r]);
                        }
                    }
                }
            }
        };

        this.generateCSS = function() {
            var finalCSS = "";

            var printConsole = (console && console.groupCollapsed);
            var consoleCSS;
            var cssRule;

            if (console.clear) {
                console.clear();
            }

            if (printConsole) {
                console.log("%cSimple Critical CSS Extraction", "font-size:24px;font-weight:bold");
                console.log("For professional Critical CSS generators, see https://github.com/addyosmani/critical-path-css-tools");
            }

            for (var file in css) {

                if (printConsole) {
                    console.groupCollapsed(file);
                    consoleCSS = '';
                }

                finalCSS += "/**\n * @file " + file;
                if (css[file].media && (css[file].media.length > 1 || css[file].media[0] !== 'all')) {
                    var media = [];
                    for (var i = 0; i < css[file].media.length; i++) {
                        media.push(css[file].media[i]);
                    }
                    media = media.join(' ');
                    finalCSS += "\n * @media " + media;
                }
                finalCSS += "\n */\n";
                for (k in css[file].css) {

                    cssRule = k + " { ";
                    for (var j in css[file].css[k]) {
                        cssRule += j + ": " + css[file].css[k][j] + "; ";
                    }
                    cssRule += "}" + "\n";

                    finalCSS += cssRule;

                    if (printConsole) {
                        consoleCSS += cssRule;
                    }
                }
                finalCSS += "\n";

                if (printConsole) {
                    console.log(consoleCSS);
                    console.groupEnd();
                }
            }

            if (printConsole) {
                console.groupCollapsed('All Extracted Critical CSS');
            } else {
                console.log('%cAll:', "font-weight:bold");
            }
            console.log(finalCSS);

            if (printConsole) {
                console.groupEnd();
            }

            return finalCSS;
        };

        parseTree();
    };

    // public extract Critical CSS method
    window.extractCriticalCSS = function() {

        var cp = new CSSCriticalPath(window, document);
        var css = cp.generateCSS();

        try {
            var isFileSaverSupported = !!new Blob;
        } catch (e) {}

        if (!isFileSaverSupported) {
            alert('Your browser does not support javascript based file download. The critical CSS is printed in the console.')
        } else {
            var blob = new Blob(["/**\n * Simple Critical CSS extracted using the Page Speed Optimization widget\n *\n * @link https://wordpress.org/plugins/above-the-fold-optimization/\n *\n * Note: this critical CSS is extracted using the browser viewport. For professional Critical CSS generators @see https://github.com/addyosmani/critical-path-css-tools\n */\n\n" +
                css
            ], {
                type: "text/css;charset=utf-8"
            });
            var path = window.location.pathname;
            if (path && path !== '/' && path.indexOf('/') !== -1) {
                path = '-' + path.replace(/\/$/, '').split('/').pop();
            } else {
                path = '-front-page';
            }
            var filename = 'critical-css' + path + '.css';
            saveAs(blob, filename);

            setTimeout(function() {
                alert('Simple Critical CSS Extraction\n\nA download should have been started. If not, please check your browser permissions.\n\nThe Critical CSS have also been printed to the browser console.');
            }, 100);
        }
    };

    // based on @link https://github.com/krasimir/css-steal
    var CSSSteal = function() {
        var elements = [document.body],
            html = null,
            styles = [],
            indent = '  ';

        var getHTMLAsString = function() {
            return elements.outerHTML;
        };
        var toArray = function(obj, ignoreFalsy) {
            var arr = [],
                i;

            for (i = 0; i < obj.length; i++) {
                if (!ignoreFalsy || obj[i]) {
                    arr[i] = obj[i];
                }
            }
            return arr;
        }
        var getRules = function(a) {
            var sheets = document.styleSheets,
                result = [],
                selectorText;

            a.matches = a.matches || a.webkitMatchesSelector || a.mozMatchesSelector || a.msMatchesSelector || a.oMatchesSelector;
            for (var i in sheets) {
                var rules = sheets[i].rules || sheets[i].cssRules;
                for (var r in rules) {
                    selectorText = rules[r].selectorText ? rules[r].selectorText.split(' ').map(function(piece) {
                        return piece ? piece.split(/(:|::)/)[0] : false;
                    }).join(' ') : false;
                    try {
                        if (a.matches(selectorText)) {
                            result.push(rules[r]);
                        }
                    } catch (e) {
                        // can not run matches on this selector
                    }
                }
            }
            return result;
        }
        var readStyles = function(els) {
            return els.reduce(function(s, el) {
                s.push(getRules(el));
                s = s.concat(readStyles(toArray(el.children)));
                return s;
            }, []);
        };
        var flattenRules = function(s) {
            var filterBySelector = function(selector, result) {
                return result.filter(function(item) {
                    return item.selector === selector;
                });
            }
            var getItem = function(selector, result) {
                var arr = filterBySelector(selector, result);
                return arr.length > 0 ? arr[0] : {
                    selector: selector,
                    styles: {}
                };
            }
            var pushItem = function(item, result) {
                var arr = filterBySelector(item.selector, result);
                if (arr.length === 0) result.push(item);
            }
            var all = [];
            s.forEach(function(rules) {
                rules.forEach(function(rule) {
                    var item = getItem(rule.selectorText, all);
                    for (var i = 0; i < rule.style.length; i++) {
                        var property = rule.style[i];
                        item.styles[property] = rule.style.getPropertyValue(property);
                    }
                    pushItem(item, all);
                });
            });
            return all;
        };

        html = getHTMLAsString();
        styles = flattenRules(readStyles(elements));

        return styles.reduce(function(text, item) {
            text += item.selector + ' {\n';
            text += Object.keys(item.styles).reduce(function(lines, prop) {
                lines.push(indent + prop + ': ' + item.styles[prop] + ';');
                return lines;
            }, []).join('\n');
            text += '\n}\n';
            return text;
        }, '');

    };

    // public extract Full CSS method
    window.extractFullCSS = function() {
        var css = CSSSteal();

        try {
            var isFileSaverSupported = !!new Blob;
        } catch (e) {}

        if (console.clear) {
            console.clear();
        }

        console.log("%cFull CSS Extraction", "font-size:24px;font-weight:bold");

        if (console.groupCollapsed) {
            console.groupCollapsed('Extracted Full CSS');
        }
        console.log(css);
        if (console.groupCollapsed) {
            console.groupEnd();
        }

        if (!isFileSaverSupported) {
            alert('Your browser does not support javascript based file download. The full CSS is printed in the console.')
        } else {
            var blob = new Blob(["/**\n * Full CSS extracted using the Page Speed Optimization widget\n *\n * @link https://wordpress.org/plugins/above-the-fold-optimization/\n */\n\n" +
                css
            ], {
                type: "text/css;charset=utf-8"
            });
            var path = window.location.pathname;
            if (path && path !== '/' && path.indexOf('/') !== -1) {
                path = '-' + path.replace(/\/$/, '').split('/').pop();
            } else {
                path = '-front-page';
            }
            var filename = 'full-css' + path + '.css';
            saveAs(blob, filename);

            setTimeout(function() {
                alert('Full Critical CSS Extraction\n\nA download should have been started. If not, please check your browser permissions.\n\nThe Full CSS have also been printed to the browser console.');
            }, 100);
        }
    };
})();
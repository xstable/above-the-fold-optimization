# WordPress Above The Fold Optimization

The [Above The Fold Optimization](https://wordpress.org/plugins/above-the-fold-optimization/) plugin is a toolkit that enables to achieve a **`100`** score in the [Google PageSpeed Insights](https://developers.google.com/speed/pagespeed/insights/) test. It is a plugin intended for optimization professionals and advanced WordPress users.

https://wordpress.org/plugins/above-the-fold-optimization/

The plugin is compatible with most optimization, caching and minification plugins such as Autoptimize and W3 Total Cache. The plugin offers modular compatibility and can be extended to support any optimization plugin. ([more info](https://github.com/optimalisatie/above-the-fold-optimization/tree/master/trunk/modules/plugins/))

## Critical CSS management

The plugin contains a tool to manage Critical Path CSS for inline placement in the `<head>` of the HTML document. Read more about Critical CSS in the [documentation by Google](https://developers.google.com/speed/docs/insights/PrioritizeVisibleContent). 

[This article](https://github.com/addyosmani/critical-path-css-tools) by a Google engineer provides information about the available methods for creating critical path CSS. 

## Conditional Critical CSS

The plugin contains a tool to configure Critical Path CSS for individual posts, pages, page types and other conditions.

## CSS Delivery Optimization

The plugin contains several tools to optimize the delivery of CSS in the browser. The plugin offers async loading of CSS via [loadCSS](https://github.com/filamentgroup/loadCSS) and it offers an enhanced version of loadCSS that uses the `requestAnimationFrame` API following the [recommendations by Google](https://developers.google.com/speed/docs/insights/OptimizeCSSDelivery).

The plugin offers advanced options such as a render delay in milliseconds, the position to start CSS rendering (header or footer) and the removal of CSS files from the HTML.

## Web Font Optimization

The plugin contains a tool to optimize web fonts. The plugin automaticly parses web font `@import` links in minified CSS files and `<link>` links in the HTML and loads them via [Google Web Font Loader](https://github.com/typekit/webfontloader).

## External Resource Proxy

The plugin contains a tool to localize (proxy) external javascript and CSS resources such as Google Analytics and Facebook SDK to pass the `Leverage browser caching` rule from Google PageSpeed Insights. The proxy is able to capture "script-injected" async scripts to solve the problem without further configuration.

## Lazy Loading Scripts

The plugin contains a tool based on [jQuery Lazy Load XT](https://github.com/ressio/lazy-load-xt#widgets) to lazy load scripts such as Facebook en Twitter social widgets.

## Above The Fold Quality Tester

The plugin contains a tool to test the quality of the above the fold (critical path CSS) rendering.

## Full CSS Extraction

The plugin contains a tool to extract the full CSS of a page.

## Maintainers

* [@optimalisatie](https://github.com/optimalisatie)

## License

(C) [www.pagespeed.pro](https://pagespeed.pro) 2014–2016, released under the MIT license

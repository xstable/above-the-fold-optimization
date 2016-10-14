# WordPress Optimization Plugin Modules

The [Above The Fold Optimization](https://wordpress.org/plugins/above-the-fold-optimization/) plugin can be made compatible with any optimization, minification or full page cache plugin by creating a module extension. The plugin contains several modules by default for some of the most used plugins.


## Creating a custom module

To add support for an unsupported module, you can copy the source of an existing module and place it as a custom module in `/wp-content/themes/YOUR_THEME_NAME/abovethefold/plugins/module-name.inc.php` and add a text file named `module-name.active.txt` that contains the WordPress plugin reference name (usually `plugin-name/plugin-name.php`) which is used for fast checking the active state of the plugin.

Please submit new modules or suggestions to support new optimization plugins to info@pagespeed.pro.

## Maintainers

* [@optimalisatie](https://github.com/optimalisatie)

## License

(C) [www.pagespeed.pro](https://pagespeed.pro) 2014–2016, released under the MIT license

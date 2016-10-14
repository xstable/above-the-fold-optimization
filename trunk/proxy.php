<?php
/**
 * Above the fold cached external resource proxy
 *
 * Cached externa resource proxy to pass the "Eliminate render-blocking JavaScript and CSS in above-the-fold content" rule from Google PageSpeed.
 *
 * @link              https://pagespeed.pro/
 * @since             2.5.0
 * @package           abovethefold
 */

$currenturl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];


die($_SERVER['HTTP_REFERER']);

$path = dirname(__FILE__);
$path .= (substr($path, -1) == '/' ? '' : '/');



$url = trim($_GET['url']);
$type = trim($_GET['url']);


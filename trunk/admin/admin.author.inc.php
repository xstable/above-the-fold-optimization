<?php
 
	/**
	 * lgcode for Google Documentation links
	 */
	$lgcode = strtolower(get_locale());
	if (strpos($lgcode,'_') !== false) {
		$lgparts = explode('_',$lgcode);
		$lgcode = $lgparts[0];
	}
	if ($lgcode === 'en') {
		$lgcode = '';
	}
	
?>
<div id="post-body-content" style="padding-bottom:0px;margin-bottom:0px;margin-left:5px;">
	<div class="authorbox">
		<div class="inside" style="width:auto;margin:0px;float:left;position:relative;margin-right:2em;">
			<p style="z-index:999;">Developed by <strong><a href="https://pagespeed.pro/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=Above%20The%20Fold%20Optimization" target="_blank">PageSpeed.pro</a></strong>
			<br />Contribute via <a href="https://github.com/optimalisatie/above-the-fold-optimization" target="_blank">Github</a> &dash; <a href="https://wordpress.org/support/plugin/above-the-fold-optimization" target="_blank">Report a bug</a> &dash; <a href="https://wordpress.org/support/view/plugin-reviews/above-the-fold-optimization?rate=5" target="_blank">Review this plugin</a></p>
		</div>

		<div class="inside" style="margin:0px;float:left;font-style:italic;">
			<img src="https://optimalisatie.nl/img/websockify-rocket-50.png" title="Websockify" style="float:left;margin-right:5px;margin-top:8px;" width="50" align="absmiddle" />
			<p>We have developed a prototype plugin that is able to provide instant (&lt;1ms) website load times (also for WooCommerce) and up to 99% document data-transfer saving. 
			<a href="http://websockify.it/" target="_blank">Information</a> / <a href="https://websockify.io/" target="_blank">Demo (WooCommerce)</a>.</p>
		</div>
		<!--
			This plugin is not a simple 'on/off' plugin. It is a tool for optimization professionals and advanced WordPress users to achieve a Google PageSpeed <span class="g100">100</span> Score.
		-->
	</div>
</div>
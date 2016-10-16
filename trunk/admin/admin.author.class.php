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
			<svg id="reviewanim" width="300" height="100" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg" <?php if (isset($options['update_count']) && $options['update_count']>=2) { print ' data-count="1"'; } ?> style="display:none;"><g><path class="glittering" fill="#ffcc00" d="m270.88123,28.60792l-7.20074,6.20193l1.02679,9.44777l-8.12354,-4.93181l-8.66808,3.89605l2.18013,-9.24996l-6.38396,-7.03987l9.47093,-0.78498l4.72256,-8.24694l3.67325,8.76482l9.30264,1.94299z"/></g></svg>
			<p style="z-index:999;">Developed by <strong><a href="https://pagespeed.pro/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=Above%20The%20Fold%20Optimization" target="_blank">PageSpeed.pro</a></strong>
			<br />Contribute via <a href="https://github.com/optimalisatie/above-the-fold-optimization" target="_blank">Github</a> &dash; <a href="https://wordpress.org/support/plugin/above-the-fold-optimization" target="_blank">Report a bug</a> &dash; <a href="https://wordpress.org/support/plugin/above-the-fold-optimization/reviews/?rate=5#new-post" target="_blank">Review this plugin</a></p>
		</div>
		<!--
			This plugin is not a simple 'on/off' plugin. It is a tool for optimization professionals and advanced WordPress users to achieve a Google PageSpeed <span class="g100">100</span> Score.
		-->
	</div>
</div>
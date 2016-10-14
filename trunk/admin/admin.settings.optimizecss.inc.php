<?php

	/**
	 * Get version of local loadCSS
	 */
	$loadcss_version = '';
	$loadcss_package = WPABTF_PATH . 'bower_components/loadcss/package.json';
	if (!file_exists($loadcss_package)) {
?>
	<tr valign="top">
		<th scope="row" colspan="2"><h1 style="color:red;">WARNING: PLUGIN INSTALLATION NOT COMPLETE, MISSING bower_components/loadcss/</h1></th>
	</tr>
<?php
	} else {

		$package = @json_decode(file_get_contents($loadcss_package),true);
		if (!is_array($package)) {
?>
	<tr valign="top">
		<th scope="row" colspan="2"><h1 style="color:red;">failed to parse bower_components/loadcss/package.json</h1></th>
	</tr>
<?php
		} else { 

			// set version
			$loadcss_version = $package['version'];
		}
	}

	if (empty($loadcss_version)) {
		$loadcss_version = '(unknown)';
	}

	// CSS Proxy Enabled?
	$cssProxy = (isset($options['css_proxy']) && intval($options['css_proxy']) === 1);
	$cssProxyOptions = ($cssProxy) ? '' : 'display:none;';
	$cssProxyOptionsHide = (!$cssProxy) ? '' : 'display:none;';
?>
<tr valign="top">
	<th scope="row" style="padding-top:0px;">Optimize CSS Delivery</th>
	<td style="padding-top:0px;">
		<label><input type="checkbox" name="abovethefold[cssdelivery]" value="1"<?php if (!isset($options['cssdelivery']) || intval($options['cssdelivery']) === 1) { print ' checked'; } ?> onchange="if (jQuery(this).is(':checked')) { jQuery('.cssdeliveryoptions').show(); } else { jQuery('.cssdeliveryoptions').hide(); }"> Enabled</label>
		<p class="description">When enabled, CSS files are loaded asynchronously via <a href="https://github.com/filamentgroup/loadCSS" target="_blank">loadCSS</a> (v<?php print $loadcss_version;?>).  <a href="https://developers.google.com/speed/docs/insights/OptimizeCSSDelivery?hl=<?php print $lgcode;?>" target="_blank">Click here</a> for the recommendations by Google.</p>
	</td>
</tr>
<tr valign="top" class="cssdeliveryoptions" style="<?php if (isset($options['cssdelivery']) && intval($options['cssdelivery']) !== 1) { print 'display:none;'; } ?>">
	<td colspan="2" style="padding-top:0px;">

		<div class="abtf-inner-table">

			<h3 class="h"><span>CSS Delivery Optimization</span></h3>
			<div class="inside">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Proxy External Styles</th>
						<td>
                            <label><input type="checkbox" name="abovethefold[css_proxy]" onchange="if (jQuery(this).is(':checked')) { jQuery('.proxycssoptions').show(); jQuery('.proxycssoptionshide').hide(); } else { jQuery('.proxycssoptions').hide(); jQuery('.proxycssoptionshide').show(); }" value="1"<?php if ($cssProxy) { print ' checked'; } ?>> Enabled</label>
                            <p class="description">Capture external stylesheets and load the files through a caching proxy. This feature enables to pass the <code>Eliminate render-blocking JavaScript and CSS in above-the-fold content</code> rule from Google PageSpeed Insights.</p>
						</td>
					</tr>
					<tr valign="top" class="proxycssoptions" style="<?php print $cssProxyOptions; ?>">
						<th scope="row">Proxy Include List</th>
						<td>
							<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[css_proxy_include]" placeholder="Leave blank to proxy all external files..."><?php if (isset($options['css_proxy_include'])) { echo htmlentities($options['css_proxy_include'],ENT_COMPAT,'utf-8'); } ?></textarea>
							<p class="description">Enter (parts of) external stylesheets to proxy, e.g. <code>googleapis.com/jquery-ui.css</code>. One stylesheet per line. </p>
						</td>
					</tr>
					<tr valign="top" class="proxycssoptions" style="<?php print $cssProxyOptions; ?>">
						<th scope="row">Proxy Exclude List</th>
						<td>
							<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[css_proxy_exclude]"><?php if (isset($options['css_proxy_exclude'])) { echo htmlentities($options['css_proxy_exclude'],ENT_COMPAT,'utf-8'); } ?></textarea>
							<p class="description">Enter (parts of) external stylesheets to exclude from the proxy. One stylesheet per line.</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Enhanced loadCSS</th>
						<td>
							<label><input type="checkbox" name="abovethefold[loadcss_enhanced]" value="1" onchange="if (jQuery(this).is(':checked')) { jQuery('.enhancedloadcssoptions').show(); } else { jQuery('.enhancedloadcssoptions').hide(); }"<?php if (!isset($options['loadcss_enhanced']) || intval($options['loadcss_enhanced']) === 1) { print ' checked'; } ?>> Enabled</label>
							<p class="description">When enabled, a customized version of loadCSS is used to make use of the <code>requestAnimationFrame</code> API following the <a href="https://developers.google.com/speed/docs/insights/OptimizeCSSDelivery?hl=<?php print $lgcode;?>" target="_blank">recommendations by Google</a>.</p>
						</td>
					</tr>
					<tr valign="top" class="enhancedloadcssoptions" style="<?php if (isset($options['loadcss_enhanced']) && intval($options['loadcss_enhanced']) !== 1) { print 'display:none;'; } ?>">
						<th scope="row">CSS render delay</th>
						<td>
							<table cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td valign="top" style="padding:0px;vertical-align:top;"><input type="number" min="0" max="3000" step="1" name="abovethefold[cssdelivery_renderdelay]" size="10" value="<?php print ((empty($options['cssdelivery_renderdelay']) || $options['cssdelivery_renderdelay'] === 0) ? '' : htmlentities($options['cssdelivery_renderdelay'],ENT_COMPAT,'utf-8')); ?>" onkeyup="if (jQuery(this).val() !== '' && jQuery(this).val() !== '0') { jQuery('#warnrenderdelay').show(); } else { jQuery('#warnrenderdelay').hide(); }" onchange="if (jQuery(this).val() === '0') { jQuery(this).val(''); } if (jQuery(this).val() !== '' && jQuery(this).val() !== '0') { jQuery('#warnrenderdelay').show(); } else { jQuery('#warnrenderdelay').hide(); }" placeholder="0 ms" /></td>
									<td valign="top" style="padding:0px;vertical-align:top;padding-left:10px;font-size:11px;"><div id="warnrenderdelay" style="padding:0px;margin:0px;<?php print ((empty($options['cssdelivery_renderdelay']) || $options['cssdelivery_renderdelay'] === 0 || trim($options['cssdelivery_renderdelay']) === '') ? 'display:none;' : ''); ?>"><span style="color:red;font-weight:bold;">Warning:</span> A higher Google PageSpeed score may sometimes be achieved using this option but it may not be beneficial to the page rendering experience of your users. Often it is best to seek an alternative solution.</div></td>
								</tr>
							</table>
							<p class="description" style="clear:both;">Optionally, enter a time in milliseconds to delay the rendering of CSS files.</p>

						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Position</th>
						<td>
							<select name="abovethefold[cssdelivery_position]">
								<option value="header"<?php if (isset($options['cssdelivery_position']) && $options['cssdelivery_position'] === 'header') { print ' selected'; } ?>>Header</option>
								<option value="footer"<?php if (!isset($options['cssdelivery_position']) || empty($options['cssdelivery_position']) || $options['cssdelivery_position'] === 'footer') { print ' selected'; } ?>>Footer</option>
							</select>
							<p class="description">Select the position where the async loading of CSS will start.</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Ignore List</th>
						<td>
							<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[cssdelivery_ignore]"><?php if (isset($options['cssdelivery_ignore'])) { echo htmlentities($options['cssdelivery_ignore'],ENT_COMPAT,'utf-8'); } ?></textarea>
							<p class="description">CSS files to ignore in CSS delivery optimization (one file per line). The files will be left untouched in the HTML.</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Remove List</th>
						<td>
							<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[cssdelivery_remove]"><?php if (isset($options['cssdelivery_remove'])) { echo htmlentities($options['cssdelivery_remove'],ENT_COMPAT,'utf-8'); } ?></textarea>
							<p class="description">CSS files to remove (one file per line). This feature enables to include small plugin related CSS files inline.</p>
						</td>
					</tr>
				</table>
			</div>

		</div>

		<br />
		<?php
			submit_button( __( 'Save' ), 'primary large', 'is_submit', false );
		?>
	</td>
</tr>
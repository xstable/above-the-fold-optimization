<?php

	/**
	 * Get version of local webfont.js
	 */
	$webfont_version = $this->CTRL->gwfo->package_version(true);
	if (empty($webfont_version)) {
		$webfont_version = '(unknown)';
	}

?>
<tr valign="top">
	<th scope="row">Optimize Web Fonts</th>
	<td>
		<label><input type="checkbox" name="abovethefold[gwfo]" value="1"<?php if (!isset($options['gwfo']) || intval($options['gwfo']) === 1) { print ' checked'; } ?> onchange="if (jQuery(this).is(':checked')) { jQuery('.gwfooptions').show(); } else { jQuery('.gwfooptions').hide(); }"> Enabled
		</label>
		<p class="description">When enabled, web fonts are optimized using <a href="https://github.com/typekit/webfontloader" target="_blank">Google Web Font Loader</a>.</p>
	</td>
</tr>
<tr valign="top" class="gwfooptions" style="<?php if (isset($options['gwfo']) && intval($options['gwfo']) !== 1) { print 'display:none;'; } ?>">
	<td colspan="2" style="padding-top:0px;">

		<div class="abtf-inner-table">

			<h3 class="h"><span>Web Font Optimization</span></h3>

			<div class="inside">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">webfont.js Load Method</th>
						<td>
							<select name="abovethefold[gwfo_loadmethod]">
								<option value="inline"<?php if (!isset($options['gwfo_loadmethod']) || $options['gwfo_loadmethod'] === 'inline') { print ' selected'; } ?>>Inline</option>
								<option value="async"<?php if (isset($options['gwfo_loadmethod']) && $options['gwfo_loadmethod'] === 'async') { print ' selected'; } ?>>Async</option>
								<option value="async_cdn"<?php if (isset($options['gwfo_loadmethod']) && $options['gwfo_loadmethod'] === 'async_cdn') { print ' selected'; } ?>>Async from Google CDN (v<?php print $this->CTRL->gwfo->cdn_version; ?>)</option>
								<option value="wordpress"<?php if (isset($options['gwfo_loadmethod']) && $options['gwfo_loadmethod'] === 'wordpress') { print ' selected'; } ?>>WordPress include</option>
							</select>
							<p class="description">Select the method to load <a href="https://developers.google.com/speed/libraries/?hl=<?php print $lgcode;?>#web-font-loader" target="_blank">webfont.js</a> (v<?php print $webfont_version; ?>).</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Load Position</th>
						<td>
							<select name="abovethefold[gwfo_loadposition]">
								<option value="header"<?php if (!isset($options['gwfo_loadposition']) || $options['gwfo_loadposition'] === 'header') { print ' selected'; } ?>>Header</option>
								<option value="footer"<?php if (isset($options['gwfo_loadposition']) && $options['gwfo_loadposition'] === 'footer') { print ' selected'; } ?>>Footer</option>
							</select>
							<p class="description">Select the position where the loading of web fonts will start.</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">WebFontConfig</th>
						<td>
							<textarea style="width: 100%;height:100px;font-size:11px;" name="abovethefold[gwfo_config]" placeholder="WebFontConfig = { classes: false, typekit: { id: 'xxxxxx' }, loading: function() {}, google: { families: ['Droid Sans', 'Droid Serif'] } };"><?php if (isset($options['gwfo_config'])) { echo htmlentities($options['gwfo_config']); } ?></textarea>
							<p class="description">Enter the <code>WebFontConfig</code> variable for Google Web Font Loader. Leave blank for the default configuration. (<a href="https://github.com/typekit/webfontloader#configuration" target="_blank">more information</a>)</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Google Web Fonts</th>
						<td>
							<textarea style="width: 100%;height:<?php if (count(explode("\n",$options['gwfo_googlefonts'])) > 3) { print '100px'; } else { print '50px'; } ?>;font-size:11px;" name="abovethefold[gwfo_googlefonts]" placeholder="Droid Sans
Open Sans Condensed:300,700:latin,greek"><?php if (isset($options['gwfo_googlefonts'])) { echo htmlentities($options['gwfo_googlefonts']); } ?></textarea>
							<p class="description">Enter the <a href="https://developers.google.com/fonts/docs/getting_started?hl=<?php print $lgcode;?>&csw=1" target="_blank">Google Font API</a> definitions of <a href="https://fonts.google.com/?hl=<?php print $lgcode;?>" target="_blank">Google Web Fonts</a> to load. One font per line. (<a href="https://github.com/typekit/webfontloader#google" target="_blank">documentation</a>)</p>
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
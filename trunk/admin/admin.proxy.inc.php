<?php

	// CSS Proxy Enabled?
	$cssProxy = (isset($options['css_proxy']) && intval($options['css_proxy']) === 1);
	$cssProxyOptions = ($cssProxy) ? '' : 'display:none;';
	$cssProxyOptionsHide = (!$cssProxy) ? '' : 'display:none;';


	// Javascript Proxy Enabled?
	$jsProxy = (isset($options['js_proxy']) && intval($options['js_proxy']) === 1);
	$jsProxyOptions = ($jsProxy) ? '' : 'display:none;';
	$jsProxyOptionsHide = (!$jsProxy) ? '' : 'display:none;';


	// js preload list
	if (isset($options['js_proxy_preload']) && !empty($options['js_proxy_preload'])) {

		$jsPreload = array();
		foreach ($options['js_proxy_preload'] as $url) {
			$jsPreload[] = (is_string($url)) ? $url : json_encode($url);
		}
		$jsPreload = implode("\n",$jsPreload);
	} else {
		$jsPreload = '';
	}

	// css preload list
	if (isset($options['css_proxy_preload']) && !empty($options['css_proxy_preload'])) {

		$cssPreload = array();
		foreach ($options['css_proxy_preload'] as $url) {
			$cssPreload[] = (is_string($url)) ? $url : json_encode($url);
		}
		$cssPreload = implode("\n",$cssPreload);
	} else {
		$cssPreload = '';
	}

?>
<form method="post" action="<?php echo admin_url('admin-post.php?action=abtf_proxy_update'); ?>" class="clearfix">
	<?php wp_nonce_field('abovethefold'); ?>
	<div class="wrap abovethefold-wrapper">
		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
				<div id="post-body-content">
					<div class="postbox">
						<h3 class="hndle">
							<span><?php _e( 'External Resource Proxy', 'abovethefold' ); ?></span>
						</h3>
						<div class="inside testcontent">

							<p>The external resource proxy loads external resources such as scripts and stylesheets via a caching proxy. This feature enables to pass the <a href="https://developers.google.com/speed/docs/insights/LeverageBrowserCaching?hl=<?php print $lgcode;?>" target="_blank">Leverage browser caching</a> rule from Google PageSpeed Insights.</p>

							<table class="form-table">
								
								<tr valign="top">
									<th scope="row">Proxy Scripts</th>
									<td>
			                            <label><input type="checkbox" name="abovethefold[js_proxy]" onchange="if (jQuery(this).is(':checked')) { jQuery('.proxyjsoptions').show(); } else { jQuery('.proxyjsoptions').hide(); }" value="1"<?php if ($jsProxy) { print ' checked'; } ?>> Enabled</label>
			                            <p class="description">Capture external scripts and load the scripts through a caching proxy.</p>
									</td>
								</tr>
								<tr valign="top" class="proxyjsoptions" style="<?php print $jsProxyOptions; ?>">
									<th scope="row">&nbsp;</th>
									<td style="padding-top:0px;">
										<h5 class="h">&nbsp;Proxy Include List</h5>
										<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[js_proxy_include]" placeholder="Leave blank to proxy all external scripts..."><?php if (isset($options['js_proxy_include'])) { echo htmlentities($options['js_proxy_include'],ENT_COMPAT,'utf-8'); } ?></textarea>
										<p class="description">Enter (parts of) external javascript files to proxy, e.g. <code>google-analytics.com/analytics.js</code> or <code>facebook.net/en_US/sdk.js</code>. One script per line. </p>
									</td>
								</tr>
								<tr valign="top" class="proxyjsoptions" style="<?php print $jsProxyOptions; ?>">
									<th scope="row">&nbsp;</th>
									<td style="padding-top:0px;">
										<h5 class="h">&nbsp;Proxy Exclude List</h5>
										<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[js_proxy_exclude]"><?php if (isset($options['js_proxy_exclude'])) { echo htmlentities($options['js_proxy_exclude'],ENT_COMPAT,'utf-8'); } ?></textarea>
										<p class="description">Enter (parts of) external javascript files to exclude from the proxy. One script per line.</p>
									</td>
								</tr>
								<tr valign="top" class="proxyjsoptions" style="<?php print $jsProxyOptions; ?>">
									<th scope="row">&nbsp;</th>
									<td style="padding-top:0px;">
										<h5 class="h">&nbsp;Proxy Preload List</h5>
										<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[js_proxy_preload]"><?php if ($jsPreload !== '') { echo htmlentities($jsPreload,ENT_COMPAT,'utf-8'); } ?></textarea>
										<p class="description">Enter the exact url or JSON object of external scripts to preload for "script injected" async script capture, e.g. <code>https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js</code>. This setting will enable the proxy to load the cache url instead of the WordPress PHP proxy url. One url per line.</p>
										<p class="description" style="margin-top:10px;">JSON objects must be placed on one line and contain a target url. Valid parameters are <code>url</code>, <code>regex</code>, <code>regex-flags</code> and <code>expire</code> (expire time in seconds).</p>
										<p class="description">Example JSON object: <code>{"regex": "^https://app\\.analytics\\.com/file\\.js\\?\\d+$", "regex-flags":"i", "url": "https://app.analytics.com/file.js", "expire": "2592000"}</code></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">Proxy Stylesheets</th>
									<td>
								        <label><input type="checkbox" name="abovethefold[css_proxy]" onchange="if (jQuery(this).is(':checked')) { jQuery('.proxycssoptions').show(); jQuery('.proxycssoptionshide').hide(); } else { jQuery('.proxycssoptions').hide(); jQuery('.proxycssoptionshide').show(); }" value="1"<?php if ($cssProxy) { print ' checked'; } ?>> Enabled</label>
								        <p class="description">Capture external stylesheets and load the files through a caching proxy. </p>
									</td>
								</tr>
								<tr valign="top" class="proxycssoptions" style="<?php print $cssProxyOptions; ?>">
									<th scope="row">&nbsp;</th>
									<td style="padding-top:0px;">
										<h5 class="h">&nbsp;Proxy Include List</h5>
										<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[css_proxy_include]" placeholder="Leave blank to proxy all external stylesheets..."><?php if (isset($options['css_proxy_include'])) { echo htmlentities($options['css_proxy_include'],ENT_COMPAT,'utf-8'); } ?></textarea>
										<p class="description">Enter (parts of) external stylesheets to proxy, e.g. <code>googleapis.com/jquery-ui.css</code>. One stylesheet per line. </p>
									</td>
								</tr>
								<tr valign="top" class="proxycssoptions" style="<?php print $cssProxyOptions; ?>">
									<th scope="row">&nbsp;</th>
									<td style="padding-top:0px;">
										<h5 class="h">&nbsp;Proxy Exclude List</h5>
										<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[css_proxy_exclude]"><?php if ($cssPreload !== '') { echo htmlentities($cssPreload,ENT_COMPAT,'utf-8'); } ?></textarea>
										<p class="description">Enter (parts of) external stylesheets to exclude from the proxy. One stylesheet per line.</p>
									</td>
								</tr>
								<tr valign="top" class="proxycssoptions" style="<?php print $cssProxyOptions; ?>">
									<th scope="row">&nbsp;</th>
									<td style="padding-top:0px;">
										<h5 class="h">&nbsp;Proxy Preload List</h5>
										<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[css_proxy_preload]"><?php if (isset($options['css_proxy_preload']) && is_array($options['css_proxy_preload'])) { echo htmlentities(implode("\n",$options['css_proxy_preload']),ENT_COMPAT,'utf-8'); } ?></textarea>
										<p class="description">Enter the exact url or JSON object of external stylesheets to preload for "script injected" async stylesheet capture, e.g. <code>https://fonts.googleapis.com/css?family=Open+Sans:400</code>. This setting will enable the proxy to load the cache url instead of the WordPress PHP proxy url. One url per line.</p>
										<p class="description" style="margin-top:10px;">JSON objects must be placed on one line and contain a target url. Valid parameters are <code>url</code>, <code>regex</code>, <code>regex-flags</code> and <code>expire</code> (expire time in seconds).</p>
										<p class="description">Example JSON object: <code>{"regex": "^https://app\\.analytics\\.com/file\\.css\\?\\d+$", "regex-flags":"i", "url": "https://app.analytics.com/file.css", "expire": "2592000"}</code></p>
									</td>
								</tr>
							
								
								<tr valign="top">
									<th scope="row">Proxy URL</th>
									<td>
			                            <input type="url" name="abovethefold[proxy_url]" style="width:100%;" placeholder="Leave blank for the default WordPress PHP based proxy url..." value="<?php if (isset($options['proxy_url'])) { echo htmlentities($options['proxy_url'],ENT_COMPAT,'utf-8'); } ?>" />
			                            <p class="description">Enter a custom proxy url to serve captured external resources. There are 2 parameters that can be used in the url: <code>{PROXY:URL}</code> and <code>{PROXY:TYPE}</code>.</p>
			                            <p class="description">E.g.: <code>https://nginx-proxy.mydomain.com/{PROXY:TYPE}/{PROXY:URL}</code>. Type is the string <u>js</u> or <u>css</u>.</p>
									</td>
								</tr>
							</table>
							<hr />
							<?php
								submit_button( __( 'Save' ), 'primary large', 'is_submit', false );
							?>
						</div>
					</div>

	<!-- End of #post_form -->

				</div>
			</div> <!-- End of #post-body -->
		</div> <!-- End of #poststuff -->
	</div> <!-- End of .wrap .nginx-wrapper -->
</form>

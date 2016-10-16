<?php

?>
<form method="post" action="<?php echo admin_url('admin-post.php?action=abovethefold_javascript'); ?>" class="clearfix">
	<?php wp_nonce_field('abovethefold'); ?>
	<div class="wrap abovethefold-wrapper">
		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
				<div id="post-body-content">
					<div class="postbox">
						<h3 class="hndle">
							<span><?php _e( 'Javascript Render Optimization', 'abovethefold' ); ?></span>
						</h3>
						<div class="inside testcontent">

						<p>Modern javascript libraries such as Google Analytics and Facebook API use an old method for async loading that blocks on CSSOM and is often slower than blocking script includes. The script-injected "async scripts" are targeted at very old browsers (IE8/9 and Android 2.2/2.3) while modern browsers would require a different method for optimal performance.</p>

						<p><a href="https://www.igvita.com/2014/05/20/script-injected-async-scripts-considered-harmful/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=Above%20The%20Fold%20Optimization" target="_blank"><img src="<?php print WPABTF_URI; ?>admin/benchmark-script-injected-async.png" style="width:100%;height:auto;max-width:770px;" alt="script-injected async scripts benchmark" /></a></p>

						<p>More information and benchmarks can be found in <a href="https://www.igvita.com/2014/05/20/script-injected-async-scripts-considered-harmful/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=Above%20The%20Fold%20Optimization" target="_blank">this blog</a> from Google web performance engineer <a href="https://www.igvita.com/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=Above%20The%20Fold%20Optimization" target="_blank">Ilya Grigorik</a>, author of the O'Reilly book <a href="https://www.amazon.com/High-Performance-Browser-Networking-performance/dp/1449344763/" target="_blank">High Performance Browser Networking</a> (<a href="https://hpbn.co/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=Above%20The%20Fold%20Optimization" target="_blank">free online</a>).</p>

						<table class="form-table">

							<!--tr valign="top">
								<th scope="row" style="padding-top:0px;">Optimize Javascript Delivery</th>
								<td style="padding-top:0px;">
									<label><input type="checkbox" name="abovethefold[cssdelivery]" value="1"<?php if (!isset($options['cssdelivery']) || intval($options['cssdelivery']) === 1) { print ' checked'; } ?> onchange="if (jQuery(this).is(':checked')) { jQuery('.cssdeliveryoptions').show(); } else { jQuery('.cssdeliveryoptions').hide(); }"> Enabled</label>
									<p class="description">When enabled, Javascript files are loaded asynchronously using the best practices as described above.</p>
								</td>
							</tr>
							<tr valign="top" class="cssdeliveryoptions" style="<?php if (isset($options['cssdelivery']) && intval($options['cssdelivery']) !== 1) { print 'display:none;'; } ?>">
								<td colspan="2" style="padding-top:0px;">

									<div class="abtf-inner-table">

										<h3 class="h"><span>Javascript Delivery Optimization</span></h3>
										<div class="inside">
											<table class="form-table">
												<tr valign="top">
													<th scope="row">Load Position</th>
													<td>
														<select name="abovethefold[cssdelivery_position]">
															<option value="header"<?php if (isset($options['cssdelivery_position']) && $options['cssdelivery_position'] === 'header') { print ' selected'; } ?>>Header</option>
															<option value="footer"<?php if (!isset($options['cssdelivery_position']) || empty($options['cssdelivery_position']) || $options['cssdelivery_position'] === 'footer') { print ' selected'; } ?>>Footer</option>
														</select>
														<p class="description">Select the position where the async loading of javascript files will start.</p>
													</td>
												</tr>
												<tr valign="top" class="enhancedloadcssoptions" style="<?php if (isset($options['loadcss_enhanced']) && intval($options['loadcss_enhanced']) !== 1) { print 'display:none;'; } ?>">
													<th scope="row">Load Delay</th>
													<td>
														<table cellpadding="0" cellspacing="0" border="0">
															<tr>
																<td valign="top" style="padding:0px;vertical-align:top;"><input type="number" min="0" max="3000" step="1" name="abovethefold[cssdelivery_renderdelay]" size="10" value="<?php print ((empty($options['cssdelivery_renderdelay']) || $options['cssdelivery_renderdelay'] === 0) ? '' : htmlentities($options['cssdelivery_renderdelay'],ENT_COMPAT,'utf-8')); ?>" onchange="if (jQuery(this).val() === '0') { jQuery(this).val(''); }" placeholder="0 ms" /></td>
																<td valign="top" style="padding:0px;vertical-align:top;padding-left:10px;font-size:11px;"></td>
															</tr>
														</table>
														<p class="description" style="clear:both;">Optionally, enter a time in milliseconds to delay the loading of javascript files.</p>

													</td>
												</tr>
												<tr valign="top">
													<th scope="row">Ignore List</th>
													<td>
														<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[cssdelivery_ignore]"><?php if (isset($options['cssdelivery_ignore'])) { echo htmlentities($options['cssdelivery_ignore'],ENT_COMPAT,'utf-8'); } ?></textarea>
														<p class="description">Javascript files to ignore in javascript delivery optimization (one file per line). The files will be left untouched in the HTML.</p>
													</td>
												</tr>
												<tr valign="top">
													<th scope="row">Remove List</th>
													<td>
														<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[cssdelivery_remove]"><?php if (isset($options['cssdelivery_remove'])) { echo htmlentities($options['cssdelivery_remove'],ENT_COMPAT,'utf-8'); } ?></textarea>
														<p class="description">Javascript files to remove (one file per line). This feature enables to remove plugin related javascript files.</p>
													</td>
												</tr>
											</table>
										</div>

									</div>
								</td>
							</tr-->

							<tr valign="top">
								<th scope="row">Proxy External Scripts</th>
								<td>
                                    <label><input type="checkbox" name="abovethefold[js_proxy]" onchange="if (jQuery(this).is(':checked')) { jQuery('.proxyjsoptions').show(); } else { jQuery('.proxyjsoptions').hide(); }" value="1"<?php if (isset($options['js_proxy']) && intval($options['js_proxy']) === 1) { print ' checked'; } ?>> Enabled</label>
                                    <p class="description">Capture external scripts and load the scripts through a caching proxy. This feature enables to pass the <a href="https://developers.google.com/speed/docs/insights/LeverageBrowserCaching?hl=<?php print $lgcode;?>" target="_blank">Leverage browser caching</a> rule from Google PageSpeed Insights.</p>
								</td>
							</tr>
							<tr valign="top" class="proxyjsoptions" style="<?php if (!isset($options['js_proxy']) || intval($options['js_proxy']) !== 1) { print 'display:none;'; } ?>">
								<th scope="row">Proxy Include List</th>
								<td>
									<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[js_proxy_include]" placeholder="Leave blank to proxy all external files..."><?php if (isset($options['js_proxy_include'])) { echo htmlentities($options['js_proxy_include'],ENT_COMPAT,'utf-8'); } ?></textarea>
									<p class="description">Enter (parts of) external javascript files to proxy, e.g. <code>google-analytics.com/analytics.js</code> or <code>facebook.net/en_US/sdk.js</code>. One script per line. </p>
								</td>
							</tr>
							<tr valign="top" class="proxyjsoptions" style="<?php if (!isset($options['js_proxy']) || intval($options['js_proxy']) !== 1) { print 'display:none;'; } ?>">
								<th scope="row">Proxy Exclude List</th>
								<td>
									<textarea style="width: 100%;height:50px;font-size:11px;" name="abovethefold[js_proxy_exclude]"><?php if (isset($options['js_proxy_exclude'])) { echo htmlentities($options['js_proxy_exclude'],ENT_COMPAT,'utf-8'); } ?></textarea>
									<p class="description">Enter (parts of) external javascript files to exclude from the proxy. One script per line.</p>
								</td>
							</tr>

							<tr valign="top">
								<th scope="row">
									Lazy Load Scripts<a name="lazyscripts">&nbsp;</a>
								</th>
								<td>
									<label><input type="checkbox" name="abovethefold[lazyscripts_enabled]" value="1"<?php if (isset($options['lazyscripts_enabled']) && intval($options['lazyscripts_enabled']) === 1) { print ' checked'; } ?> onchange="if (jQuery(this).is(':checked')) { jQuery('.lazyscriptsoptions').show(); } else { jQuery('.lazyscriptsoptions').hide(); }"> Enabled</label>
									<p class="description">When enabled, the widget module from <a href="https://github.com/ressio/lazy-load-xt#widgets" target="_blank">jQuery Lazy Load XT</a> is loaded to enable lazy loading of inline scripts such as Facebook like and Twitter follow buttons.</p>
										<p class="description lazyscriptsoptions" style="<?php if (isset($options['lazyscripts_enabled']) && intval($options['lazyscripts_enabled']) === 1) { } else { print 'display:none;'; } ?>">This option is compatible with <a href="<?php print admin_url('plugin-install.php?s=Lazy+Load+XT&tab=search&type=term'); ?>">WordPress lazy load plugins</a> that use Lazy Load XT. Those plugins are <u>not required</u> for this feature.</p>
										<pre style="float:left;width:100%;overflow:auto;<?php if (isset($options['lazyscripts_enabled']) && intval($options['lazyscripts_enabled']) === 1) { } else { print 'display:none;'; } ?>" class="lazyscriptsoptions">
<?php print htmlentities('<div data-lazy-widget><!--
<div id="fblikebutton_1" class="fb-like" data-href="https://pagespeed.pro/" 
data-layout="standard" data-action="like" data-show-faces="true" data-share="true"></div>
<script>
FB.XFBML.parse(document.getElementById(\'fblikebutton_1\').parentNode||null);
</script>
--></div>');?>
										</pre>
								</td>
							</tr>
						</table>
						<hr />
						<?php
							submit_button( __( 'Save' ), 'primary large', 'is_submit', false );
						?>&nbsp;
						<?php
							submit_button( __( 'Clear Page Caches' ), 'large', 'clear_pagecache', false );
						?>

						</div>
					</div>


	<!-- End of #post_form -->

				</div>
			</div> <!-- End of #post-body -->
		</div> <!-- End of #poststuff -->
	</div> <!-- End of .wrap .nginx-wrapper -->
</form>

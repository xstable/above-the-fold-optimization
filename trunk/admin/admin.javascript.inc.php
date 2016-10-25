<?php

?>
<form method="post" action="<?php echo admin_url('admin-post.php?action=abtf_javascript_update'); ?>" class="clearfix">
	<?php wp_nonce_field('abovethefold'); ?>
	<div class="wrap abovethefold-wrapper">
		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
				<div id="post-body-content">
					<div class="postbox">
						<h3 class="hndle">
							<span><?php _e( 'Javascript Optimization', 'abovethefold' ); ?></span>
						</h3>
						<div class="inside testcontent">

						<p>Modern javascript libraries such as Google Analytics and Facebook API use an old method for async loading that blocks on CSSOM and is often slower than blocking script includes. Script-injected "async scripts" are targeted at old browsers (IE8/9 and Android 2.2/2.3) while modern browsers would require a different method for optimal performance.</p>

						<p>More information and benchmarks can be found in <a href="https://www.igvita.com/2014/05/20/script-injected-async-scripts-considered-harmful/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=Above%20The%20Fold%20Optimization" target="_blank">this blog</a> by <a href="https://www.igvita.com/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=Above%20The%20Fold%20Optimization" target="_blank">Ilya Grigorik</a>, web performance engineer at Google and author of the O'Reilly book <a href="https://www.amazon.com/High-Performance-Browser-Networking-performance/dp/1449344763/" target="_blank">High Performance Browser Networking</a> (<a href="https://hpbn.co/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=Above%20The%20Fold%20Optimization" target="_blank">free online</a>).</p>


						<table class="form-table">
							
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
						<p>More javascript optimization tools will be added in next versions.</p>
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

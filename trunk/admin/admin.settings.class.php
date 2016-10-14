<?php 

// author info
require_once('admin.author.class.php');

?>
<form method="post" action="<?php echo admin_url('admin-post.php?action=abovethefold_update'); ?>" data-addccss="<?php echo admin_url('admin-post.php?action=abovethefold_add_ccss'); ?>" data-delccss="<?php echo admin_url('admin-post.php?action=abovethefold_del_ccss'); ?>" id="abtf_settings_form" class="clearfix" style="margin-top:0px;">
	<?php wp_nonce_field('abovethefold'); ?>
	<div class="wrap abovethefold-wrapper">
		<div id="poststuff">
			<div id="post-body" class="metabox-holder">
				<div id="post-body-content">
					<div class="postbox">

						<h3 class="hndle">
							<span><?php _e( 'Critical CSS', 'abovethefold' ); ?></span>
						</h3>
						<div class="inside testcontent">

							<p>Critical CSS is the minimum CSS required to render above the fold content (<a href="https://developers.google.com/speed/docs/insights/PrioritizeVisibleContent?hl=<?php print $lgcode;?>" target="_blank">documentation by Google</a>).</p>

							<p><a href="https://github.com/addyosmani/critical-path-css-tools" target="_blank">This article</a> by a Google engineer provides information about the available methods for creating critical path CSS. Check out <a href="https://criticalcss.com/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=PageSpeed.pro%3A%20Above%20The%20Fold%20Optimization" target="_blank">CriticalCSS.com</a> for an easy automated critical path CSS generator. </p>

							<table class="form-table">
								<tr valign="top">
									<td class="criticalcsstable">

										<h3 style="padding:0px;margin:0px;margin-bottom:10px;">Critical CSS</h3>

										<p class="description" style="margin-bottom:1em;"><?php _e('Configure the Critical Path CSS to be inserted inline into the <code>&lt;head&gt;</code> of the page.', 'abovethefold'); ?></p>

										<ul class="menu ui-sortable" style="width:auto!important;margin-top:0px;padding-top:0px;">
											
											<?php
												require_once('admin.settings.criticalcss.inc.php');
											?>

											<?php
												require_once('admin.settings.conditionalcss.inc.php');
											?>
										</ul>
									</td>
								</tr>
							</table>

							<table class="form-table">

								<?php

									/**
									 * CSS Delivery Optimization Settings
									 */
									require_once('admin.settings.optimizecss.inc.php');
								
									/**
									 * Google Webfont Optimization Settings
									 */
									require_once('admin.settings.webfontoptimizer.inc.php');
								?>

								<tr valign="top">
									<th scope="row">Admin Bar</th>
									<td>
                                        <label><input type="checkbox" name="abovethefold[adminbar]" value="1"<?php if (!isset($options['adminbar']) || intval($options['adminbar']) === 1) { print ' checked'; } ?>> Enabled</label>
                                        <p class="description">Show a <code>PageSpeed</code> menu in the top admin bar with links to website speed and security tests such as Google PageSpeed Insights.</p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">Clear Page Caches</th>
									<td>
                                        <label><input type="checkbox" name="abovethefold[clear_pagecache]" value="1"<?php if (!isset($options['clear_pagecache']) || intval($options['clear_pagecache']) === 1) { print ' checked'; } ?>> Enabled</label>
                                        <p class="description">If enabled, the page related caches of <a href="https://github.com/optimalisatie/above-the-fold-optimization/tree/master/trunk/modules/plugins/" target="_blank">supported plugins</a> is cleared when updating the above the fold settings.</p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">Debug Modus</th>
									<td>
                                        <label><input type="checkbox" name="abovethefold[debug]" value="1"<?php if (isset($options['debug']) && intval($options['debug']) === 1) { print ' checked'; } ?>> Enabled</label>
                                        <p class="description">Show debug info in the browser console for logged in admin-users.</p>
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
	</div> <!-- End of .wrap -->
</form>
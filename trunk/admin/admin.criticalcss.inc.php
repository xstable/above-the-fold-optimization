<?php 

/**
 * Paths
 */
$pageoptions = array(
	'<option value="/">/ - Root</option>'
);


// Get random post
$args = array( 'post_type' => 'post', 'posts_per_page' => -1 );
query_posts($args);
if (have_posts()) {
	$pageoptions[] = '<optgroup label="'.__('Posts').'">';
	while (have_posts()) {
		the_post();
		$pageoptions[] = '<option value="'.str_replace(get_option('siteurl'),'',get_permalink($wp_query->post->ID)).'">' . str_replace(get_option('siteurl'),'',get_permalink($wp_query->post->ID)) . '</option>';
	}
	$pageoptions[] = '</optgroup>';
}

// Get random page
$args = array( 'post_type' => 'page', 'posts_per_page' => -1 );
query_posts($args);
if (have_posts()) {
	$pageoptions[] = '<optgroup label="'.__('Pages').'">';
	while (have_posts()) {
		the_post();
		$pageoptions[] = '<option value="'.str_replace(get_option('siteurl'),'',get_permalink($wp_query->post->ID)).'">' . str_replace(get_option('siteurl'),'',get_permalink($wp_query->post->ID)) . '</option>';
	}
	$pageoptions[] = '</optgroup>';
}

// Random category
$taxonomy = 'category';
$terms = get_terms($taxonomy);
shuffle ($terms);
if ($terms) {
	$pageoptions[] = '<optgroup label="'.__('Categories').'">';
	foreach($terms as $term) {
		$pageoptions[] = '<option value="'.str_replace(get_option('siteurl'),'',get_category_link( $term->term_id )).'">' . str_replace(get_option('siteurl'),'',get_category_link( $term->term_id )) . '</option>';
	}
	$pageoptions[] = '</optgroup>';
}

?>
<form method="post" action="<?php echo admin_url('admin-post.php?action=abtf_criticalcss_update'); ?>" data-addccss="<?php echo admin_url('admin-post.php?action=abtf_add_ccss'); ?>" data-delccss="<?php echo admin_url('admin-post.php?action=abtf_delete_ccss'); ?>" id="abtf_settings_form" class="clearfix" style="margin-top:0px;">
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

							<div style="background:#f1f1f1;padding:10px;margin-top:10px;margin-bottom:20px;">
								Critical CSS is the minimum CSS required to render above the fold content (<a href="https://developers.google.com/speed/docs/insights/PrioritizeVisibleContent?hl=<?php print $lgcode;?>" target="_blank">documentation by Google</a>).
								<br />
								<a href="https://github.com/addyosmani/critical-path-css-tools" target="_blank">This article</a> by a Google engineer provides information about the available methods for creating critical path CSS. Check out <a href="https://criticalcss.com/#utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=PageSpeed.pro%3A%20Above%20The%20Fold%20Optimization" target="_blank">CriticalCSS.com</a> for an easy automated critical path CSS generator.
							</div>

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
								<tr valign="top">
									<td class="criticalcsstable">
										<br />

										<h3 style="padding:0px;margin:0px;margin-bottom:10px;"><?php _e( 'Extract Full CSS', 'abovethefold' ); ?></h3>

										<p class="description">For the creation of Critical Path CSS you need the full CSS of a page. This tool allows you to extract the full CSS from any url and optionally to select the specific CSS files you want to extract.</p>
										<p class="description" style="margin-bottom:1em;">You can quickly output the full CSS of any url by adding the query string <code><strong>?extract-css=<?php print md5(SECURE_AUTH_KEY . AUTH_KEY); ?>&amp;output=print</strong></code>.</p>

											<select id="fullcsspages"><option value=""></option><?php print implode('',$pageoptions); ?></select>

											<div style="margin-top:10px;">
											<button type="button" id="fullcsspages_dl" rel="<?php print md5(SECURE_AUTH_KEY . AUTH_KEY); ?>" class="button button-large">Download</button>
											<button type="button" id="fullcsspages_print" rel="<?php print md5(SECURE_AUTH_KEY . AUTH_KEY); ?>" class="button button-large">Print</button>
											</div>
											<br /><br />
									</td>
								</tr>
							</table>
						</div>
					</div>

					<!-- End of #post_form -->

				</div>
			</div> <!-- End of #post-body -->
		</div> <!-- End of #poststuff -->
	</div> <!-- End of .wrap -->
</form>
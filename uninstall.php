<?php
/**
 * Removes the plugin's settings when it is deleted.
 *
 * Posts, their kinds and their response data are kept, so they are still
 * there if the plugin is installed again.
 *
 * @package Post_Kinds
 * @since   4.0.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Deletes the plugin's options and scheduled events on the current site.
 *
 * @since 4.0.0
 */
function post_kinds_uninstall_site() {
	$options = array(
		// Settings.
		'kind_termslist',
		'kind_default',
		'kind_embeds',
		'kind_firehose',
		'kind_bottom',
		'kind_display',
		'kind_kses',
		'kind_title',
		'kind_base',
		// Upgrade state (Kind_Upgrade).
		'kind_upgrade_version',
		'kind_upgrade_last_id',
		'kind_upgrade_assigned_article',
		// Settings from earlier versions.
		'kind_protection',
		'iwt_options',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Kind_Upgrade's batches, and Kind_Plugins' citation retries, which are scheduled per post.
	wp_unschedule_hook( 'kind_upgrade_batch' );
	wp_unschedule_hook( 'post_kinds_enrich_citation' );
}

if ( is_multisite() ) {
	foreach ( get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) as $post_kinds_site_id ) {
		switch_to_blog( $post_kinds_site_id );
		post_kinds_uninstall_site();
		restore_current_blog();
	}
} else {
	post_kinds_uninstall_site();
}

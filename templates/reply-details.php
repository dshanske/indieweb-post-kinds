<?php
/**
 * Details tab of the Post Kinds metabox.
 *
 * Included by reply-metabox.php; includes reply-time.php.
 *
 * @package Post_Kinds
 * @since   3.0.0
 *
 * @var Kind_Post $kind_post The post being edited.
 * @var array     $cite      The citation, normalized by Kind_Post::normalize_cite().
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="kind-details" class="hide-if-js">
		<h4><?php esc_html_e( 'Information on what you are responding to', 'indieweb-post-kinds' ); ?></h4>
	<label for="cite_summary">
		<?php esc_html_e( 'Summary/Quote/Caption', 'indieweb-post-kinds' ); ?>
	<textarea name="cite_summary" id="cite_summary" data-role="none" class="widefat"><?php echo esc_textarea( html_entity_decode( wp_strip_all_tags( (string) $cite['summary'] ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ); ?></textarea>
	</label>
	<p class="field-row">
	<label for="cite_publication" class="three-quarters">
		<?php esc_html_e( 'Site Name/Publication/Album', 'indieweb-post-kinds' ); ?>
			<input type="text" name="cite_publication" id="cite_publication" class="widefat" value="<?php echo esc_attr( $cite['publication'] ); ?>" />
	</label>
	</p>
	<p class="field-row">
			<?php echo Kind_Metabox::kind_the_time( 'cite_published', __( 'Published/Released', 'indieweb-post-kinds' ), $cite['published'], 'published' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Kind_Metabox::kind_the_time() escapes its output. ?>
		<?php echo Kind_Metabox::kind_the_time( 'cite_updated', __( 'Updated', 'indieweb-post-kinds' ), $cite['updated'], 'updated' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Kind_Metabox::kind_the_time() escapes its output. ?>
	</p>
	<label for="cite_tags">
		<?php esc_html_e( 'Tags (semicolon separated)', 'indieweb-post-kinds' ); ?>
	<textarea name="cite_tags" id="cite_tags" data-role="none" class="widefat"><?php echo esc_textarea( $cite['category'] ); ?></textarea>
	</label>
	<p class="field-row">
	<label for="cite_featured" class="three-quarters">
		<?php esc_html_e( 'Featured Image', 'indieweb-post-kinds' ); ?>
			<input type="text" name="cite_featured" id="cite_featured" class="widefat" value="<?php echo esc_attr( $cite['featured'] ); ?>" />
	</label>
	</p>
	<?php require_once 'reply-time.php'; ?>
</div>

<?php

/**
 * Provides the 'Main' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */

if ( isset( $cite['category'] ) ) {
	$tags = $cite['category'];
	if ( is_array( $tags ) ) {
		$tags = implode( ';', $tags );
	}
} else {
	$tags = '';
}

// FIXME: Discards extra URLs as currently unsupported
if ( isset( $cite['url'] ) && is_array( $cite['url'] ) ) {
	$cite['url'] = array_shift( $cite['url'] );
}

?>
<div class="inside">
<h4><?php _e( 'Add the URL and/or the Name/Summary You Wish to Respond To. The Retrieve button will retrieve the URL and attempt to set the values.', 'indieweb-post-kinds' ); ?></h4>
	<div id="kindmetatab-main">
			<?php echo self::metabox_text( 'cite_url', __( 'URL', 'indieweb-post-kinds' ), ifset( $cite['url'] ) ); ?>
		<?php if ( version_compare( PHP_VERSION, '5.3', '>' ) ) { ?>
		<button type="button" class="kind-retrieve-button button-primary">Retrieve</button>
		<?php } ?>
		<br/>

		<?php echo self::metabox_text( 'cite_name', __( 'Name/Title', 'indieweb-post-kinds' ), ifset( $cite['name'] ) ); ?>
				<br />

		<?php echo self::metabox_text( 'cite_summary', __( 'Summary/Quote', 'indieweb-post-kinds' ), ifset( $cite['summary'] ), 'textarea' ); ?>
		<br />

		<?php echo self::metabox_text( 'cite_tags', __( 'Tags (semicolon separated)', 'indieweb-post-kinds' ), $tags, 'textarea' ); ?>
		<br />
		</div><!-- #kindmetatab-main -->
	

</div>

<?php

/**
 * Provides the 'Main' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */
// FIXME: This is temporary
if ( is_array( $url ) ) {
	$url = $url[0];
}

if (  isset( $cite['category'] ) )  {
	$tags = implode( ';', $cite['category'] );
}
else{ 
	$tags = '';
}

?>
<div class="inside">
<h4><?php _e( 'Add the URL and/or the Name/Summary You Wish to Respond To. The Retrieve button will retrieve the URL and attempt to set the values.', 'indieweb-post-kinds' ); ?></h4>
	<div id="kindmetatab-main">
 		<?php echo self::metabox_text( 'cite_url', __( 'URL', 'indieweb-post-kinds' ), ifset( $url ), $type = 'text' ); ?>
		<?php if ( version_compare( PHP_VERSION, '5.3', '>' ) ) { ?>
		<button type="button" class="kind-retrieve-button button-primary">Retrieve</button>
		<?php } ?>
		<br/>

		<?php echo self::metabox_text( 'cite_name', __( 'Name/Title', 'indieweb-post-kinds' ), ifset( $cite['name'] ), $type = 'text' ); ?>
                <br />

		<?php echo self::metabox_text( 'cite_summary', __( 'Summary/Quote', 'indieweb-post-kinds' ), ifset( $cite['summary'] ), $type = 'textarea' ); ?>
		<br />

		<?php echo self::metabox_text( 'cite_tags', __( 'Tags (semicolon separated)', 'indieweb-post-kinds' ), $tags, $type = 'textarea' ); ?>
		<br />
		</div><!-- #kindmetatab-main -->
	

</div>

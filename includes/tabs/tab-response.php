<?php

/**
 * Provides the 'Response' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */
// FIXME: This is temporary
if ( is_array( $url ) ) {
	$url = $url[0];
}
?>
<div class="inside">
	<p>Add the URL and/or the Name You Wish to Respond To</p>
	<div id="kindmetatab-response">
			<label for="url"><?php _e( 'URL', 'indieweb-post-kinds' ); ?></label><br/>
			<input type="url" name="url" id="cite_url" size="70" value="<?php echo ifset( $url ); ?>" />
			<?php if ( version_compare( PHP_VERSION, '5.3', '>' ) ) { ?>
				<button type="button" class="kind-retrieve-button button-primary">Retrieve</button>
			<?php } ?>
			<br/>
	  <label for="name"><?php _e( 'Name/Title', 'indieweb-post-kinds' ); ?></label><br/>
	  <input type="text" name="cite[name]" id="cite_name" size="70" value="<?php echo ifset( $cite['name'] ); ?>" />
			<br />

		</div><!-- #kindmetatab-resources -->
	

</div>

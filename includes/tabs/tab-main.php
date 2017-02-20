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
?>
<div class="inside">
<h4><?php _e( 'Add the URL and/or the Name/Summary You Wish to Respond To. The Retrieve button will retrieve the URL and attempt to set the values.', 'indieweb-post-kinds' ); ?></h4>
	<div id="kindmetatab-main">
	<label for="url"><?php _e( 'URL', 'indieweb-post-kinds' ); ?></label><br/>
			<input type="url" name="url" id="cite_url" size="70" value="<?php echo ifset( $url ); ?>" />
			<?php if ( version_compare( PHP_VERSION, '5.3', '>' ) ) { ?>
				<button type="button" class="kind-retrieve-button button-primary">Retrieve</button>
			<?php } ?>
	<br/>
	  <label for="name"><?php _e( 'Name/Title', 'indieweb-post-kinds' ); ?></label><br/>
	  <input type="text" name="cite[name]" id="cite_name" size="70" value="<?php echo ifset( $cite['name'] ); ?>" />
			<br />
 	   <label for="summary"><?php _e( 'Summary/Quote', 'indieweb-post-kinds' ); ?></label><br/>
	   <textarea name="cite[summary]" id="cite_summary" cols="70"></textarea><br/>
	  <label for="tags"><?php _e('Tags (one tag per line)', 'indieweb-post-kinds' ); ?></label><br />
	   <textarea name="cite[tags]" id="cite_tags" style="resize: none;" data-role="none" cols="40" rows"4"></textarea></br />
	<br />
		</div><!-- #kindmetatab-main -->
	

</div>

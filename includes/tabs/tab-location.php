<?php

/**
 * Provides the 'Location' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */
?>
<div class="inside">
	<p>Add the URL and/or the Name You Wish to Respond To</p>
	<div id="kindmetatab-location">
			<label for="url"><?php _e( 'URL', 'Post kind' ); ?></label><br/>
			<input type="url" name="url" id="cite_url" size="70" value="<?php echo ifset( $url ); ?>" />
	  <button type="button" class="kind-retrieve-button button-primary">Retrieve</button>
			<br/>
	  <label for="name"><?php _e( 'Name/Title', 'Post kind' ); ?></label><br/>
	  <input type="text" name="cite[name]" id="cite_name" size="70" value="<?php echo ifset( $cite['name'] ); ?>" />
			<br />

		</div><!-- #kindmetatab-location -->
	

</div>

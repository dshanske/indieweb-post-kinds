<?php
 
/**
 * Provides the 'Response' view for the corresponding tab in the Post Meta Box.
 *
 *
 * @package    Indieweb_Post_Kinds
 */
?>
 
<div class="inside">
    <p>Add URL.</p>
    <div id="kindmetatab-response">
			<label for="url"><?php _e( 'URL', 'Post kind' ); ?></label><br/>
			<input type="url" name="url" id="kind-url" size="70" />
      <button type="button" class="kind-retrieve-button button-primary">Retrieve</button>
			<br/>
      <label for="name"><?php _e( 'Name/Title', 'Post kind' ); ?></label><br/>
      <input type="text" name="name" size="70" />
			<br />

		</div><!-- #kindmetatab-resources -->
    

</div>

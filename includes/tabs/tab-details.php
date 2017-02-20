<?php

/**
 * Provides the 'Citation' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */
?>
	
<div class="inside hidden">
	<p><?php _e( 'Information on what you are responding to', 'indieweb-post-kinds' ); ?></p>
		<div id="kindmetatab-citation">
	 <br/>
	  <label for="publication"><?php _e( 'Site Name/Publication/Album', 'indieweb-post-kinds' ); ?></label><br/>
	  <input type="text" name="cite[publication]" id="cite_publication" size="70" value="<?php echo ifset( $cite['publication'] ); ?>"/><br/>


			<label for="featured"><?php _e( 'Featured Image/Site Icon', 'indieweb-post-kinds' ); ?></label><br/>
	  <input type="text" name="cite[featured]" id="cite_featured" size="70" value="<?php echo ifset( $cite['featured'] ); ?>" />
	 <br/>
	 <?php echo self::kind_the_time( 'published', __( 'Published/Released', 'indieweb-post-kinds' ), $time ); ?>
         <br />
	<?php echo self::kind_the_time( 'updated', __( 'Updated', 'indieweb-post-kinds' ), $time ); ?>
	  <br />

		</div> <!-- #kindmetatab-citation -->
</div>

<?php

/**
 * Provides the 'Citation' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */
?>
	
<div class="inside hidden">
	<p><?php _e( 'Details of the site you are responding to', 'indieweb-post-kinds' ); ?></p>
		<div id="kindmetatab-citation">
	 <br/>
	<label for="summary"><?php _e( 'Summary', 'indieweb-post-kinds' ); ?></label><br/>
	 <textarea name="cite[summary]" id="cite_summary" cols="70"><?php echo ifset( $cite['summary'] ); ?></textarea><br/>
	  <label for="publication"><?php _e( 'Site Name/Publication/Album', 'indieweb-post-kinds' ); ?></label><br/>
	  <input type="text" name="cite[publication]" id="cite_publication" size="70" value="<?php echo ifset( $cite['publication'] ); ?>"/><br/>


			<label for="featured"><?php _e( 'Featured Image', 'indieweb-post-kinds' ); ?></label><br/>
	  <input type="text" name="cite[featured]" id="cite_featured" size="70" value="<?php echo ifset( $cite['featured'] ); ?>" />
	 <br/>
	  <br />

		</div> <!-- #kindmetatab-citation -->
	<div id="kindmetatab-author">
	  <label for="author_name"><?php _e( 'Author/Artist Name', 'indieweb-post-kinds' ); ?></label><br/>
	  <input type="text" name="author[name]" id="author_name" size="70" value="<?php echo ifset( $author['name'] ); ?>" />
	  <br />

	  <label for="url"><?php _e( 'Author/Artist URL', 'indieweb-post-kinds' ); ?></label><br/>
	  <input type="url" name="author[url]" id="author_url" size="70" value="<?php echo ifset( $author['url'] ); ?>" />
	  <br />


	  <label for="author_photo"><?php _e( 'Author Photo', 'indieweb-post-kinds' ); ?></label><br/>
	  <input type="text" name="author[photo]" id="author_photo" size="70" value="<?php echo ifset( $author['photo'] ); ?>" />
	  <br />
	</div><!-- #kindmetatab-author -->


</div>

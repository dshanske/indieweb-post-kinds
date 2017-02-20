<?php

/**
 * Provides the 'Author' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */
?>
	
<div class="inside hidden">
	<div id="kindmetatab-author">
	<h4> <?php _e( 'Information on the Author or Artist of the Piece', 'indieweb-post-kinds' ); ?></h4>
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

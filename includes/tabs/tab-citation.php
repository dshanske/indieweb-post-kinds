<?php
 
/**
 * Provides the 'Citation' view for the corresponding tab in the Post Meta Box.
 *
 *
 * @package    Indieweb_Post_Kinds
 */
?>
 
<div class="inside hidden">
    <p>Citation.</p>
		<div id="kindmetatab-citation">
      <label for="publication"><?php _e( 'Site Name/Publication/Album', 'Post kind' ); ?></label><br/>
      <input type="text" name="publication" id="publication" size="70" value="<?php echo ifset($kindmeta['publication']); ?>"/><br/>

      <label for="published"><?php _e( 'Publish Date', 'Post kind' ); ?></label><br/>
      <input type="text" name="published" id="published" class="datepick" size="70" value="<?php echo ifset($kindmeta['published']); ?>"/><br/>
      <label for="updated"><?php _e( 'Last Updated Date', 'Post kind' ); ?></label><br/>
      <input type="text" name="updated" id="updated" size="70" class="datepick" value="<?php echo ifset($kindmeta['updated']); ?>"/><br/>



			<label for="featured"><?php _e( 'Featured Image', 'Post kind' ); ?></label><br/>
      <input type="text" name="featured" id="featured" size="70" value="<?php echo ifset($kindmeta['featured']); ?>" />
     <br/>
		 <br/><br/>
		 <label for="cite_content"><?php _e( 'Summary', 'Post kind' ); ?></label><br/>
		 <textarea name="cite_content" id="cite_content" cols="70"><?php echo ifset($kindmeta['summary']); ?></textarea>


      <br />

		</div> <!-- #kindmetatab-citation -->
    <div id="kindmetatab-author">
      <label for="name"><?php _e( 'Author/Artist Name', 'Post kind' ); ?></label><br/>
      <input type="text" name="author_name" id="author_name" size="70" value="<?php echo ifset($author['name']); ?>" />
      <br />
      <label for="name"><?php _e( 'Author Photo', 'Post kind' ); ?></label><br/>
      <input type="text" name="author_photo" id="author_photo" size="70" value="<?php echo ifset($author['photo']); ?>" />
      <br />
    </div><!-- #kindmetatab-author -->


</div>

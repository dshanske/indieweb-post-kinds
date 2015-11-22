<?php
 
/**
 * Provides the 'Citation' view for the corresponding tab in the Post Meta Box.
 *
 *
 * @package    Indieweb_Post_Kinds
 */
?>
 
<div class="inside hidden">
    <p><?php _e("Details of the site you are responding to", "Post Kinds"); ?></p>
		<div id="kindmetatab-citation">
      <label for="publication"><?php _e( 'Site Name/Publication/Album', 'Post kind' ); ?></label><br/>
      <input type="text" name="cite[publication]" id="cite_publication" size="70" value="<?php echo ifset($cite['publication']); ?>"/><br/>

      <label for="published"><?php _e( 'Publish Date', 'Post kind' ); ?></label><br/>
      <input type="text" name="cite[published]" id="cite_published" class="datepick" size="15" value="<?php echo ifset($cite['published']); ?>"/><br/>
      <label for="updated"><?php _e( 'Last Updated Date', 'Post kind' ); ?></label><br/>
      <input type="text" name="cite[updated]" id="cite_updated" size="15" class="datepick" value="<?php echo ifset($cite['updated']); ?>"/><br/>



			<label for="featured"><?php _e( 'Featured Image', 'Post kind' ); ?></label><br/>
      <input type="text" name="cite[featured]" id="cite_featured" size="70" value="<?php echo ifset($cite['featured']); ?>" />
     <br/>
		 <br/><br/>
		 <label for="summary"><?php _e( 'Summary', 'Post kind' ); ?></label><br/>
		 <textarea name="cite[summary]" id="cite_summary" cols="70"><?php echo ifset($cite['summary']); ?></textarea>


      <br />

		</div> <!-- #kindmetatab-citation -->
    <div id="kindmetatab-author">
      <label for="author_name"><?php _e( 'Author/Artist Name', 'Post kind' ); ?></label><br/>
      <input type="text" name="author[name]" id="author_name" size="70" value="<?php echo ifset($author['name']); ?>" />
      <br />

      <label for="url"><?php _e( 'Author/Artist URL', 'Post kind' ); ?></label><br/>
      <input type="url" name="author[url]" id="author_url" size="70" value="<?php echo ifset($author['url']); ?>" />
      <br />


      <label for="author_photo"><?php _e( 'Author Photo', 'Post kind' ); ?></label><br/>
      <input type="text" name="author[photo]" id="author_photo" size="70" value="<?php echo ifset($author['photo']); ?>" />
      <br />
    </div><!-- #kindmetatab-author -->


</div>

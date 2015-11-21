<?php
 
/**
 * Provides the 'Other' view for the corresponding tab in the Post Meta Box.
 *
 *
 * @package    Indieweb_Post_Kinds
 */
?>
 
<div class="inside hidden">
    <p>This is where the Other content will reside.</p>
    <div id="kindmetatab-other">
      <label for="duration"><?php _e( 'Duration/Length', 'Post kind' ); ?></label><br/>
      <input type="text" name="duration" id="duration" size="30" value="<?php echo ifset($kindmeta['duration']); ?>" />
      <br />

    </div><!-- #kindmetatab-other -->

</div>

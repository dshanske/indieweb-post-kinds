<?php

/**
 * Provides the 'Details' view for the corresponding tab in the Post Meta Box.
 *
 * @package    Indieweb_Post_Kinds
 */
?>
	
<div class="inside hidden">
	<h4><?php _e( 'Information on what you are responding to', 'indieweb-post-kinds' ); ?></h4>
		<div id="kindmetatab-details">
 			<?php echo self::metabox_text( 'cite_publication', __( 'Site Name/Publication/Album', 'indieweb-post-kinds' ), ifset( $cite['publication'] ), $type = 'text' ); ?>
                	<br />

			<?php echo self::metabox_text( 'cite_featured', __( 'Featured Image/Site Icon', 'indieweb-post-kinds' ), ifset( $cite['featured'] ), $type = 'url' ); ?>
			<br />

			 <?php echo self::kind_the_time( 'cite_published', __( 'Published/Released', 'indieweb-post-kinds' ), $meta->divide_time( ifset( $cite['published'] ) ) ); ?>
		         <br />

			<?php echo self::kind_the_time( 'cite_updated', __( 'Updated', 'indieweb-post-kinds' ), $meta->divide_time( ifset( $cite['updated'] ) ) ); ?>
			<br />
		</div> <!-- #kindmetatab-details -->
</div>

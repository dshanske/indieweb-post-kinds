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
		<?php echo self::metabox_text( 'cite_author_name', __( 'Author/Artist Name', 'indieweb-post-kinds' ), ifset( $author['name'] ), $type = 'text' ); ?>
		<br />
		<?php echo self::metabox_text( 'cite_author_url', __( 'Author/Artist URL', 'indieweb-post-kinds' ), ifset( $author['url'] ), $type = 'text' ); ?>
		<br />
		<?php echo self::metabox_text( 'cite_author_photo', __( 'Author/Artist Photo', 'indieweb-post-kinds' ), ifset( $author['photo'] ), $type = 'text' ); ?>
		<br />
	</div><!-- #kindmetatab-author -->


</div>

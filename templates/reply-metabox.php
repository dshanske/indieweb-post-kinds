<?php
wp_nonce_field( 'replykind_metabox', 'replykind_metabox_nonce' );
$mf2_post = new MF2_Post( get_post() );
$cite     = $mf2_post->fetch();

if ( ! isset( $cite['url'] ) ) {
	if ( array_key_exists( 'kindurl', $_GET ) && Link_Preview::is_valid_url( $_GET['kindurl'] ) ) {
		$cite   = Link_Preview::parse( $_GET['kindurl'] );
		$author = ifset( $cite['author'], array() );
	}
}
$author   = ifset( $cite['author'], array() );
$time = array();
if ( isset( $cite['category'] ) ) {
        $tags = $cite['category'];
        if ( is_array( $tags ) ) {
                $tags = implode( ';', $tags );
        }
} else {
        $tags = '';
}


// FIXME: Discards extra URLs as currently unsupported
if ( isset( $cite['url'] ) && is_array( $cite['url'] ) ) {
			$cite['url'] = array_shift( $cite['url'] );
}

?>
<p class="field-row">
	<label for="cite_url" class="three-quarters">
		<?php _e( 'URL', 'indieweb-post-kinds' ); ?>
        	<input type="text" name="cite_url" id="cite_url" class="widefat" value="<?php echo ifset( $cite['url'] ); ?>" />
	</label>
</p>
<p class="field-row">
	<label for="cite_name" class="three-quarters">
		<?php _e( 'Name', 'indieweb-post-kinds' ); ?>
        	<input type="text" name="cite_name" id="cite_name" class="widefat" value="<?php echo ifset( $cite['name'] ); ?>" />
	</label>
</p>
<button class="clear-kindmeta-button button hide-if-no-js"><?php _e( 'Clear', 'indieweb-post-kinds' ); ?></button>
<p class="field-row hide-if-js" id="rsvp-option">
	<?php echo Kind_Metabox::rsvp_select( $mf2_post->get( 'rsvp', true ) ); ?>
</p>

<p>
	<a href="#kind-details" class="show-kind-details hide-if-no-js"><?php _e( 'Show Additional Details', 'indieweb-post-kinds' ); ?></a>
</p>
<div id="kind-details" class="hide-if-js">
	<label for="cite_summary">
		<?php _e( 'Summary/Quote', 'indieweb-post-kinds' ); ?>
	<textarea name="cite_summary" id="cite_summary" data-role="none" class="widefat"><?php echo ifset( $cite['summary'] );?></textarea>
	</label>
	<label for="cite_tags">
		<?php _e( 'Tags (semicolon separated)', 'indieweb-post-kinds' ); ?>
	<textarea name="cite_tags" id="cite_tags" data-role="none" class="widefat"><?php echo ifset( $tags );?></textarea>
	</label>
        <h4> <?php _e( 'Information on the Author or Artist of the Piece', 'indieweb-post-kinds' ); ?></h4>
        <?php _e( '(Multiple Entries separated by semicolon)', 'indieweb-post-kinds' ); ?><BR />
	<p class="field-row">
	<label for="cite_author_name" class="three-quarters">
		<?php _e( 'Author', 'indieweb-post-kinds' ); ?>
        	<input type="text" name="cite_author_name" id="cite_author_name" class="widefat" value="<?php echo ifset( $author['name'] ); ?>" />
	</label>
	</p>
	<p class="field-row">
	<label for="cite_author_url" class="three-quarters">
		<?php _e( 'Author URL', 'indieweb-post-kinds' ); ?>
        	<input type="text" name="cite_author_url" id="cite_author_url" class="widefat" value="<?php echo ifset( $author['url'] ); ?>" />
	</label>
	</p>
	<p class="field-row">
	<label for="cite_author_photo" class="three-quarters">
		<?php _e( 'Author Photo URL', 'indieweb-post-kinds' ); ?>
        	<input type="text" name="cite_author_photo" id="cite_author_photo" class="widefat" value="<?php echo ifset( $author['photo'] ); ?>" />
	</label>
	</p>
        <h4><?php _e( 'Information on what you are responding to', 'indieweb-post-kinds' ); ?></h4>
	<p class="field-row">
	<label for="cite_publication" class="three-quarters">
		<?php _e( 'Site Name/Publication/Album', 'indieweb-post-kinds' ); ?>
        	<input type="text" name="cite_publication" id="cite_publication" class="widefat" value="<?php echo ifset( $cite['publication'] ); ?>" />
	</label>
	</p>
	<p class="field-row">
	<label for="cite_featured" class="three-quarters">
		<?php _e( 'Featured Image', 'indieweb-post-kinds' ); ?>
        	<input type="text" name="cite_featured" id="cite_featured" class="widefat" value="<?php echo ifset( $cite['featured'] ); ?>" />
	</label>
	<p class="field-row">
 		<?php echo Kind_Metabox::kind_the_time( 'cite_published', __( 'Published/Released', 'indieweb-post-kinds' ), Kind_Metabox::divide_time( ifset( $cite['published'] ) ) ); ?>
		<?php echo Kind_Metabox::kind_the_time( 'cite_updated', __( 'Updated', 'indieweb-post-kinds' ), Kind_Metabox::divide_time( ifset( $cite['updated'] ) ) ); ?>
	</p>
        <h4> <?php _e( 'Start Time and End Time will be Used to Calculate Duration', 'indieweb-post-kinds' ); ?> </h4>
	<p class="field-row">
		<?php echo Kind_Metabox::kind_the_time( 'mf2_start', __( 'Start Time', 'indieweb-post-kinds' ), Kind_Metabox::divide_time( $mf2_post->get( 'dt-start', true ) ) ); ?>
		<?php echo Kind_Metabox::kind_the_time( 'mf2_end', __( 'End Time', 'indieweb-post-kinds' ), Kind_Metabox::divide_time( $mf2_post->get( 'dt-end', true ) ) ); ?>
	</p>


</div>


	<div class="loading">
		<img src="<?php echo esc_url( includes_url( '/images/wpspin-2x.gif' ) ); ?>" class="loading-spinner" />
	</div>

<?php

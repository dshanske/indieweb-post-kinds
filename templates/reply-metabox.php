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
<p class="field-row hide-if-js" id="rsvp-option">
	<?php echo Kind_Metabox::rsvp_select( $mf2_post->get( 'rsvp', true ) ); ?>
</p>


<a href="#kind-details" class="button show-kind-details hide-if-no-js"><?php _e( 'Details', 'indieweb-post-kinds' ); ?></a>
<a href="#kind-author" class="button show-kind-author-details hide-if-no-js"><?php _e( 'Author', 'indieweb-post-kinds' ); ?></a>
<button class="clear-kindmeta-button button hide-if-no-js"><?php _e( 'Clear', 'indieweb-post-kinds' ); ?></button>
<?php require_once( 'reply-details.php' ); ?>
<?php require_once( 'reply-author.php' ); ?>

	<div class="loading">
		<img src="<?php echo esc_url( includes_url( '/images/wpspin-2x.gif' ) ); ?>" class="loading-spinner" />
	</div>

<?php

<?php
wp_nonce_field( 'replykind_metabox', 'replykind_metabox_nonce' );
$kind_post = new Kind_Post( get_the_ID() );
$kind      = $kind_post->get_kind();
$type      = Kind_Taxonomy::get_kind_info( $kind, 'property' ); // phpcs:ignore
$cite      = $kind_post->get_cite();

if ( is_string( $cite ) ) {
	$cite = wp_http_validate_url( $cite ) ? array( 'url' => $cite ) : array( 'name' => $cite );
}

if ( empty( $cite['url'] ) && array_key_exists( 'kindurl', $_GET ) ) {
	$cite['url'] = esc_url_raw( $_GET['kindurl'] );
}

$attachment = 0;


if ( in_array( $kind, array( 'audio', 'video', 'photo' ) ) ) {
	$attachment = attachment_url_to_postid( $cite['url'] );
	if ( $attachment ) {
		$attachment_post = new Kind_Post( $attachment );
		$cite            = $attachment_post->get_cite();
	}
}

$cite = $kind_post->normalize_cite( $cite );

?>
<a href="#kind-details" class="show-kind-details button hide-if-no-js"><?php _e( 'Details', 'indieweb-post-kinds' ); ?></a>
<a href="#kind-author" class="show-kind-author-details button hide-if-no-js"><?php _e( 'Author', 'indieweb-post-kinds' ); ?></a>
<a id="add-kind-media" class="button hide-if-no-js hidden" href="javascript:;">Upload or Attach Media</a>
<button class="clear-kindmeta-button button hide-if-no-js"><?php _e( 'Clear', 'indieweb-post-kinds' ); ?></button>
<p class="field-row">
	<label for="cite_url" class="three-quarters">
		<?php _e( 'URL', 'indieweb-post-kinds' ); ?>
			<input type="text" name="cite_url" id="cite_url" class="widefat" value="<?php echo $cite['url']; ?>" />
	</label>
</p>
<p class="field-row">
	<label for="cite_name" class="three-quarters">
		<?php _e( 'Name', 'indieweb-post-kinds' ); ?>
			<input type="text" name="cite_name" id="cite_name" class="widefat" value="<?php echo $cite['name']; ?>" />
	</label>
</p>
<p class="field-row hide-if-js" id="rsvp-option">
	<?php echo Kind_Metabox::rsvp_select( $kind_post->get( 'rsvp' ) ); ?>
</p>
<p class="field-row hide-if-js" id="rating-option">
	<?php echo Kind_Metabox::rating_select( $kind_post->get( 'rating' ) ); ?>
</p>
<p id="kind-media hide-if-no-js">
<?php $show_media = ( isset( $cite['url'] ) && in_array( $kind, array( 'photo', 'audio', 'video' ) ) ); ?>
<div id="kind-media-container" <?php echo ( $show_media ) ? '' : 'class="hidden"'; ?> >
<?php
if ( $attachment ) {
	if ( wp_attachment_is( 'image', $attachment ) ) {
		echo wp_get_attachment_image( $attachment );
	} elseif ( wp_attachment_is( 'audio', $attachment ) ) {
		$view = new Kind_Media_View( $attachment, 'audio' );
		echo $view->get();
	} elseif ( wp_attachment_is( 'video', $attachment ) ) {
		$view = new Kind_Media_View( $attachment, 'video' );
		echo $view->get();
	}
}
?>
	</div>
	<input type="hidden" id="cite_media" name="cite_media" value="<?php echo $attachment; ?>" >
</p>


<?php require_once 'reply-details.php'; ?>
<?php require_once 'reply-author.php'; ?>

	<div class="loading">
		<img src="<?php echo esc_url( includes_url( '/images/wpspin-2x.gif' ) ); ?>" class="loading-spinner" />
	</div>

<?php

<?php
/**
 * Post Kinds metabox for the Classic Editor.
 *
 * Loaded by Kind_Metabox::reply_metabox(). It sets up $kind_post, $kind, $type
 * and $cite, then includes reply-details.php and reply-author.php, which use them.
 *
 * @package Post_Kinds
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

wp_nonce_field( 'replykind_metabox', 'replykind_metabox_nonce' );
$kind_post = new Kind_Post( get_the_ID() );
$kind      = $kind_post->get_kind();
$type      = Kind_Taxonomy::get_kind_info( $kind, 'property' ); // phpcs:ignore
$cite      = $kind_post->get_cite();

if ( is_string( $cite ) ) {
	$cite = wp_http_validate_url( $cite ) ? array( 'url' => $cite ) : array( 'name' => $cite );
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Prefills the URL on the new post screen; nothing is saved.
if ( empty( $cite['url'] ) && isset( $_GET['kindurl'] ) && is_string( $_GET['kindurl'] ) ) {
	$cite['url'] = sanitize_url( wp_unslash( $_GET['kindurl'] ) );
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$attachment = 0;


if ( in_array( $kind, array( 'audio', 'video', 'photo' ) ) ) {
	$attachment = empty( $cite['url'] ) ? 0 : Kind_Post::get_attachment_id( $cite['url'] );
	if ( $attachment ) {
		$attachment_post = new Kind_Post( $attachment );
		$cite            = $attachment_post->get_cite();
	}
}

$cite = $kind_post->normalize_cite( $cite );

?>
<a href="#kind-details" class="show-kind-details button hide-if-no-js"><?php esc_html_e( 'Details', 'indieweb-post-kinds' ); ?></a>
<a href="#kind-author" class="show-kind-author-details button hide-if-no-js"><?php esc_html_e( 'Author', 'indieweb-post-kinds' ); ?></a>
<a id="add-kind-media" class="button hide-if-no-js hidden" href="javascript:;"><?php esc_html_e( 'Upload or Attach Media', 'indieweb-post-kinds' ); ?></a>
<button class="clear-kindmeta-button button hide-if-no-js"><?php esc_html_e( 'Clear', 'indieweb-post-kinds' ); ?></button>
<p class="field-row">
	<label for="cite_url" class="three-quarters">
		<?php esc_html_e( 'URL', 'indieweb-post-kinds' ); ?>
			<input type="text" name="cite_url" id="cite_url" class="widefat" value="<?php echo esc_attr( $cite['url'] ); ?>" />
	</label>
</p>
<p class="field-row">
	<label for="cite_name" class="three-quarters">
		<?php esc_html_e( 'Name', 'indieweb-post-kinds' ); ?>
			<input type="text" name="cite_name" id="cite_name" class="widefat" value="<?php echo esc_attr( $cite['name'] ); ?>" />
	</label>
</p>
<p class="field-row hide-if-js" id="rsvp-option">
	<?php echo Kind_Metabox::rsvp_select( $kind_post->get( 'rsvp' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Kind_Metabox::rsvp_select() escapes its output. ?>
</p>
<p class="field-row hide-if-js" id="rating-option">
	<?php echo Kind_Metabox::rating_select( $kind_post->get( 'rating' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Kind_Metabox::rating_select() escapes its output. ?>
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
		echo $view->get(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Kind_Media_View::get() returns core gallery, audio or video shortcode markup.
	} elseif ( wp_attachment_is( 'video', $attachment ) ) {
		$view = new Kind_Media_View( $attachment, 'video' );
		echo $view->get(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Kind_Media_View::get() returns core gallery, audio or video shortcode markup.
	}
}
?>
	</div>
	<input type="hidden" id="cite_media" name="cite_media" value="<?php echo absint( $attachment ); ?>" >
</p>


<?php require_once 'reply-details.php'; ?>
<?php require_once 'reply-author.php'; ?>

	<div class="loading">
		<img src="<?php echo esc_url( includes_url( '/images/wpspin-2x.gif' ) ); ?>" class="loading-spinner" />
	</div>

<?php

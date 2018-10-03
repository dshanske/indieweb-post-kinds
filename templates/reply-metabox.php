<?php
wp_nonce_field( 'replykind_metabox', 'replykind_metabox_nonce' );
$mf2_post = new MF2_Post( get_post() );
$type = Kind_Taxonomy::get_kind_info( $mf2_post->get( 'kind' ), 'property' );
$cite     = $mf2_post->fetch( $type );
$duration = divide_iso8601_duration( $mf2_post->get( 'duration' ) );

if ( ! isset( $cite['url'] ) ) {
	if ( array_key_exists( 'kindurl', $_GET ) && Link_Preview::is_valid_url( $_GET['kindurl'] ) ) {
		$cite   = Link_Preview::parse( $_GET['kindurl'] );
		$author = ifset( $cite['author'], array() );
	}
}
$author = ifset( $cite['author'], array() );
if ( 1 === count( $author ) && wp_is_numeric_array( $author ) ) {
	$author = array_pop( $author );
}
if ( is_string( $author ) ) {
	$author = array( 'name' => $author );
}

if ( isset( $author['name'] ) && is_array( $author['name'] ) ) {
	$author['name'] = implode( ';', $author['name'] );
}
if ( isset( $author['url'] ) && is_array( $author['url'] ) ) {
	$author['url'] = implode( ';', $author['url'] );
}

$time   = array();
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
<a href="#kind-details" class="show-kind-details button hide-if-no-js"><?php _e( 'Details', 'indieweb-post-kinds' ); ?></a>
<a href="#kind-author" class="show-kind-author-details button hide-if-no-js"><?php _e( 'Author', 'indieweb-post-kinds' ); ?></a>
<a id="add-kind-media" class="button hide-if-no-js hidden" href="javascript:;">Upload or Attach Media</a>
<button class="clear-kindmeta-button button hide-if-no-js"><?php _e( 'Clear', 'indieweb-post-kinds' ); ?></button>
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
<p id="kind-media hide-if-no-js">
<div id="kind-media-container" <?php echo isset( $cite['url'] ) ? '' : 'class="hidden"';?> >
    <img src="<?php echo ifset( $cite['url'], "" ); ?>" alt="" title="" height="100" />
    </div>
    <input type="hidden" value="" id="cite_media" name="cite_media">
</p>


<?php require_once 'reply-details.php'; ?>
<?php require_once 'reply-author.php'; ?>

	<div class="loading">
		<img src="<?php echo esc_url( includes_url( '/images/wpspin-2x.gif' ) ); ?>" class="loading-spinner" />
	</div>

<?php

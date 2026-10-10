<?php
/**
 * Read view.
 *
 * Rendered by Kind_View::get_view_part(). To change it, copy this file into a
 * kind_views folder in your theme; see develop.txt.
 *
 * @package Post_Kinds
 * @since   2.6.1
 *
 * @var Kind_Post    $kind_post The post being displayed.
 * @var string       $kind      The kind's slug, such as 'like'.
 * @var string       $type      The kind's microformats property, such as 'like-of'.
 * @var array        $cite      The citation, normalized by Kind_Post::normalize_cite(),
 *                              so every key is present. 'name' falls back to the URL.
 * @var string       $url       The cited URL.
 * @var string|false $author    The cited authors as h-card markup, listed as in "Ann, Bob and Cat", or false if there are none.
 * @var string       $embed     Embed markup for the cited URL, or an empty string.
 * @var int[]|false  $photos    Attachment IDs of the post's photos, from Kind_Post::get_photo().
 * @var array        $args      Arguments passed to Kind_View::get_view_part(); 'post_id' is the post's ID.
 */

defined( 'ABSPATH' ) || exit;

if ( ! $cite ) {
	return;
}
$read   = $kind_post->get( 'read-status', true );
$rating  = $kind_post->get( 'rating', true );

?>
<section class="response <?php echo empty( $url ) ? 'p-read-of' : 'u-read-of'; ?> h-cite">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'read' );
if ( ! $embed ) {
	if ( $read ) {
		printf( ' - <span class="p-read-status">%1$s</span>', esc_html( Kind_View::read_text( $read ) ) );
	}
	echo Kind_View::get_cite_title( $cite );
	if ( $author ) {
		echo ' ' . esc_html__( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	$site_name = Kind_View::get_site_name( $cite );
	if ( $site_name ) {
		echo ' <em>(' . $site_name . ')</em>';
	}
}
?>
</header>
<?php
if ( $cite ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
	} else {
		echo Kind_View::get_summary( $cite );
	}
}

if ( $rating ) {
	echo '<data class="p-rating" value="' . esc_attr( $rating ) . '">' . esc_html( Kind_View::rating_text( $rating ) ) . '</data>';
}


if ( $photos && ! has_post_thumbnail( get_the_ID() ) ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}
// Close Response
?>
</section>

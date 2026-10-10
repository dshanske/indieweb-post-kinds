<?php
/**
 * Video view.
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

$videos = $kind_post->get_video();
$duration = null;
if ( is_array( $videos ) ) {
	if ( 1 === count( $videos ) && 0 !== $videos[0] ) {
		$video_attachment = new Kind_Post( $videos[0] );
		$cite = $video_attachment->get_cite();
		$cite = $video_attachment->normalize_cite( $cite );
		$author = Kind_View::get_hcard( $cite['author'] );
		
		if ( array_key_exists( 'duration', $cite ) ) {
			$duration = Kind_View::display_duration( $cite['duration'] );
		} else {
			$duration = null;
		}
	}
}
$first_photo = null;
if ( is_array( $photos ) && ! empty( $photos ) ) {
	$first_photo = $photos[0];
}
if ( is_array( $cite ) && ! $videos ) {
	if ( ! $embed ) {
		$view = new Kind_Media_View( $url, 'video' );
		$embed = $view->get();
	}
}


?>
<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'video' );
if ( isset( $cite['name'] ) ) {
	printf( '<span class="p-name">%1$s</span>', esc_html( $cite['name'] ) );
}

if ( $author ) {
	echo ' ' . esc_html__( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
}
if ( $duration ) {
	printf( '(%1$s)', $duration );
}

?>
</header>
</section>
<?php
if ( $embed ) {
	printf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
} elseif ( $videos ) {

	$poster = wp_get_attachment_image_url( $first_photo, 'full' );
	$view = new Kind_Media_View( $videos, 'video' );
	echo $view->get();
}
?>
<?php

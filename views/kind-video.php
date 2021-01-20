<?php
/*
 * Video Template
 *
 */

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
if ( is_countable( $photos ) ) {
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
	echo sprintf( '<span>%1s</a>', $cite['name'] );
}

if ( $author ) {
	echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
}
if ( $duration ) {
	printf( '(%1$s)', $duration );
}

?>
</header>
</section>
<?php
if ( $embed ) {
	printf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
} elseif ( $videos ) {

	$poster = wp_get_attachment_image_url( $first_photo, 'full' );
	$view = new Kind_Media_View( $videos, 'video' );
	echo $view->get();
}
?>
<?php

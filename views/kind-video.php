<?php
/*
 * Video Template
 *
 */

$videos = $kind_post->get_video();
if ( is_array( $videos ) ) {
	if ( 1 === count( $videos ) ) {
		$video_attachment = new Kind_Post( $videos[0] );
		$cite = $video_attachment->get_cite();
	}
}
$photos      = $kind_post->get_photo();
$first_photo = null;
if ( is_countable( $photos ) ) {
	$first_photo = $photos[0];
}
$embed = null;
if ( is_array( $cite ) && ! $videos ) {
	$url   = ifset( $cite['url'] );
	$embed = self::get_embed( $url );
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
	echo sprintf( '<span class="p-name">%1s</a>', $cite['name'] );
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

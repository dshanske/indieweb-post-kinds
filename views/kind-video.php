<?php
/*
 * Video Template
 *
 */

$videos      = $mf2_post->get_videos();
$photos      = $mf2_post->get_images();
if ( is_array( $photos ) ) {
	$first_photo = array_pop( array_reverse( $photos ) );
}	
$embed       = null;
if ( is_array( $cite ) && ! $videos ) {
	$url   = ifset( $cite['url'] );
	$embed = self::get_embed( $url );
	if ( ! $embed ) {
		$embed = wp_video_shortcode(
			array(
				'class' => 'wp_video-shortcode u-video',
				'src'   => $url,
			)
		);
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
	echo wp_video_shortcode(
		array(
			'poster' => $poster,
			'class'  => 'wp-video-shortcode u-video',
		)
	);
}
?>
<?php

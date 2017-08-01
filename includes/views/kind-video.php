<?php
/*
 * Photo Template
 *
 */

$meta = new Kind_Meta( get_the_ID() );
$videos = get_attached_media( 'video', get_the_ID() );
$cite = $meta->get_cite();
$url = $meta->get_url();
$embed = self::get_embed( $meta->get_url() );

?>
<section class="response">
<header>
<?php echo Kind_Taxonomy::get_icon( 'video' );
if ( isset( $cite['name'] ) ) {
	echo sprintf( '<span class="p-name">%1s</a>', $cite['name'] );
}

?>
</header>
</section>
<?php
if ( $videos && ! has_post_thumbnail( get_the_ID() ) ) {
	echo wp_video_shortcode();
}
else {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	}
}
?>
<?php

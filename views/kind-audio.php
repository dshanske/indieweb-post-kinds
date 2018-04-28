<?php
/*
 * Audio Template
 *
 */

$mf2_post = new MF2_Post( get_the_ID() );
$audios   = get_attached_media( 'audio', get_the_ID() );
$cite     = $mf2_post->fetch();
if ( ! $cite ) {
	$cite = array();
}
$url   = ifset( $cite['url'] );
$embed = self::get_embed( $url );
if ( ! $audios && ! $embed ) {
	$embed = wp_audio_shortcode( 
		array(
			'class' => 'wp_audio-shortcode u-audio',
			'src' => $url,
		)
	);
}

?>
<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'audio' );
if ( isset( $cite['name'] ) ) {
	echo sprintf( '<span class="p-name">%1s</a>', $cite['name'] );
}

?>
</header>
</section>
<?php
if ( $audios ) {
	echo wp_audio_shortcode(
		array(
			'class' => 'wp-audio-shortcode u-audio',
		)
	);
} else {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	}
}
?>
<?php

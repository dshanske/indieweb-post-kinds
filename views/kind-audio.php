<?php
/*
 * Audio Template
 *
 */

$audios = get_attached_media( 'audio', get_the_ID() );
if ( ! $cite ) {
	$cite = array();
}
$url   = ifset( $cite['url'] );
$embed = self::get_embed( $url );
if ( ! $audios && ! $embed ) {
	$embed = wp_audio_shortcode(
		array(
			'class' => 'wp_audio-shortcode u-audio',
			'src'   => $url,
		)
	);
}

$duration = $mf2_post->get( 'duration', true );
if ( ! $duration ) {
	$duration = calculate_duration( $mf2_post->get( 'dt-start' ), $mf2_post->get( 'dt-end' ) );
}

?>
<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'audio' );
if ( isset( $cite['name'] ) ) {
	echo sprintf( '<span class="p-name">%1s</a>', $cite['name'] );
}

if ( $duration ) {
	echo '(<data class="p-duration" value="' . $duration . '">' . Kind_View::display_duration( $duration ) . '</data>)';
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

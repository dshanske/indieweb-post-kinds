<?php
/*
 * Audio Template
 *
 */
$audios = $mf2_post->get_audios();
if ( $cite && ! $audios ) {
	$url   = ifset( $cite['url'] );
	$embed = self::get_embed( $url );
	if ( ! $embed ) {
		$embed = kind_audio_gallery( $url );
	}
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
	printf( '<span class="p-name">%1s</a>', $cite['name'] );
}

if ( $duration ) {
	printf( '(<data class="p-duration" value="%1$s">%2$s</data>', $duration, Kind_View::display_duration( $duration ) );
}

?>
</header>
</section>
<?php
if ( $embed ) {
	printf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
} elseif ( $audios ) {
	echo kind_audio_gallery( $audios );
}

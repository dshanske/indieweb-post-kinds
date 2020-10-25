<?php
/*
 * Audio Template
 *
 */
$audios = $kind_post->get_audio();
$duration = null;
$publication = null;

if ( is_array( $audios ) ) {
	if ( 1 === count( $audios ) && 0 !== $audios[0] ) {
		$audio_attachment = new Kind_Post( $audios[0] );
		$cite = mf2_to_jf2( $audio_attachment->get_cite() );
		if ( ! $cite ) {
			$cite = array();
		}
		if ( array_key_exists( 'author', $cite ) ) {
			$author = Kind_View::get_hcard( $cite['author'] );
		} else {
			$author    = null;
		}
		if ( array_key_exists( 'duration', $cite ) ) {
			$duration = Kind_View::display_duration( $cite['duration'] );
		} else {
			$duration = null;
		}
	}
}
?>
<section class="response">
<header>
<?php echo Kind_Taxonomy::get_before_kind( 'audio' );
if ( isset( $cite['name'] ) ) {
	echo sprintf( '<span>%1s</a>', $cite['name'] );
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
} elseif ( $audios ) {
	$view = new Kind_Media_View( $audios, 'audio' );
	echo $view->get();
}

<?php
/*
 * Audio Template
 *
 */
$audios = $kind_post->get_audio();
if ( is_array( $audios ) ) {
	if ( 1 === count( $audios ) ) {
		$audio_attachment = new Kind_Post( $audios[0] );
		$cite = mf2_to_jf2( $audio_attachment->get_cite() );
	}
}
$duration = null;
$publication = null;

?>
<section class="response">
<header>
<?php echo Kind_Taxonomy::get_before_kind( 'audio' );
if ( isset( $cite['name'] ) ) {
	echo sprintf( '<span class="p-name">%1s</a>', $cite['name'] );
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

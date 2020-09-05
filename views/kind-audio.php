<?php
/*
 * Audio Template
 *
 */
$audios = $kind_post->get_audios();
$a = $kind_post->get( 'audio' );
$author = null;
$duration = null;
$publication = null;
if ( $audios && is_array( $audios ) ) {
	foreach( $audios as $audio ) {
		if ( wp_http_validate_url( $audio ) ) {
			$audio = attachment_url_to_postid( $audio );
		}
		if ( is_numeric( $audio ) ) {
			$audio_attachment = new Kind_Post( $audio );
		}
	}
}

?>
<section class="response">
<header>
<?php echo Kind_Taxonomy::get_before_kind( 'audio' ); ?>
</header>
</section>
<?php
if ( $embed ) {
	printf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
} elseif ( $audios ) {
	$view = new Kind_Media_View( $audios, 'audio' );
	echo $view->get();
}

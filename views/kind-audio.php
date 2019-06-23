<?php
/*
 * Audio Template
 *
 */
$audios = $mf2_post->get_audios();
$a = $mf2_post->get( 'audio' );
$author = null;
$duration = null;
$publication = null;
if ( $audios && is_array( $audios ) ) {
	foreach( $audios as $audio ) {
		if ( wp_http_validate_url( $audio ) ) {
			$audio = attachment_url_to_postid( $audio );
		}
		if ( is_numeric( $audio ) ) {
			$audio_attachment = new MF2_Post( $audio );
			$cite = $audio_attachment->get();
			$author = Kind_View::get_hcard( $cite['author'] );
			$duration = $audio_attachment->get( 'duration', true );
			$publication = $audio_attachment->get( 'publication', true );
		}
	}
}



if ( $cite && ! $audios ) {
	$url   = ifset( $cite['url'] );
	$embed = self::get_embed( $url );
	if ( ! $embed ) {
		$view = new Kind_Media_View( $url, 'audio' );
		$embed = $view->get();
	}
}

?>
<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'audio' );
if ( isset( $cite['name'] ) ) {
	printf( '<span class="p-name">%1s</a>', $cite['name'] );
}

if ( $author ) {
	echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
}
if ( $publication ) {
	echo sprintf( ' <em>(<span class="p-publication">%1s</span>)</em>', $cite['publication'] );
}

if ( $duration ) {
	printf( '(<data class="p-duration" value="%1$s">%2$s</data>)', $duration, Kind_View::display_duration( $duration ) );
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

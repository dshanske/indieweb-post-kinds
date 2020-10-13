<?php
/*
 * Photo Template
 *
 */

$photos = $kind_post->get_photo();
if ( $photos ) {
	$embed = null;
}

if ( is_array( $photos ) ) {
	if ( 1 === count( $photos ) ) {
		$photos_attachment = new Kind_Post( $photos[0] );
		$cite = $photos_attachment->get_cite() );
		$cite = $photos_attachment->normalize_cite( $cite );
		$author = Kind_View::get_hcard( $cite['author'] );
	}
}

?>
<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'photo' );
if ( ! empty( $cite['name'] ) ) {
	echo sprintf( '<span class="p-name">%1s</a>', $cite['name'] );
}
if ( $author ) {
	echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
}
?>
</header>
</section>
<?php
if ( $embed ) {
	printf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
} elseif ( $photos ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}

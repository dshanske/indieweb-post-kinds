<?php
defined( 'ABSPATH' ) || exit;

/*
 * Photo Template
 *
 */
if ( $photos ) {
	$embed = null;
}

if ( is_array( $photos ) ) {
	if ( 1 === count( $photos ) ) {
		$photos_attachment = new Kind_Post( $photos[0] );
		$cite = $photos_attachment->get_cite();
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
	printf( '<span class="p-name">%1$s</span>', esc_html( $cite['name'] ) );
}
?>
</header>
<?php
if ( $embed ) {
	printf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
} elseif ( $photos ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
} ?>
</section>

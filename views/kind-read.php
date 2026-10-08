<?php
/*
 * Read Template
 *
 */

defined( 'ABSPATH' ) || exit;

if ( ! $cite ) {
	return;
}
$read   = $kind_post->get( 'read-status', true );
$rating  = $kind_post->get( 'rating', true );

?>
<section class="response <?php echo empty( $url ) ? 'p-read-of' : 'u-read-of'; ?> h-cite">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'read' );
if ( ! $embed ) {
	if ( $read ) {
		printf( ' - <span class="p-read-status">%1$s</span>', esc_html( Kind_View::read_text( $read ) ) );
	}
	echo Kind_View::get_cite_title( $cite );
	if ( $author ) {
		echo ' ' . esc_html__( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	$site_name = Kind_View::get_site_name( $cite );
	if ( $site_name ) {
		echo ' <em>(' . $site_name . ')</em>';
	}
}
?>
</header>
<?php
if ( $cite ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
	} else {
		echo Kind_View::get_summary( $cite );
	}
}

if ( $rating ) {
	echo '<data class="p-rating" value="' . esc_attr( $rating ) . '">' . esc_html( Kind_View::rating_text( $rating ) ) . '</data>';
}


if ( $photos && ! has_post_thumbnail( get_the_ID() ) ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}
// Close Response
?>
</section>

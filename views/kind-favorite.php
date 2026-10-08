<?php
/*
 * Favorite Template
 *
 */

defined( 'ABSPATH' ) || exit;

if ( ! $cite ) {
	return;
}

?>

<section class="response u-favorite-of h-cite">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'favorite' );
if ( ! $embed ) {
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

// Close Response
?>
</section>

<?php

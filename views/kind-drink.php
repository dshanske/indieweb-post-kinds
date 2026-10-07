<?php
/*
 * Drink Template
 *
 */

if ( ! $cite ) {
	return;
}
?>

<section class="response h-food p-drank">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'drink' );
if ( ! $embed ) {
	echo Kind_View::get_cite_title( $cite );
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

if ( $photos && ! has_post_thumbnail( get_the_ID() ) ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}
// Close Response
?>
</section>

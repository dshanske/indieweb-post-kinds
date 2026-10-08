<?php
/*
 * Checkin Template
 *
 */

if ( ! $cite ) {
	return;
}

?>

<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'checkin' );
if ( ! $embed ) {
	if ( empty( $cite['name'] ) ) {
		$cite['name'] = $url;
	}
	if ( ! empty( $url ) ) {
		printf( '<a href="%1$s" class="u-checkin h-card">%2$s</a>', esc_url( $url ), esc_html( $cite['name'] ) );
	} else {
		printf( '<span class="h-card p-checkin">%1$s</span>', esc_html( $cite['name'] ) );
	}
}
?>
</header>
<?php
if ( $cite ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
	}
}

if ( $photos && ! has_post_thumbnail( get_the_ID() ) ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}
// Close Response
?>
</section>


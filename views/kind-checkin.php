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
		echo sprintf( '<a href="%1s" class="u-checkin h-card">%2s</a>', $url, $cite['name'] );
	} else {
		echo sprintf( '<span class="h-card p-checkin">%1s</span>', $cite['name'] );
	}
}
?>
</header>
<?php
if ( $cite ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	}
}

if ( $photos && ! has_post_thumbnail( get_the_ID() ) ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}
// Close Response
?>
</section>


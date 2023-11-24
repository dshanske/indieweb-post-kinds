<?php
/*
  Watch Template
 *
 */

if ( ! $cite ) {
	return;
}
$site_name = Kind_View::get_site_name( $cite, $url );
$title     = Kind_View::get_cite_title( $cite, $url );
$duration  = $kind_post->get( 'duration', true );
$rating  = $kind_post->get( 'rating', true );
if ( ! $duration ) {
	$duration = calculate_duration( $kind_post->get( 'start' ), $kind_post->get( 'end' ) );
}


?>
<section class="response <?php echo empty( $url ) ? 'p-watch-of' : 'u-watch-of'; ?> h-cite">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'watch' );
if ( ! $embed ) {
	if ( $title ) {
		echo $title;
	}
	if ( $author ) {
		echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	if ( $site_name ) {
		echo __( ' from ', 'indieweb-post-kinds' ) . '<em>' . $site_name . '</em>';
	}
	if ( $duration ) {
		echo Kind_View::display_duration( $duration );
	}
}
?>
</header>
<?php
if ( $cite ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	} elseif ( array_key_exists( 'summary', $cite ) ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $cite['summary'] );
	}
}

// Close Response
?>
</section>

<?php if ( $rating ) {
	echo '<data class="p-rating" value="' . $rating . '">' . sprintf( Kind_View::rating_text( $rating ), $url, $title ) . '</data>';
} ?>


<?php

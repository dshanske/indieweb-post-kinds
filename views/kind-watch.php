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
		echo ' ' . esc_html__( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	if ( $site_name ) {
		echo esc_html__( ' from ', 'indieweb-post-kinds' ) . '<em>' . $site_name . '</em>';
	}
	if ( $duration ) {
		echo esc_html( Kind_View::display_duration( $duration ) );
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

<?php if ( $rating ) {
	echo '<data class="p-rating" value="' . esc_attr( $rating ) . '">' . esc_html( Kind_View::rating_text( $rating ) ) . '</data>';
} ?>


<?php

<?php
/*
  Jam Template
 *
 */

$mf2_post = new MF2_Post( get_the_ID() );
$cite     = $mf2_post->fetch();
if ( ! $cite ) {
	return;
}
$author    = Kind_View::get_hcard( ifset( $cite['author'] ) );
$url       = $cite['url'];
$site_name = Kind_View::get_site_name( $cite, $url );
$title     = Kind_View::get_cite_title( $cite, $url );
$embed     = self::get_embed( $url );
$duration  = $mf2_post->get( 'duration' );
if ( ! $duration ) {
	$duration = $mf2_post->calculate_duration( $mf2_post->get( 'dt-start' ), $mf2_post->get( 'dt-end' ) );
}

?>

<section class="response p-jam-of h-cite">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'jam' );
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
		echo '(<data class="p-duration" value="' . $duration . '">' . Kind_View::display_duration( $duration ) . '</data>)';
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

<?php

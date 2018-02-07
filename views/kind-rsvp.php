<?php
/*
 * RSVP Template
 *
 */

$mf2_post = new MF2_Post( get_the_ID() );
$cite     = $mf2_post->fetch();
if ( ! $cite ) {
	return;
}
$author = Kind_View::get_hcard( ifset( $cite['author'] ) );
$url    = $cite['url'];
$embed  = self::get_embed( $url );
$title  = isset( $cite['name'] ) ? $cite['name'] : $url;
$rsvp   = $mf2_post->get( 'rsvp', true );

?>

<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'rsvp' );

if ( ! $embed ) {
	if ( $rsvp ) {
		echo '<data class="p-rsvp" value="' . $rsvp . '">' . sprintf( Kind_View::rsvp_text( $rsvp ), $url, $title ) . '</data>';
	}
}
?>
</header>
<?php
if ( $embed ) {
	echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
} elseif ( array_key_exists( 'summary', $cite ) ) {
	echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $cite['summary'] );
}

// Close Response
?>
</section>

<?php

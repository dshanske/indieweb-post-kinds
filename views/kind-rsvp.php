<?php
/*
 * RSVP Template
 *
 */

if ( ! $cite ) {
	return;
}
$title  = isset( $cite['name'] ) ? $cite['name'] : $url;
$rsvp   = $kind_post->get( 'rsvp', true );

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

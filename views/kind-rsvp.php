<?php
/*
 * RSVP Template
 *
 */

defined( 'ABSPATH' ) || exit;

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
		echo '<data class="p-rsvp" value="' . esc_attr( $rsvp ) . '">' . Kind_View::rsvp_text( $rsvp, $url, $title ) . '</data>';
	}
}
?>
</header>
<?php
if ( $embed ) {
	echo sprintf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
} else {
	echo Kind_View::get_summary( $cite );
}

// Close Response
?>
</section>

<?php

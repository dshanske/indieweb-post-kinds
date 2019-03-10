<?php
/*
 * Event Template
 *
 */

?>

<section class="response h-event">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'event' );
echo get_the_title();
?>
</header>
<?php
$start = new DateTime( $mf2_post->get( 'start' ) );
$end = new DateTime( $mf2_post->get( 'end' ) );
$timestring = '<p>%1$s: <time class="%2$s" datetime="%3$s">%4$s %5$s</time></p>';

if ( $start ) {
	printf( $timestring, __( 'Start', 'indieweb-post-kinds' ), 'dt-start', $start->format( DATE_W3C ), $start->format( get_option( 'date_format' ) ), $start->format( get_option( 'time_format' ) ) );
} 
if ( $end ) {
	printf( $timestring, __( 'End', 'indieweb-post-kinds' ), 'dt-end', $end->format( DATE_W3C ), $end->format( get_option( 'date_format' ) ), $end->format( get_option( 'time_format' ) ) );
}	
// Close Response
?>
</section>

<?php

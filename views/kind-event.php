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
$start = $mf2_post->get( 'start' );
$end = $mf2_post->get( 'end' );
$timestring = '<p>%1$s: <time class="%2$s" datetime="%3$s">%4$s</time></p>';

if ( $start ) {
	printf( $timestring, __( 'Start', 'indieweb-post-kinds' ), 'dt-start', $start, display_formatted_datetime( $start ) );
} 
if ( $end ) {
	printf( $timestring, __( 'End', 'indieweb-post-kinds' ), 'dt-end', $end, display_formatted_datetime( $end ) );
}	
// Close Response
?>
</section>

<?php

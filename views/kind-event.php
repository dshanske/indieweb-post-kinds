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
$start = $kind_post->get( 'start' );
$end = $kind_post->get( 'end' );
$timestring = '<p>%1$s: <time class="%2$s" datetime="%3$s">%4$s</time></p>';

if ( $start ) {
	printf( $timestring, __( 'Start', 'indieweb-post-kinds' ), 'dt-start', $start->format( DATE_W3C ), display_formatted_datetime( $start ) );
} 
if ( $end ) {
	printf( $timestring, __( 'End', 'indieweb-post-kinds' ), 'dt-end', $end->format( DATE_W3C ), display_formatted_datetime( $end ) );
}	
if ( $photos && ! has_post_thumbnail( get_the_ID() ) ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}
// Close Response
?>
</section>

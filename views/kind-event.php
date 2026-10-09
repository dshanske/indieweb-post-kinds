<?php
/**
 * Event view.
 *
 * Rendered by Kind_View::get_view_part(). To change it, copy this file into a
 * kind_views folder in your theme; see develop.txt.
 *
 * @package Post_Kinds
 * @since   3.2.0
 *
 * @var Kind_Post    $kind_post The post being displayed.
 * @var string       $kind      The kind's slug, such as 'like'.
 * @var string       $type      The kind's microformats property, such as 'like-of'.
 * @var array        $cite      The citation, normalized by Kind_Post::normalize_cite(),
 *                              so every key is present. 'name' falls back to the URL.
 * @var string       $url       The cited URL.
 * @var string|false $author    The cited author as h-card markup, or false if there is none.
 * @var string       $embed     Embed markup for the cited URL, or an empty string.
 * @var int[]|false  $photos    Attachment IDs of the post's photos, from Kind_Post::get_photo().
 * @var array        $args      Arguments passed to Kind_View::get_view_part(); 'post_id' is the post's ID.
 */

defined( 'ABSPATH' ) || exit;

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
	printf( $timestring, esc_html__( 'Start', 'indieweb-post-kinds' ), 'dt-start', esc_attr( $start->format( DATE_W3C ) ), esc_html( Kind_Time::display_formatted_datetime( $start ) ) );
} 
if ( $end ) {
	printf( $timestring, esc_html__( 'End', 'indieweb-post-kinds' ), 'dt-end', esc_attr( $end->format( DATE_W3C ) ), esc_html( Kind_Time::display_formatted_datetime( $end ) ) );
}	
if ( $photos && ! has_post_thumbnail( get_the_ID() ) ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}
// Close Response
?>
</section>

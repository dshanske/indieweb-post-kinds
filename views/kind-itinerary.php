<?php
/**
 * Itinerary view.
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
 * @var string|false $author    The cited authors as h-card markup, listed as in "Ann, Bob and Cat", or false if there are none.
 * @var string       $embed     Embed markup for the cited URL, or an empty string.
 * @var int[]|false  $photos    Attachment IDs of the post's photos, from Kind_Post::get_photo().
 * @var array        $args      Arguments passed to Kind_View::get_view_part(); 'post_id' is the post's ID.
 */

defined( 'ABSPATH' ) || exit;

$itineraries = $kind_post->get( 'itinerary', false );
$itineraries = is_array( $itineraries ) ? $itineraries : array();

foreach ( $itineraries as $key => $value ) {
	$leg = \ParseThis\mf2_to_jf2( $value );
	// Each field is shown as text; fill in any that are missing.
	foreach ( array( 'operator', 'number', 'transit-type', 'origin', 'destination', 'departure', 'arrival' ) as $field ) {
		$leg[ $field ] = ( isset( $leg[ $field ] ) && is_scalar( $leg[ $field ] ) ) ? (string) $leg[ $field ] : '';
	}
	$itineraries[ $key ] = $leg;
}

?>


<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'itinerary' );
echo get_the_title();
?>
</header>
<?php
foreach( $itineraries as $itinerary ) {
?>
   <div class="h-leg p-itinerary">
		<h3><span class="p-operator"><?php echo esc_html( $itinerary['operator'] ); ?></span>
		<span class="p-number"><?php echo esc_html( $itinerary['number'] ); ?></span></h3>
		<data class="p-transit-type" value="<?php echo esc_attr( $itinerary['transit-type'] ); ?>"></data>
   <ul>
	<li> 
		<?php esc_html_e( 'Departs: ', 'indieweb-post-kinds' ); ?>
		<span class="p-origin"><?php echo esc_html( $itinerary['origin'] ); ?></span>
		<time class="dt-departure" datetime="<?php echo esc_attr( $itinerary['departure'] ); ?>"><?php echo esc_html( Kind_Time::display_formatted_datetime( $itinerary['departure'] ) ); ?></time>
	</li>
	<li>
		<?php esc_html_e( 'Arrives: ', 'indieweb-post-kinds' ); ?>
		<span class="p-destination"><?php echo esc_html( $itinerary['destination'] ); ?></span>
		<time class="dt-arrival" datetime="<?php echo esc_attr( $itinerary['arrival'] ); ?>"><?php echo esc_html( Kind_Time::display_formatted_datetime( $itinerary['arrival'] ) ); ?></time>
	</li>
   </ul>
   </div>
<?php
}


// Close Response
?>
</section>

<?php

<?php
/*
 * Event Template
 *
 */


$itineraries = $mf2_post->get( 'itinerary', false );

foreach( $itineraries as $key => $value ) {
	$itineraries [ $key ] = mf2_to_jf2( $value );
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
		<h3><span class="p-operator"><?php echo $itinerary['operator']; ?></span>
		<span class="p-number"><?php echo $itinerary['number']; ?></span></h3>
		<data class="p-transit-type" value="<?php echo $itinerary['transit-type']; ?>">
   <ul>
	<li> 
		<?php _e( 'Departs: ', 'indieweb-post-kinds' ); ?>
		<span class="p-origin"><?php echo $itinerary['origin']; ?></span>
		<time class="dt-departure" datetime="<?php echo $itinerary['departure']; ?>"><?php echo display_formatted_datetime( $itinerary['departure'] ); ?></time>
	</li>
	<li>
		<?php _e( 'Arrives: ', 'indieweb-post-kinds' ); ?>
		<span class="p-destination"><?php echo $itinerary['destination']; ?></span>
		<time class="dt-arrival" datetime="<?php echo $itinerary['arrival']; ?>"><?php echo display_formatted_datetime( $itinerary['arrival'] ); ?></time>
	</li>
   </ul>
   </div>
<?php
}


// Close Response
?>
</section>

<?php

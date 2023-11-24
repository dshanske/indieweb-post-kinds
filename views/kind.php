<?php
/*
  Default Template
 *	The Goal of this Template is to be a general all-purpose model that will be replaced by customization in other templates
 */

$site_name = Kind_View::get_site_name( $cite );
$title     = Kind_View::get_cite_title( $cite );
$duration  = $kind_post->get( 'duration', true );
if ( ! $duration ) {
		$duration = calculate_duration( $kind_post->get( 'start' ), $kind_post->get( 'end' ) );
}
$rsvp = $kind_post->get( 'rsvp', true );
$rating = $kind_post->get( 'rating', true );

if ( ! $kind ) {
	return;
}

// Add in the appropriate type
if ( ! empty( $type ) ) {
	$type = ( empty( $url ) ? 'p-' : 'u-' ) . $type;
}
?>

<section class="h-cite response <?php echo $type; ?> ">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( $kind );
if ( ! $embed ) {
	if ( $title ) {
		echo $title;
	}
	if ( ! empty( $author ) ) {
		echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	if ( $site_name ) {
		echo '<em> (' . $site_name . ')</em>';
	}
	if ( in_array( $kind, array( 'jam', 'listen', 'play', 'read', 'watch', 'audio', 'video' ) ) ) {
		if ( $duration ) {
			echo ' ' . Kind_View::display_duration( $duration );
		}
	}
}
?>
</header>
<?php
if ( $cite && is_array( $cite ) ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	} elseif ( array_key_exists( 'summary', $cite ) ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $cite['summary'] );
	}
}

// Close Response
?>
</section>

<?php if ( $rsvp && in_array( $kind, array( 'rsvp' ) ) ) {
	echo 'RSVP <span class="p-rsvp">' . $rsvp . '</span>';
}

if ( $rating ) {
	echo '<data class="p-rating" value="' . $rating . '">' . sprintf( Kind_View::rating_text( $rating ), $url, $title ) . '</data>';
} ?>

<?php

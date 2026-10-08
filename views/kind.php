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

<section class="h-cite response <?php echo esc_attr( $type ); ?>">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( $kind );
if ( ! $embed ) {
	if ( $title ) {
		echo $title;
	}
	if ( ! empty( $author ) ) {
		echo ' ' . esc_html__( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	if ( $site_name ) {
		echo '<em> (' . $site_name . ')</em>';
	}
	if ( in_array( $kind, array( 'jam', 'listen', 'play', 'read', 'watch', 'audio', 'video' ) ) ) {
		if ( $duration ) {
			echo ' ' . esc_html( Kind_View::display_duration( $duration ) );
		}
	}
}
?>
</header>
<?php
if ( $cite && is_array( $cite ) ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
	} else {
		echo Kind_View::get_summary( $cite );
	}
}

// Close Response
?>
</section>

<?php if ( $rsvp && in_array( $kind, array( 'rsvp' ) ) ) {
	echo esc_html__( 'RSVP', 'indieweb-post-kinds' ) . ' <span class="p-rsvp">' . esc_html( $rsvp ) . '</span>';
}

if ( $rating ) {
	echo '<data class="p-rating" value="' . esc_attr( $rating ) . '">' . esc_html( Kind_View::rating_text( $rating ) ) . '</data>';
} ?>

<?php

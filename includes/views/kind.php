<?php
/*
  Default Template
 *	The Goal of this Template is to be a general all-purpose model that will be replaced by customization in other templates
 */

$kind = get_post_kind_slug( get_the_ID() );
$meta = new Kind_Meta( get_the_ID() );
$cite = $meta->get_cite();
// Add in the appropriate type
switch ( $kind ) {
	case 'like':
		$type = 'p-like-of';
		break;
	case 'favorite':
		$type = 'p-favorite-of';
		break;
	case 'repost':
		$type = 'p-repost-of';
		break;
	case 'reply':
	case 'rsvp':
		$type = 'p-in-reply-to';
		break;
	case 'tag':
		$type = 'p-tag-of';
		break;
	case 'bookmark':
		$type = 'p-bookmark-of';
		break;
	case 'listen':
		$type = 'p-listen';
		break;
	case 'watch':
		$type = 'p-watch';
		break;
	case 'game':
		$type = 'p-play';
		break;
	case 'wish':
		$type = 'p-wish';
		break;
	case 'read':
		$type = 'p-read-of';
		break;
	case 'quote':
		$type = 'u-quotation-of';
		break;
}
?>

<cite class="h-cite response <?php echo $type; ?> ">
<?php echo Kind_Taxonomy::get_icon( $kind ); ?>
<?php Kind_View::get_cite_title( $meta->get_cite(), $meta->get_url() ); ?>
<?php 
$author = Kind_View::get_hcard( $meta->get_author() );
if ( $author ) {
	echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
}
$site_name = Kind_View::get_site_name( $meta->get_cite(), $meta->get_url() );
if ( $site_name ) {
	echo '<em>(<span class="p-publication">' . $site_name . '</span>)</em>';
}
if ( in_array( $kind, array( 'jam', 'listen', 'play', 'read', 'watch' ) ) ) {
	$duration = $meta->get_duration();
	if ( $duration ) {
		echo '(' . __( 'Duration: ', 'indieweb-post-kinds' ) . '<span class="p-duration">' . $duration . '</span>)';
	}
}
if ( $cite ) {
	$embed = self::get_embed( $meta->get_url() );
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	} else if ( array_key_exists( 'summary', $cite ) ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $cite['summary'] );
	}
}

// Close Response
?>
</cite>
<?php

<?php
/*
 * Read Template
 *
 */

if ( ! $cite ) {
	return;
}
$read   = $kind_post->get( 'read-status', true );
$rating  = $kind_post->get( 'rating', true );

?>
<section class="response <?php echo empty( $url ) ? 'p-read-of' : 'u-read-of'; ?> h-cite">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'read' );
if ( ! $embed ) {
	if ( $read ) {
		echo sprintf( ' - <span class="p-read-status">%1s</span>', Kind_View::read_text( $read ) );
	}
	if ( ! empty( $url ) ) {
		echo sprintf( '<a href="%1s" class="p-name u-url">%2s</a>', $url, $cite['name'] );
	} else {
		echo sprintf( '<span class="p-name">%1s</span>', $cite['name'] );
	}
	if ( $author ) {
		echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	if ( empty( $cite['publication'] ) ) {
		echo sprintf( ' <em>(<span class="p-publication">%1s</span>)</em>', $cite['publication'] );
	}
}
?>
</header>
<?php
if ( $cite ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	} elseif ( array_key_exists( 'summary', $cite ) && ! empty( $cite['summary'] ) ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $cite['summary'] );
	}
}

if ( $rating ) {
	echo '<data class="p-rating" value="' . $rating . '">' . sprintf( Kind_View::rating_text( $rating ), $url, $title ) . '</data>';
}


if ( $photos && ! has_post_thumbnail( get_the_ID() ) ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}
// Close Response
?>
</section>

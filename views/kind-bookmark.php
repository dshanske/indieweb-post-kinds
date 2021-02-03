<?php
/*
 * Bookmark Template
 *
 */

if ( ! $cite ) {
	return;
}

?>


<section class="response <?php echo empty( $url ) ? 'p-bookmark-of' : 'u-bookmark-of'; ?> h-cite">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'bookmark' );
if ( ! $embed ) {
	if ( ! empty( $cite['url'] ) ) {
		echo sprintf( '<a href="%1s" class="p-name u-url">%2s</a>', $cite['url'], $cite['name'] );
	} else {
		echo sprintf( '<span class="p-name">%1s</span>', esc_html( $cite['name'] ) );
	}
	if ( $author ) {
		echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	if ( ! empty( $cite['publication'] ) ) {
		echo sprintf( ' <em>(<span class="p-publication">%1s</span>)</em>', esc_html( $cite['publication'] ) );
	}
}
?>
</header>
<?php
if ( ! empty( $embed ) ) {
	echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
} elseif ( ! empty( $cite['summary'] ) ) {
	echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $cite['summary'] );
}

// Close Response
?>
</section>

<?php

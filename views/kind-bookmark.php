<?php
/*
 * Bookmark Template
 *
 */

$mf2_post = new MF2_Post( get_the_ID() );
$cite     = $mf2_post->fetch();
if ( ! $cite ) {
	return;
}
$author = Kind_View::get_hcard( ifset( $cite['author'] ) );
$url    = ifset( $cite['url'] );
$embed  = self::get_embed( $url );

?>

<section class="response p-bookmark-of h-cite">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'bookmark' );
if ( ! $embed ) {
	if ( ! array_key_exists( 'name', $cite ) ) {
		$cite['name'] = self::get_post_type_string( $url );
	}
	if ( isset( $url ) ) {
		echo sprintf( '<a href="%1s" class="p-name u-url">%2s</a>', $url, $cite['name'] );
	} else {
		echo sprintf( '<span class="p-name">%1s</span>', $cite['name'] );
	}
	if ( $author ) {
		echo ' ' . __( 'by', 'indieweb-post-kinds' ) . ' ' . $author;
	}
	if ( array_key_exists( 'publication', $cite ) ) {
		echo sprintf( ' <em>(<span class="p-publication">%1s</span>)</em>', $cite['publication'] );
	}
}
?>
</header>
<?php
if ( $cite ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	} elseif ( array_key_exists( 'summary', $cite ) ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $cite['summary'] );
	}
}

// Close Response
?>
</section>

<?php

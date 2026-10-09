<?php
/**
 * Photo view.
 *
 * Rendered by Kind_View::get_view_part(). To change it, copy this file into a
 * kind_views folder in your theme; see develop.txt.
 *
 * @package Post_Kinds
 * @since   2.6.1
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

if ( $photos ) {
	$embed = null;
}

if ( is_array( $photos ) ) {
	if ( 1 === count( $photos ) ) {
		$photos_attachment = new Kind_Post( $photos[0] );
		$cite = $photos_attachment->get_cite();
		$cite = $photos_attachment->normalize_cite( $cite );
		$author = Kind_View::get_hcard( $cite['author'] );
	}
}

?>
<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'photo' );
if ( ! empty( $cite['name'] ) ) {
	printf( '<span class="p-name">%1$s</span>', esc_html( $cite['name'] ) );
}
?>
</header>
<?php
if ( $embed ) {
	printf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
} elseif ( $photos ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
} ?>
</section>

<?php
/**
 * RSVP view.
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
 * @var string|false $author    The cited authors as h-card markup, listed as in "Ann, Bob and Cat", or false if there are none.
 * @var string       $embed     Embed markup for the cited URL, or an empty string.
 * @var int[]|false  $photos    Attachment IDs of the post's photos, from Kind_Post::get_photo().
 * @var array        $args      Arguments passed to Kind_View::get_view_part(); 'post_id' is the post's ID.
 */

defined( 'ABSPATH' ) || exit;

if ( ! $cite ) {
	return;
}
$title  = isset( $cite['name'] ) ? $cite['name'] : $url;
$rsvp   = $kind_post->get( 'rsvp', true );

?>

<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'rsvp' );

if ( ! $embed ) {
	if ( $rsvp ) {
		echo '<data class="p-rsvp" value="' . esc_attr( $rsvp ) . '">' . Kind_View::rsvp_text( $rsvp, $url, $title ) . '</data>';
	}
}
?>
</header>
<?php
if ( $embed ) {
	echo sprintf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
} else {
	echo Kind_View::get_summary( $cite );
}

// Close Response
?>
</section>

<?php

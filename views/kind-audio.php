<?php
/**
 * Audio view.
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

$audios = $kind_post->get_audio();
$duration = null;
$publication = null;

if ( is_array( $audios ) ) {
	if ( 1 === count( $audios ) && 0 !== $audios[0] ) {
		$audio_attachment = new Kind_Post( $audios[0] );
		$cite = \ParseThis\mf2_to_jf2( $audio_attachment->get_cite() );
		if ( ! $cite ) {
			$cite = array();
		}
		if ( array_key_exists( 'author', $cite ) ) {
			$author = Kind_View::get_hcard( $cite['author'] );
		} else {
			$author    = null;
		}
		if ( array_key_exists( 'duration', $cite ) ) {
			$duration = Kind_View::display_duration( $cite['duration'] );
		} else {
			$duration = null;
		}
	}
}
?>
<section class="response">
<header>
<?php echo Kind_Taxonomy::get_before_kind( 'audio' );
if ( isset( $cite['name'] ) ) {
	printf( '<span class="p-name">%1$s</span>', esc_html( $cite['name'] ) );
}
if ( $duration ) {
	printf( '(%1$s)', $duration );
}
?>
</header>
</section>
<?php
if ( $embed ) {
	printf( '<blockquote class="e-summary">%1$s</blockquote>', $embed );
} elseif ( $audios ) {
	$view = new Kind_Media_View( $audios, 'audio' );
	echo $view->get();
}

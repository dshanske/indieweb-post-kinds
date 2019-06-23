<?php
/*
 * Photo Template
 *
 */

$photos = $mf2_post->get_images();
$embed  = null;
if ( ! $photos && is_array( $cite ) ) {
	$url   = ifset( $cite['url'] );
	$embed = self::get_embed( $url );
}
?>
<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'photo' );
?>
</header>
</section>
<?php
if ( $embed ) {
	printf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
} elseif ( $photos ) {
	$view = new Kind_Media_View( $photos, 'photo' );
	echo $view->get();
}

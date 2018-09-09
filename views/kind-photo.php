<?php
/*
 * Photo Template
 *
 */

$mf2_post = new MF2_Post( get_the_ID() );
$photos   = get_attached_media( 'image', get_the_ID() );
// Check for any sort of URL that is probably media
$src_urls = kind_src_url_in_content( get_the_content() );
$cite     = $mf2_post->fetch( 'photo' );
if ( ! $cite ) {
	$cite = array();
}
$url   = ifset( $cite['url'] );
$embed = self::get_embed( $url );
?>
<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'photo' );
if ( isset( $cite['name'] ) ) {
	echo sprintf( '<span class="p-name">%1s</a>', $cite['name'] );
}

?>
</header>
</section>
<?php
if ( $photos && ! has_post_thumbnail( get_the_ID() ) && empty( $src_urls ) )  {
	echo gallery_shortcode(
		array(
			'id'      => get_the_ID(),
			'size'    => 'large',
			'columns' => 1,
			'link'    => 'file',
		)
	);
} else {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	}
}

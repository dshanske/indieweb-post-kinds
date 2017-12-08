<?php
/*
 * Checkin Template
 *
 */

$mf2_post = new MF2_Post( get_the_ID() );
$cite     = $mf2_post->fetch();
if ( ! $cite ) {
	return;
}
$url   = $mf2_post->get_single( $cite['url'] );
$name  = $mf2_post->get_single( $cite['name'] );
$embed = self::get_embed( $url );

?>

<section class="response">
<header>
<?php
echo Kind_Taxonomy::get_before_kind( 'checkin' );
if ( ! $embed ) {
	if ( ! array_key_exists( 'name', $cite ) ) {
		$cite['name'] = self::get_post_type_string( $url );
	}
	if ( isset( $url ) ) {
		echo sprintf( '<a href="%1s" class="u-checkin h-card">%2s</a>', $url, $name );
	} else {
		echo sprintf( '<span class="h-card p-checkin">%1s</span>', $name );
	}
}
?>
</header>
<?php
if ( $cite ) {
	if ( $embed ) {
		echo sprintf( '<blockquote class="e-summary">%1s</blockquote>', $embed );
	}
}

// Close Response
?>
</section>

<?php

<?php
/**
 * Post Kind View Class
 *
 * Handles the logic of adding the kind displays to the post content.
 */
add_action( 'init' , array( 'Kind_View', 'init' ) );

// The Kind_View class sets up the kind display behavior for kinds
class Kind_View {
	public static function init() {
		// If the Theme Has Not Declared Support for Post Kinds
		// Add the Response Display to the Content Filter
		if ( ! current_theme_supports( 'post-kinds' ) ) {
			add_filter( 'the_content', array( 'Kind_View', 'content_response' ), 20 );
		} else {
			add_filter( 'kind_response_display', array( 'Kind_View', 'content_response' ) );
		}
		add_filter( 'the_content_feed', array( 'Kind_View', 'kind_content_feed' ), 20 );

	}

	public static function kind_content_feed( $content ) {
		$response = self::get_kind_response_display();
		$response = str_replace( ']]>', ']]&gt;', $response );
		return $response . $content;
	}
	public static function get_kind_response_display() {
		global $post;
		$kind = get_post_kind_slug( $post );
		// Allow for customized kind_display objects
		switch ( $kind ) {
			default:
				$object = new kind_display( get_the_ID() );
		}
		return $object->get_display();
	}
	public static function content_response ( $content ) {
		$c = '';
		$c .= self::get_kind_response_display();
		$c .= $content;
		return $c;
	}

}  // End Class


function kind_response_display() {
	echo apply_filters( 'kind_response_display', '' );
}

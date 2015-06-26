<?php

// Functions Related to Display

add_action( 'init' , array('kind_view', 'init' ) );

// The kind_view class sets up the kind display behavior for kinds
class kind_view {
  public static function init() {
		// If the Theme Has Not Declared Support for Post Kinds
		// Add the Response Display to the Content Filter
		if (!current_theme_supports('post-kinds')) {
    	add_filter( 'the_content', array('kind_view', 'content_response'), 20 );
		}
		else {
			add_filter( 'kind_response_display', array('kind_view', 'content_response') );
		}
      add_filter( 'the_content_feed', array('kind_view', 'kind_content_feed'), 20 );

	}

	public static function kind_content_feed($content) {
		$response = self::get_kind_response_display();
  	$response = str_replace(']]>', ']]&gt;', $response);
		return $response . $content;
	}
	public static function get_kind_response_display() {
		global $post;
		$kind = get_post_kind_slug($post);
		// Allow for customized kind_display objects
		switch ($kind) {
			default:
				$object = new kind_display( get_the_ID() );
		}
		return $object->get_display();
	}
	public static function content_response ($content ) {
    $c = "";
    $c .= self::get_kind_response_display();
    $c .= $content;
    return $c;
	}

}  // End Class


function kind_response_display() {
  echo apply_filters('kind_response_display', "");
}

<?php 
// Takes Kind Meta from Various Meta Fields and Narrows it Down to One Array

class kind_meta {
	protected $meta=array(); // Raw Meta Data
	protected $kind=""; // Actual or Implied Kind
	protected $meta_key=""; // The primary meta key
	public function __construct( $post_id ) {
		if( class_exists( 'kind_taxonomy' ) ) {
			$this->kind = get_post_kind_slug( get_post( $post_id ) );
		}
		$response = get_post_meta($post_id, 'response', true);
		// Retrieve from the old response array and store as the first
		// entry in a new multidimensional array
		if ( !empty($response) ) {
			$new = array();
			// Convert to new format and update
			if ( !empty($response['title']) ) {
				$new['name'] = $response['title'];
			}
			if ( !empty($response['url']) ) {
				$new['url'] = $response['url'];
			}
			if ( !empty($response['content']) ) {
				$new['content'] = $response['content'];
			}
			if ( !empty($response['published']) ) {
				$new['published'] = $response['published'];
			}
			if ( !empty($response['author']) ) {
				$new['card'] = array();
				$new['card']['name'] = $response['author'];
				if ( !empty($response['icon']) ) {
					$new['card']['photo'] = $response['icon'];
				}
			}
			$new = array_unique($new);
			$new['card'] = array_unique( $new['card'] );
			if( isset($new) ) {
				update_post_meta($post_id, 'mf2_cite', $new);
				delete_post_meta($post_id, 'response');
			}
		}
  	$props = array('cite', 'card', 'category', 'content', 'description', 'duration', 
		'end', 'h', 'in-reply-to','like', 'like-of', 'location', 'name', 'photo', 
		'published', 'repost', 'repost-of', 'rsvp', 'slug', 'start', 'summary',
		'syndication', 'syndicate-to');
		foreach ($props as $prop) {
			$key = 'mf2_' . $prop;
			$this->meta[$prop] = get_post_meta($post_id, $key, true);
		}
		$this->meta = array_filter($this->meta);
		$keys = $this->get_class_mapper();   
		// If there is no kind, then try to derive it from the post meta
		if ($this->kind=="") {
			foreach ($this->meta as $key => $values) {
				if (in_array($key, array_keys($keys))) {
					$this->kind = $keys[$key];
					break;
        }
			}
			// Set the post kind if not set, now that it has been determined
			// This may be overkill
			set_post_kind($post_id, $this->kind);
		}
    $keys = $this->get_class_mapper();
    // Generate a list of properties for the kind
    $props = array_keys($keys, $this->kind);
    foreach ($props as $prop) {
      $key = 'mf2_' . $prop;
      if ( isset( $this->meta[$key] ) ) {
        $this->meta_key = $this->meta[$key];
        break;
      }
    }
    if ( empty($this->meta_key) ) {
      if ( isset ( $this->meta['cite'] ) ) {
        $this->meta_key='cite';
      }
    }
	}

	public function get_kind() {
			if ( isset( $this->kind ) ) {
				return $this->kind;
			}
			return false;
		}

  public function get_all_meta() {
      if ( !empty( $this->meta ) ) {
        return $this->meta;
      }
      return false;
    }
	public function get_meta() {
		if ( ! isset( $this->meta ) ) {
			return false;
		}
		if ( empty ($this->meta_key) ) {
			return false;
		}
		$response = $this->meta[$this->meta_key];
		if (is_multi_array($response) ) {
				if (count($response)==1) {
					$response=array_shift($response);
				}
				if( isset( $response['card'] ) ) {
					if (is_multi_array($response['card'] ) ) {
						if (count($response['card'])==1) {
							$response['card'] = array_shift($response['card']);
						}
					}
				}
		}
		return $response;
	}

	/**
	 * maps classes to kinds
	 * courtesy of a similar function in Semantic Linkbacks
	 *
	 * @return array
	 */
	public function get_class_mapper() {
		$class_mapper = array();
		/*
		 * replies
		 * @link http://indiewebcamp.com/replies
		*/
		$class_mapper["in-reply-to"] = "reply";
		$class_mapper["reply"]       = "reply";
		$class_mapper["reply-of"]    = "reply";
		/*
		 * repost
		 * @link http://indiewebcamp.com/repost
		 */
		$class_mapper["repost"]      = "repost";
		$class_mapper["repost-of"]   = "repost";
		/*
		 * likes
		 * @link http://indiewebcamp.com/likes
		 */
		$class_mapper["like"]        = "like";
		$class_mapper["like-of"]     = "like";
		/*
		 * favorite
		 * @link http://indiewebcamp.com/favorite
		 */
		$class_mapper["favorite"]    = "favorite";
		$class_mapper["favorite-of"] = "favorite";
		/*
		 * rsvp
		 * @link http://indiewebcamp.com/rsvp
		 */
		$class_mapper["rsvp"]        = "rsvp";
		/*
		 * tag
		 * @link http://indiewebcamp.com/tag
		 */
		$class_mapper["tag-of"]      = "tag";

		$class_mapper["listen"]      = "listen";

		$class_mapper["watch"]       = "watch";
		$class_mapper["play"]        = "play";
		$class_mapper["wish"]        = "wish";

		return apply_filters("kind_class_mapper", $class_mapper);
	}

	public static function get_context_class ( $class = '', $classtype='u') {
		$classes = array();
		if ( $this->kind ) {
			switch ( $this->kind ) {
				case "like":
					$classes[] = $classtype.'-like-of';
					break;
				case "favorite":
					$classes[] = $classtype.'-favorite-of';
					break;
				case "repost":
					$classes[] = $classtype.'-repost-of';
					break;
				case "reply":
					$classes[] = $classtype.'-in-reply-to';
					break;
				case "rsvp":
					$classes[] = $classtype.'-in-reply-to';
					break; 
				case "tag":
					$classes[] = $classtype.'-tag-of';
					break;
				case "bookmark":
					break;
				case "listen":
					$classes[] = $classtype.'-listen';
					break;
				case "watch":
					$classes[] = $classtype.'-watch';
					break;
				case "game":
					$classes[] = $classtype.'-play';
					break;
				case "wish":
					$classes[] = $classtype.'-wish';
					break;
			}
		}         
		if ( ! empty( $class ) ) {
			if ( !is_array( $class ) )
				$class = preg_split( '#\s+#', $class );
				$classes = array_merge( $classes, $class );
			} 
			else {
				// Ensure that we always coerce class to being an array.
				$class = array();
			}
			$classes = array_map( 'esc_attr', $classes );
 		/**
		 * Filter the list of CSS kind classes for the current response URL.
		 *
		 *
		 * @param array  $classes An array of kind classes.
		 * @param string $class   A comma-separated list of additional classes added to the link.
		 * @param string $kind 		The slug of the kind the post is set to
		 */
		return apply_filters( 'kind_classes', $classes, $class, $this->kind );
	}

} // End Class

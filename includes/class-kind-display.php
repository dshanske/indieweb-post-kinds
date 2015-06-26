<?php 
// Class Displaying Kinds But Can Be Extended for Different Types

class kind_display {
	protected $meta;
	protected $post_id;
	public function __construct( $post_id ) {
			$this->meta = new kind_meta( $post_id );
			$this->post_id=$post_id;
	}
	public function get_kind() {
			return $this->meta->get_kind();
	}

	// Return the Display
	public function get_display() {
   	if ( ! kind_taxonomy::response_kind( $this->get_kind() ) ) {
			return apply_filters( 'kind-response-display', "", $this->meta->get_kind() );
    }
  	$response = get_post_meta($this->post_id, '_resp_full', true);
  	$options = get_option('iwt_options');
		$content = "";
  	if ( ($options['cacher']==1) && (!empty($response)) ) {  
			return apply_filters( 'kind-response-display', $response);
		}
		$verbstrings = kind_taxonomy::get_verb_strings();
 		$kind = $this->get_kind();
		if($kind) {
	 		$verb = '<span class="verb"><strong>' . $verbstrings[$kind] . '</strong></span>';
		}
		else {
			$verb = "";
		}
		$card = $this->get_hcards();
		if (!empty($card) ) {
			$cards = ' ' . kind_taxonomy::get_author_string($verb) . ' ' . $card;
		}
		else {
			$cards = "";
		}
		$m = $this->meta->get_meta();
		if ( isset($m['url']) ) {
			$urlatr = array (
										'class' => array('u-url', 'p-name')
									);
			if ( isset($m['name'] ) ) {
						$url = $this->get_url_link($m['url'], $m['name'], $urlatr);
			}
			else {
				$url = $this->get_url_link($m['url'], self::get_post_type_string($m['url']), $urlatr);
			}
			$pub = ' ' . kind_taxonomy::get_publication_string( $this->meta->get_kind() ). ' ';
			if ( isset($m['publication'] ) ) { 
				$pub .= '<em>' . $m['publication'] . '</em>';
			}
			else {
				$pub .= '<em>' . extract_domain_name($m['url']) . '</em>';
			}
			$content .= $this->get_embed($m['url']);
		}
		else {
			$url = "";
			$pub = "";
		}
    if ( isset($m['duration']) ) {
        $time = ' <em>' . kind_taxonomy::get_duration_string($kind) . ' ' . '<span class="p-duration">' . $m['duration'] . '</span></em>';
      }
		else {
				$time = "";
		}

		if ( isset($m['content'] ) ) {
			$content .= '<blockquote class="e-content">' . $m['content'] . '</blockquote>';
		}
		$c = $verb . ' ' . $url . $pub . $cards . $time . $content;		
		$c = trim($c);
		if (!empty($c)) {		
	  	return '<div ' . $this->context_class('response h-cite', 'p') . '>' . $c . '</div>';
		}
		else { return ""; }
	}

	// Echo the output of get_display 
	public function display() {
		echo $this->get_display();
	}

  public function get_context_class ( $class = '', $classtype='u') {
    $classes = array();
    if ( $this->get_kind() ) {
      switch ( $this->get_kind() ) {
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
					$classes[] = $classtype.'-bookmark-of';
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
        $class = array($class);
      }
      $classes = array_map( 'esc_attr', $classes );
    /**
     * Filter the list of CSS kind classes for the current response URL.
     *
     *
     * @param array  $classes An array of kind classes.
     * @param string $class   A comma-separated list of additional classes added to the link.
     * @param string $kind    The slug of the kind the post is set to
     */
   return apply_filters( 'kind_classes', $classes, $class, $this->get_kind() );
  }

	public function context_class( $class = '', $classtype='u' ) {
		// Separates classes with a single space, collates classes
		return 'class="' . join( ' ', $this->get_context_class( $class, $classtype ) ) . '"';
	}

  /**
   * Returns an array of domains with the post type terminologies
   *
   * @return array A translated post type string for specific domain or 'a post'
   */
  public static function get_post_type_string($url) {
    $strings = array(
      'twitter.com' => _x( 'a tweet', 'Post kind' ),
      'vimeo.com' => _x( 'a video', 'Post kind' ),
      'youtube.com'   => _x( 'a video', 'Post kind' ),
			'instagram.com' => _x( 'an image', 'Post kind' )
    );
    $domain = extract_domain_name($url);
    if (array_key_exists($domain, $strings) ) {
      return apply_filters( 'kind_post_type_string', $strings[$domain] );
    }
    else {
      return _x('a post', 'Post kind');
    }
  }


	public function get_hcards() {
		$cards = $this->meta->get_hcard();
		if(!$cards) { return false; }
		$output = "";
		if (is_multi_array($cards) ) {
			foreach ($cards as $card ) {
				$output .= $this->get_hcard($card);
			}
			return $output;
		}
		return $this->get_hcard($cards);
	}
	public function get_hcard($card, $author = false) {
		if (empty($card) ) { return ""; }
		$hcardatr = array (
									'class' => array ( 'h-card' ), 
								);
		if ($author) {
			$hcardatr['class'][] = 'p-author';
			$hcardatr['rel'][]='author';
		}
		$data = "";
		$name = "";
		if (isset($card['family-name']) ) {
				$name .= $this->get_formatted($card['honorific-prefix'], ' class="p-honorific-prefix"');
				$name .= $this->get_formatted($card['given-name'], ' class="p-given-name"');
        $name .= $this->get_formatted($card['additional-name'], ' class="p-additional-name"');
        $name .= $this->get_formatted($card['honorific-suffix'], ' class="p-honorific-suffix"');
		}
		else {
				$name .= $card['name'];
		}
    if (! empty($card['photo']) ) {
      $data .= '<img class="u-photo" src="' . $card['photo'] . '" title="' . $card['name'] . '" />';
		}
		$data .= $this->get_formatted($name, $atr = array( 
                                                          'class' => array( 'p-name' )
                                                        ) );
   	if (! empty($card['url']) ) {
				$data = $this->get_url_link($card['url'], $data, array( 
																													'class' => array('u-url'), 
																													'title' => array($card['name']) 
																												 ) );
		}
		foreach ( $card as $key => $value ) {
				if ( ! in_array($key, array( 'photo', 'name', 'url' ) ) ) {
					$data .= $this->get_formatted($value, $atr = array( 
																													'class' => array( $this->map_key('$key') ) 
																												) );
				}
    }
		$hcard = $this->get_formatted($data, $hcardatr);
		return $hcard;
	}

	public function map_key($key, $pre="") {
		if ( ! empty( $pre ) ) {
			return $pre.'-'.$key;
		} 
		$p = array( 'name', 'honorific-prefix', 'given-name', 'additional-name', 'family-name', 'sort-string',
								'honorific-suffix', 'nickname', 'category', 'adr', 'post-office-box', 'extended-address',
								'street-address', 'locality', 'region', 'postal-code', 'country-name', 'label', 'latitude',
								'longitude', 'altitude', 'tel', 'note', 'org', 'job-title', 'role', 'sex', 'gender-identity' );
		if ( in_array($key, $p) ) {
				return 'p-'.$key;
		}
		else {
				return 'u-'.$key;
		}
	}

	public function hcard() {
		echo $this->get_hcards();
	}
	// Take an array of attributes and output them as a string
	public function get_attributes($classes = null) {
		if (!$classes) {
			return "";
		}
		$return = "";
		foreach ( $classes as $key => $value ) {
			$return .= ' ' . esc_attr( $key ) . '="' . esc_attr( join( ' ', array_unique($value) ) ) . '"';
		}
		return $return;
	}

	// Takes a url and returns it as marked up HTML
	public function get_url_link($url, $name="", $atr="") {
		if ( empty($url) ) {
			return "";
		}
		if( is_array($atr) ) {
				$atr = $this->get_attributes($atr);
		}
		$return =  '<a ' . $atr . ' href="' . $url . '">' . $name . '</a>';
		return $return;
	}

	// Returns the HTML for a tag with attributes encasing the data
	public function get_formatted($data, $atr = "", $tag = "span") {
		if ($data=="") {
			return "";
		}
		if (is_array($atr) ) {
			$atr = $this->get_attributes($atr);
		}
		$return = '<' . $tag . $atr . '>' . $data . '</' . $tag . '>';
		return $return;
	}

	public function get_embed($url) {
			$options = get_option('iwt_options');
			if($options['embeds'] == 0) {
					return "";
			}
    	if( isset($GLOBALS['wp_embed']) ) {
      	$embed = $GLOBALS['wp_embed']->autoembed($url);
    	}
			// Passes through the oembed handler in WordPress
			$host = extract_domain_name($url);
			switch ($host) {
				case 'facebook.com':
      		$embed = $this->get_embed_facebook ($url);
					break;
				case 'plus.google.com':
					$embed = $this->get_embed_gplus ($url);
					break;
			}
      if ( strcmp($embed, $url) == 0 ) {
        $embed = "";
      }
			else { 
				$embed = '<div class="embed">' . $embed . '</div>';
			}
			return $embed;
	}

	public function get_embed_facebook ($url) {
  	$embed = '<div id="fb-root"></div>';
		$embed .= '<script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/all.js#xfbml=1"; fjs.parentNode.insertBefore(js, fjs); }(document, \'script\', \'facebook-jssdk\'));</script>';
		$embed .= '<div class="fb-post" data-href="' . esc_url($url) . '" data-width="466"><div class="fb-xfbml-parse-ignore"><a href="' . esc_url($url) .  '">Post</a></div></div>';
		return $embed;
	} 

	public function get_embed_gplus ($url) {
		$embed = '<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>';
		$embed .= '<div class="g-post" data-href="' . esc_url($url) . '"></div>';
		return $embed;
	} 	
} // End Class kind_display

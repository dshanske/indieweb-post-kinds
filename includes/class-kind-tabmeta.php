<?php
/**
 * Post Kind Post Tabbed MetaBox Class
 *
 * Sets Up Tabbed Metabox in the Posting UI for Kind data.
 */
add_action( 'init' , array( 'Kind_Tabmeta', 'init' ) );

add_action( 'wp_ajax_kind_test', 'kind_ajaxtest' );

function kind_ajaxtest() {
	$response = array( 'result' => 'successful' );
	wp_send_json( $response );
}


class Kind_Tabmeta {
	public static function init() {
		// Add meta box to new post/post pages only
		add_action( 'load-post.php', array( 'Kind_Tabmeta', 'kindbox_setup' ) );
		add_action( 'load-post-new.php', array( 'Kind_Tabmeta', 'kindbox_setup' ) );
		add_action( 'save_post', array( 'Kind_Tabmeta', 'save_post' ), 8, 2 );
		add_action( 'transition_post_status', array( 'Kind_Tabmeta', 'transition_post_status' ) ,5,3 );
		add_action( 'wp_ajax_kind_urlfetch', array( 'Kind_Tabmeta', 'urlfetch' ) );
	}

	/* Meta box setup function. */
	public static function kindbox_setup() {
		/* Add meta boxes on the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( 'Kind_Tabmeta', 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( 'Kind_Tabmeta', 'enqueue_admin_scripts' ) );

	}

	public static function enqueue_admin_scripts() {
		if ( 'post' === get_current_screen()->id ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui' );

			wp_enqueue_script(
				'jquery-ui-timepicker',
				plugins_url( 'indieweb-post-kinds/includes/tabs/jquery.timepicker.min.js' ),
				array( 'jquery' ),
				POST_KINDS_VERSION
			);

			wp_enqueue_script(
				'kindmeta-time',
				plugins_url( 'indieweb-post-kinds/includes/tabs/time.js' ),
				array( 'jquery' ),
				POST_KINDS_VERSION
			);

			wp_enqueue_script(
				'kindmeta-tabs',
				plugins_url( 'indieweb-post-kinds/includes/tabs/tabs.js' ),
				array( 'jquery' ),
				POST_KINDS_VERSION
			);

			wp_enqueue_script(
				'kindmeta-response',
				plugins_url( 'indieweb-post-kinds/includes/tabs/retrieve.js' ),
				array( 'jquery' ),
				POST_KINDS_VERSION
			);

			wp_enqueue_script(
				'moment',
				'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.min.js',
				array( 'jquery' ),
				'2.10.6'
			);
		}
	}


	/* Create one or more meta boxes to be displayed on the post editor screen. */
	public static function add_meta_boxes() {
		add_meta_box(
			'tabbox-meta',      // Unique ID
			esc_html__( 'Post Properties', 'Post kind' ),    // Title
			array( 'Kind_Tabmeta', 'display_metabox' ),   // Callback function
			'post',         // Admin page (or post type)
			'normal',         // Context
			'default'         // Priority
		);
	}

	public static function display_metabox( $object, $box ) {
		wp_nonce_field( 'tabkind_metabox', 'tabkind_metabox_nonce' );
		$meta = new kind_meta( $object->ID );
		$cite = $meta->get_cite();
		$author = $meta->get_author();
		$url = $meta->get_url();
		if ( ! $url ) {
			if ( array_key_exists( 'kindurl', $_GET ) ) {
				$url = $_GET['kindurl'];
			}
		}
		$time = $meta->get_time();
		include_once( 'tabs/tab-navigation.php' );
	}

	public static function change_title( $data, $postarr ) {

		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! empty( $data['post_title'] ) ) {
			return $data;
		}
		$kind_strings = Kind_Taxonomy::get_strings();
		$kind = get_term_by( taxonomy_id, $_POST['tax_input']['kind'], 'kind' );
		$title = $kind_strings[ $kind->slug ];
		if ( ! empty( $_POST['cite_name'] ) ) {
				$title .= ' - ' . $_POST['cite_name'];
		}
		$data['post_title'] = $title;
		$data['post_name'] = sanitize_title( $data['post_title'] );
		return $data;
	}

	/* Save the meta box's post metadata. */
	public static function save_post( $post_id, $post ) {
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['tabkind_metabox_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['tabkind_metabox_nonce'], 'tabkind_metabox' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}
		$kind = get_post_kind_slug( $post );
		$meta = new Kind_Meta( $post );
		if ( isset( $_POST['time'] ) ) {
			if ( isset( $_POST['time']['start_date'] ) || isset( $_POST['time']['start_time'] ) ) {
				$start = $meta->build_time( $_POST['time']['start_date'], $_POST['time']['start_time'], $_POST['time']['start_offset'] );
			}
			if ( isset( $_POST['time']['end_date'] ) || isset( $_POST['time']['end_time'] ) ) {
				$end = $meta->build_time( $_POST['time']['end_date'], $_POST['time']['end_time'], $_POST['time']['end_offset'] );
			}
		}
		if ( isset( $_POST['cite'] ) ) {
			if ( in_array( $kind, array( 'like', 'reply', 'repost', 'favorite', 'bookmark' ) ) ) {
				if ( ! empty( $start ) ) {
					$_POST['cite']['published'] = $start;
					error_log( 'Start: ' . $start );
				}
				if ( ! empty( $end )  ) {
					$_POST['cite']['updated'] = $end;
					error_log( 'End: ' . $end );
				}
			} else {
				$meta->set_time( $start, $end );
			}
			$meta->set_cite( $_POST['cite'] );
		}
		if ( isset( $_POST['author'] ) ) {
			$meta->set_author( $_POST['author'] );
		}
		if ( isset( $_POST['url'] ) ) {
			$meta->set_url( $_POST['url'] );
		}
		// This is temporary - planning on improving this later
		if ( isset( $_POST['duration'] ) ) {
			$meta->set( 'duration', $_POST['duration'] );
		}
		$meta->save_meta( $post );
	}

	public static function transition_post_status( $new, $old, $post ) {
		if ( $new == 'publish' && $old != 'publish' ) {
			self::save_post( $post->ID, $post );
		}
	}

	/**
	 * Retrieves the body of a URL for parsing.
	 *
	 * @param string $url A valid URL.
	 */
	private static function fetch($url) {
		global $wp_version;
		if ( ! isset( $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.' ) );
		}
		$response = wp_safe_remote_get( $url, array(
			'timeout' => 30,
			// Use an explicit user-agent for Post Kinds
			'user-agent' => 'Post Kinds (WordPress/' . $wp_version . '); ' . get_bloginfo( 'url' ),
		) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = wp_remote_retrieve_body( $response );
		return $body;
	}
	/**
	 * Parses marked up HTML.
	 *
	 * @param string $content HTML marked up content.
	 */
	private static function parse ($content, $url) {
		$ogpdata = self::ogpparse( $content );
		$mf2data = self::mf2parse( $content, $url );
		$data = array_merge( $ogpdata, $mf2data );
		$data = array_filter( $data );
		// If Publication is Not Set, use the domain name instead
		$data['publication'] = ifset( $data['publication'] ) ?: self::pretty_domain_name( $url );
		// If Name is Not Set Use Title Tag
		if ( ! isset( $data['name'] ) ) {
			preg_match( '/<title>(.+)<\/title>/i', $content, $match );
			$data['name'] = trim( $match[1] );
		}
		if ( isset( $data['name'] ) ) {
			if ( isset( $data['summary'] ) ) {
				if ( false !== stripos( $data['summary'], $data['name'] ) ) {
					unset( $data['name'] );
				}
			}
		}
		/**
		 * Parse additionally by plugin.
		 *
		 * @param array $data An array of properties.
		 * @param string $content The content of the retrieved page.
		 * @param string $url Source URL
		 */
		return apply_filters( 'kind_parse_data', $data, $content, $url );
	}

	// Give a Proper Name for Set Sites
	public static function pretty_domain_name( $url ) {
		switch ( $url ) {
			case 'twitter.com':
			return  _( 'Twitter', 'Post kinds' );
			break;
			default:
			return extract_domain_name( $url );
		}
	}

	public static function urlfetch() {
		global $wpdb;
		if ( empty( $_POST['kind_url'] ) ) {
				wp_send_json_error( new WP_Error( 'nourl', __( 'You must specify a URL' ) ) );
		}
		if ( filter_var( $_POST['kind_url'], FILTER_VALIDATE_URL ) === false ) {
				wp_send_json_error( new WP_Error( 'badurl', __( 'Input is not a valid URL' ) ) );
		}

		$content = self::fetch( $_POST['kind_url'] );
		if ( is_wp_error( $content ) ) {
			wp_send_json_error( $response );
		}
		wp_send_json_success( self::parse( $content, $_POST['kind_url'] ) );
	}

	/*
	Parses marked up HTML using MF2.
	*
	* @param string $content HTML marked up content.
	*/
	private static function mf2parse($content, $url) {
		$data = array();
		$host = extract_domain_name( $url );
		switch ( $host ) {
			case 'twitter.com':
				$parsed = Mf2\Shim\parseTwitter( $content, $url );
				break;
			default:
				$parsed = Mf2\parse( $content, $url );
		}
		if ( mf2_cleaner::isMicroformatCollection( $parsed ) ) {
			$entries = mf2_cleaner::findMicroformatsByType( $parsed, 'h-entry' );
			if ( $entries ) {
				$entry = $entries[0];
				if ( mf2_cleaner::isMicroformat( $entry ) ) {
					foreach ( $entry['properties'] as $key => $value ) {
						$data[$key] = mf2_cleaner::getPlaintext( $entry, $key );
					}
					$data['published'] = mf2_cleaner::getPublished( $entry );
					$data['updated'] = mf2_cleaner::getUpdated( $entry );
						  $data['name'] = mf2_cleaner::getPlaintext( $entry, 'name' );
					$data['content'] = mf2_cleaner::getHtml( $entry, 'content' );
					$data['summary'] = mf2_cleaner::getHtml( $entry, 'summary' );
					$data['name'] = trim( preg_replace( '/https?:\/\/([^ ]+|$)/', '', $data['name'] ) );
					$author = mf2_cleaner::getAuthor( $entry );
					if ( $author ) {
							$data['author'] = array();
						foreach ( $author['properties'] as $key => $value ) {
							$data['author'][$key] = mf2_cleaner::getPlaintext( $author, $key );
						}
							$data['author'] = array_filter( $data['author'] );
					}
				}
			}
		}
		$data = array_filter( $data );
		if ( array_key_exists( 'name', $data ) ) {
			if ( ! array_key_exists( 'summary', $data ) || ! array_key_exists( 'content', $data ) ) {
				unset( $data['name'] );
			}
		}
		return $data;
	}

  public static function get_meta_tags( $source_content ) {
    if ( ! $source_content ) {
      return null;
    }
    $meta = array();
    if ( preg_match_all( '/<meta [^>]+>/', $source_content, $matches ) ) {
      $items = $matches[0];

      foreach ( $items as $value ) {
        if ( preg_match( '/(property|name)="([^"]+)"[^>]+content="([^"]+)"/', $value, $new_matches ) ) {
          $meta_name  = $new_matches[2];
          $meta_value = $new_matches[3];

          // Sanity check. $key is usually things like 'title', 'description', 'keywords', etc.
          if ( strlen( $meta_name ) > 100 ) {
            continue;
          }
          $meta[$meta_name] = $meta_value;
        }
      }
    }
    return $meta;
  }

	/**
	 * Parses marked up HTML using OGP or other meta tags.
	 *
	 * @param string $content HTML marked up content.
	 */
	private static function ogpparse($content) {
		$meta = self::get_meta_tags( $content );
		$data = array();
		$data['name'] = ifset( $meta['og:title'] ) ?: ifset( $meta['twitter:title'] ) ?: ifset( $meta['og:music:song'] );
		$data['summary'] = ifset( $meta['og:description'] ) ?: ifset( $meta['twitter:description'] );
		$data['site'] = ifset( $meta['og:site'] ) ?: ifset( $meta['twitter:site'] );
		$data['featured'] = ifset( $meta['og:image'] ) ?: ifset( $meta['twitter:image'] );
		$data['publication'] = ifset( $meta['og:site_name'] ) ?: ifset( $meta['og:music:album'] );
		$data['published'] = ifset( $meta['og:article:published_time'] ) ?: ifset( $meta['pdate'] ) ?: ifset( $meta['og:article:published'] ) ?: ifset( $meta['og:music:release_date'] ) ?: ifset( $meta['og:video:release_date'] );
		$metatags = ifset( $meta['article:tag'] ) ?: ifset( $meta['og:video:tag'] );
		$tags = array();
		if ( is_array( $metatags ) ) {
			foreach ( $metatags as $tag ) {
				$tags[] = str_replace( ',', ' -', $tag );
			}
			$tags = array_filter( $tags );
		}
		$data['tags'] = ifset( $data['tags'] ) ?: implode( ',' ,$tags );
		// Extended Parameters
		$data['audio'] = ifset( $meta['og:audio'] );
		$data['video'] = ifset( $meta['og:video'] );
		$data['duration'] = ifset( $meta['music:duration'] ) ?: ifset( $meta['video:duration'] );
		$data['type'] = ifset( $meta['og:type'] );
		return array_filter( $data );
	}
}
?>

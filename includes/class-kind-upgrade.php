<?php
/**
 * Kind_Upgrade class.
 *
 * @package Post_Kinds
 * @since   4.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * One-time upgrade of stored post data.
 *
 * Gives published posts without a kind one, moves citations out of older
 * storage locations, repairs citations that earlier versions damaged, and
 * records the media IDs of posts saved before they were tracked. It runs in batches through WP-Cron whenever the stored
 * upgrade version is behind Kind_Upgrade::VERSION, and can be rerun with
 * `wp post-kinds upgrade`.
 *
 * @since 4.0.0
 */
class Kind_Upgrade {

	/**
	 * Version of the stored data this class upgrades to.
	 *
	 * Increase it when upgrade_post() gains a step that existing sites need.
	 *
	 * - 1: citations moved and repaired, media IDs recorded (4.0.0).
	 * - 2: photo flag recorded for the photos archive (4.0.0).
	 * - 3: kinds assigned to published posts without one (4.0.0).
	 *
	 * @since 4.0.0
	 * @var int
	 */
	const VERSION = 3;

	/**
	 * Option holding the version the stored data was last upgraded to.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const VERSION_OPTION = 'kind_upgrade_version';

	/**
	 * Option holding the last post ID a running upgrade has processed.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const PROGRESS_OPTION = 'kind_upgrade_last_id';

	/**
	 * Option set while an upgrade has given posts the article kind, so
	 * complete() can keep them on the home page.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const ARTICLE_OPTION = 'kind_upgrade_assigned_article';

	/**
	 * Cron hook that runs one batch.
	 *
	 * @since 4.0.0
	 * @var string
	 */
	const CRON_HOOK = 'kind_upgrade_batch';

	/**
	 * Hooks the batch runner, and schedules it when an upgrade is pending.
	 *
	 * @since 4.0.0
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( static::class, 'run_batch' ) );
		if ( self::is_pending() && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time(), self::CRON_HOOK );
		}
	}

	/**
	 * Returns whether the stored data needs upgrading.
	 *
	 * @since 4.0.0
	 *
	 * @return bool True if the stored version is behind Kind_Upgrade::VERSION.
	 */
	public static function is_pending() {
		return (int) get_option( self::VERSION_OPTION, 0 ) < self::VERSION;
	}

	/**
	 * Returns the number of posts upgraded per batch.
	 *
	 * @since 4.0.0
	 *
	 * @return int Batch size, at least 1.
	 */
	public static function batch_size() {
		/**
		 * Filters the number of posts the upgrade routine processes per batch.
		 *
		 * @since 4.0.0
		 *
		 * @param int $size Posts per batch. Default 50.
		 */
		return max( 1, (int) apply_filters( 'kind_upgrade_batch_size', 50 ) );
	}

	/**
	 * Upgrades the next batch of posts, then schedules the following one or
	 * marks the upgrade complete.
	 *
	 * @since 4.0.0
	 */
	public static function run_batch() {
		if ( ! self::is_pending() ) {
			return;
		}
		$size = self::batch_size();
		$ids  = self::get_post_ids( (int) get_option( self::PROGRESS_OPTION, 0 ), $size );
		foreach ( $ids as $id ) {
			self::upgrade_post( $id );
		}
		if ( count( $ids ) < $size ) {
			self::complete();
			return;
		}
		update_option( self::PROGRESS_OPTION, end( $ids ), false );
		wp_schedule_single_event( time(), self::CRON_HOOK );
	}

	/**
	 * Records the upgrade as complete.
	 *
	 * Posts without a kind were always shown on the home page, even when it
	 * is limited to selected kinds. If the upgrade gave posts the article
	 * kind, article is added to that selection so they stay there.
	 *
	 * @since 4.0.0
	 *
	 * @return bool True if article was added to the home page kinds.
	 */
	public static function complete() {
		$added = false;
		if ( get_option( self::ARTICLE_OPTION ) ) {
			$firehose = get_option( 'kind_firehose' );
			if ( ! empty( $firehose ) && is_array( $firehose ) && ! in_array( 'article', $firehose, true ) ) {
				$firehose[] = 'article';
				$added      = update_option( 'kind_firehose', $firehose );
			}
			delete_option( self::ARTICLE_OPTION );
		}
		update_option( self::VERSION_OPTION, self::VERSION );
		delete_option( self::PROGRESS_OPTION );
		wp_clear_scheduled_hook( self::CRON_HOOK );
		return $added;
	}

	/**
	 * Returns the IDs of posts after a given ID, in ascending order.
	 *
	 * @since 4.0.0
	 *
	 * @param int $after Return posts with a greater ID than this.
	 * @param int $limit Maximum number of IDs.
	 * @return int[] Post IDs.
	 */
	public static function get_post_ids( $after, $limit ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- A keyset scan over every post, run once; WP_Query cannot filter by ID range.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'post' AND ID > %d ORDER BY ID ASC LIMIT %d", $after, $limit ) );
		return array_map( 'intval', $ids );
	}

	/**
	 * Upgrades one post's stored data.
	 *
	 * @since 4.0.0
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $dry_run Optional. Report changes without saving them. Media
	 *                      lists are only recorded when saving. Default false.
	 * @return string[] Descriptions of the changes made, or that would be made.
	 */
	public static function upgrade_post( $post_id, $dry_run = false ) {
		if ( 'post' !== get_post_type( $post_id ) ) {
			return array();
		}
		// The kind comes first: the citation step uses its property.
		$changes = array_merge( self::assign_kind( $post_id, $dry_run ), self::upgrade_citation( $post_id, $dry_run ) );
		if ( ! $dry_run ) {
			$before = self::get_media_meta( $post_id );
			Kind_Media_Metadata::save_post( $post_id );
			if ( self::get_media_meta( $post_id ) !== $before ) {
				$changes[] = 'recorded media IDs';
			}
		}
		return $changes;
	}

	/**
	 * Gives a published post without a kind one.
	 *
	 * The kind comes from the post's stored properties, using Post Type
	 * Discovery (for example in-reply-to makes a reply, and rsvp an RSVP).
	 * Posts with none get article, which the editor already selects for them.
	 * Drafts get the default kind when they are published.
	 *
	 * @since 4.0.0
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $dry_run Report the change without saving it.
	 * @return string[] Description of the change, if any.
	 */
	private static function assign_kind( $post_id, $dry_run ) {
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return array();
		}
		$terms = wp_get_object_terms( $post_id, 'kind', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $terms ) || ! empty( $terms ) ) {
			return array();
		}
		$kind = Kind_Plugins::discover_kind( self::get_stored_mf2( $post_id ) );
		if ( in_array( $kind, array( '', 'note', 'article' ), true ) ) {
			$kind   = 'article';
			$change = 'assigned kind article';
		} else {
			$change = 'assigned kind ' . $kind . ' from its properties';
		}
		if ( ! $dry_run ) {
			set_post_kind( $post_id, $kind );
			if ( 'article' === $kind ) {
				update_option( self::ARTICLE_OPTION, 1, false );
			}
		}
		return array( $change );
	}

	/**
	 * Returns a post's stored mf2_ properties as an h-entry.
	 *
	 * @since 4.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return array mf2 object.
	 */
	private static function get_stored_mf2( $post_id ) {
		$properties = array();
		foreach ( get_post_meta( $post_id ) as $key => $values ) {
			if ( 0 !== strpos( $key, 'mf2_' ) ) {
				continue;
			}
			$value = maybe_unserialize( $values[0] );
			if ( empty( $value ) ) {
				continue;
			}
			$properties[ substr( $key, 4 ) ] = wp_is_numeric_array( $value ) ? $value : array( $value );
		}
		return array(
			'type'       => array( 'h-entry' ),
			'properties' => $properties,
		);
	}

	/**
	 * Moves a post's citation into its kind's property and repairs its shape.
	 *
	 * @since 4.0.0
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $dry_run Report changes without saving them.
	 * @return string[] Descriptions of the changes.
	 */
	private static function upgrade_citation( $post_id, $dry_run ) {
		$changes   = array();
		$kind_post = new Kind_Post( $post_id );
		$property  = Kind_Taxonomy::get_kind_info( $kind_post->get_kind(), 'property' );
		if ( empty( $property ) ) {
			return $changes;
		}
		$key    = 'mf2_' . $property;
		$stored = get_post_meta( $post_id, $key, true );
		$value  = $stored;
		$remove = array();

		if ( empty( $value ) ) {
			if ( metadata_exists( 'post', $post_id, 'mf2_cite' ) ) {
				$value     = get_post_meta( $post_id, 'mf2_cite', true );
				$remove[]  = 'mf2_cite';
				$changes[] = 'moved mf2_cite to ' . $key;
			} elseif ( metadata_exists( 'post', $post_id, 'response' ) ) {
				$value     = self::response_to_jf2( get_post_meta( $post_id, 'response', true ) );
				$remove[]  = 'response';
				$changes[] = 'moved response to ' . $key;
			}
			if ( wp_is_numeric_array( $value ) && 1 === count( $value ) && ! is_array( $value[0] ) ) {
				$value = $value[0];
			}
		}

		$repaired = self::repair_citation( $value, $kind_post->get_cite_type() );
		if ( $repaired !== $value ) {
			$changes[] = 'repaired ' . $key;
		}

		if ( $dry_run || empty( $changes ) ) {
			return $changes;
		}
		if ( $repaired !== $stored && ! empty( $repaired ) ) {
			update_post_meta( $post_id, $key, wp_slash( $repaired ) );
		}
		foreach ( $remove as $old ) {
			delete_post_meta( $post_id, $old );
		}
		return $changes;
	}

	/**
	 * Returns a stored citation as valid mf2.
	 *
	 * A jf2 citation is converted to mf2. A microformat whose type earlier reads replaced
	 * with 'cite' gets the kind's type back, and properties that repeated saves
	 * nested inside themselves are flattened. A URL, or a list as Micropub
	 * stores it, is checked item by item.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed  $cite Stored citation.
	 * @param string $type jf2 type for the kind, from Kind_Post::get_cite_type().
	 * @return mixed The repaired citation; unchanged if nothing needed repair.
	 */
	public static function repair_citation( $cite, $type ) {
		if ( ! is_array( $cite ) || empty( $cite ) ) {
			return $cite;
		}
		if ( wp_is_numeric_array( $cite ) ) {
			foreach ( $cite as $index => $item ) {
				$cite[ $index ] = self::repair_citation( $item, $type );
			}
			return $cite;
		}
		if ( ! isset( $cite['properties'] ) ) {
			if ( empty( $cite['type'] ) ) {
				$cite['type'] = $type;
			}
			return \ParseThis\jf2_to_mf2( $cite );
		}
		if ( is_array( $cite['properties'] ) ) {
			$cite['properties'] = self::flatten_properties( $cite['properties'] );
		}
		if ( empty( $cite['type'] ) || ! is_array( $cite['type'] ) ) {
			$cite['type'] = array( 'h-' . $type );
		}
		return $cite;
	}

	/**
	 * Flattens microformat properties that were nested inside themselves.
	 *
	 * Before 4.0.0, each metabox save merged the whole stored microformat into
	 * the new properties, one level deeper per save. Newer values win. Keys
	 * that cannot be property names, such as 'type' and numeric keys, and
	 * values that are not lists are dropped.
	 *
	 * @since 4.0.0
	 *
	 * @param array $properties Microformat properties.
	 * @return array Flat properties.
	 */
	public static function flatten_properties( $properties ) {
		while ( isset( $properties['properties'] ) && is_array( $properties['properties'] ) ) {
			$nested = $properties['properties'];
			unset( $properties['properties'] );
			$properties = array_merge( $nested, $properties );
		}
		unset( $properties['properties'], $properties['type'] );
		foreach ( $properties as $key => $value ) {
			if ( ! is_string( $key ) || ! is_array( $value ) ) {
				unset( $properties[ $key ] );
			}
		}
		return $properties;
	}

	/**
	 * Converts the response meta of very old versions to a jf2 citation.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $response Stored response meta.
	 * @return array jf2 citation; empty if there is nothing to keep.
	 */
	private static function response_to_jf2( $response ) {
		if ( ! is_array( $response ) ) {
			return array();
		}
		$cite = array_filter(
			array(
				'name'      => $response['title'] ?? '',
				'url'       => $response['url'] ?? '',
				'content'   => $response['content'] ?? '',
				'published' => $response['published'] ?? '',
			)
		);
		if ( ! empty( $response['author'] ) ) {
			$cite['author'] = array_filter(
				array(
					'type'  => 'card',
					'name'  => $response['author'],
					'photo' => $response['icon'] ?? '',
				)
			);
		}
		return $cite;
	}

	/**
	 * Returns a post's recorded media IDs, for comparison.
	 *
	 * @param int $post_id Post ID.
	 * @return array Media IDs keyed by meta key.
	 */
	private static function get_media_meta( $post_id ) {
		$media = array();
		foreach ( array( 'img', 'audio', 'video' ) as $type ) {
			$media[ $type ] = get_post_meta( $post_id, '_content_' . $type . '_ids', true );
		}
		$media['photo'] = get_post_meta( $post_id, '_kind_has_photo', true );
		return $media;
	}

	/**
	 * Upgrades stored post kind data now.
	 *
	 * Gives published posts without a kind one, moves citations out of older
	 * storage locations, repairs citations that earlier versions damaged, and
	 * records the media in each post's content.
	 * It is safe to run more than once. Without --post, it also marks the
	 * upgrade complete, so the background upgrade stops.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<id>]
	 * : Upgrade only this post.
	 *
	 * [--dry-run]
	 * : Report what would change without saving. Media lists are not checked.
	 *
	 * ## EXAMPLES
	 *
	 *     wp post-kinds upgrade --dry-run
	 *     wp post-kinds upgrade
	 *     wp post-kinds upgrade --post=123
	 *
	 * @since 4.0.0
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Associative arguments.
	 */
	public static function cli( $args, $assoc_args ) {
		$dry_run = ! empty( $assoc_args['dry-run'] );
		$changed = 0;
		$checked = 0;
		$run     = function ( $ids ) use ( $dry_run, &$changed, &$checked ) {
			foreach ( $ids as $id ) {
				++$checked;
				$changes = self::upgrade_post( $id, $dry_run );
				if ( $changes ) {
					++$changed;
					WP_CLI::log( sprintf( 'Post %d: %s', $id, implode( ', ', $changes ) ) );
				}
			}
		};

		if ( isset( $assoc_args['post'] ) ) {
			$run( array( absint( $assoc_args['post'] ) ) );
		} else {
			$size = self::batch_size();
			$ids  = self::get_post_ids( 0, $size );
			while ( $ids ) {
				$run( $ids );
				$ids = count( $ids ) === $size ? self::get_post_ids( end( $ids ), $size ) : array();
			}
		}

		if ( ! $dry_run && ! isset( $assoc_args['post'] ) && self::complete() ) {
			WP_CLI::log( 'Added Article to the kinds shown on the home page, so posts that had no kind stay there.' );
		}
		$message = $dry_run ? '%d of %d posts would change.' : '%d of %d posts changed.';
		WP_CLI::success( sprintf( $message, $changed, $checked ) );
	}
}

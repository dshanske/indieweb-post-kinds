<?php

// get_feed_build_date introduced in WordPress 5.2
if ( ! function_exists( 'get_feed_build_date' ) ) {

	function get_feed_build_date( $format ) {
		global $wp_query;

		if ( empty( $wp_query ) || ! $wp_query->have_posts() ) {
			// Fallback to last time any post was modified or published.
			return get_lastpostmodified( 'GMT' );
		}

			// Extract the post modified times from the posts.
			$modified_times = wp_list_pluck( $wp_query->posts, 'post_modified_gmt' );

			// If this is a comment feed, check those objects too.
		if ( $wp_query->is_comment_feed() && $wp_query->comment_count ) {
			// Extract the comment modified times from the comments.
			$comment_times = wp_list_pluck( $wp_query->comments, 'comment_date_gmt' );

				// Add the comment times to the post times for comparison.
				$modified_times = array_merge( $modified_times, $comment_times );
		}

			// Determine the maximum modified time.
			$max_modified_time = max(
				array_map(
					function ( $time ) use ( $format ) {
										return mysql2date( $format, $time, false );
					},
					$modified_times
				)
			);

			/**
			 * Filters the date the last post or comment in the query was modified.
			 *
			 * @since 5.2.0
			 *
			 * @param string $max_modified_time Date the last post or comment was modified in the query.
			 * @param string $format            The date format requested in get_feed_build_date.
			 */
			return apply_filters( 'get_feed_build_date', $max_modified_time, $format );
	}
}

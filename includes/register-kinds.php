<?php
/**
 * Registers the built-in kinds.
 *
 * The arguments each kind takes are documented in
 * Kind_Taxonomy::register_post_kind().
 *
 * @package Post_Kinds
 * @since   3.3.4
 */

defined( 'ABSPATH' ) || exit;

register_post_kind(
	'article',
	array(
		'singular_name'   => __( 'Article', 'indieweb-post-kinds' ),
		'name'            => __( 'Articles', 'indieweb-post-kinds' ),
		'verb'            => ' ',
		'property'        => '',
		'properties'      => array(),
		'format'          => '',
		'description'     => __( 'traditional long form content: a post with an explicit title and body', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/article',
		'shortlink'       => 'b',
		'title'           => true,
		'show'            => true,
	)
);

register_post_kind(
	'note',
	array(
		'singular_name'   => __( 'Note', 'indieweb-post-kinds' ),
		'name'            => __( 'Notes', 'indieweb-post-kinds' ),
		'verb'            => ' ',
		'property'        => '',
		'properties'      => array(),
		'format'          => 'aside',
		'description'     => __( 'short content: a post or status update with just plain content and typically without a title', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/note',
		'shortlink'       => 't',
		'title'           => false,
		'show'            => false,
	)
);

register_post_kind(
	'reply',
	array(
		'singular_name'   => __( 'Reply', 'indieweb-post-kinds' ),
		'name'            => __( 'Replies', 'indieweb-post-kinds' ),
		'verb'            => __( 'Replied to', 'indieweb-post-kinds' ),
		'property'        => 'in-reply-to',
		'properties'      => array(
			'in-reply-to' => array(
				'type'       => 'cite',
				'label'      => __( 'In Reply To', 'indieweb-post-kinds' ),
				'properties' => array(
					'name'        => array(
						'type'  => 'text',
						'label' => __( 'Name', 'indieweb-post-kinds' ),
					),
					'url'         => array(
						'type'  => 'url',
						'label' => __( 'URL', 'indieweb-post-kinds' ),
					),
					'author'      => array(
						'type'  => 'author',
						'label' => __(
							'Author',
							'indieweb-post-kinds'
						),
					),
					'summary'     => array(
						'type'  => 'textarea',
						'label' => __( 'Summary', 'indieweb-post-kinds' ),
					),
					'publication' => array(
						'type'  => 'text',
						'label' => __( 'Publication/Website', 'indieweb-post-kinds' ),
					),
					'published'   => array(
						'type'  => 'datetime',
						'label' => __( 'Publish Date', 'indieweb-post-kinds' ),
					),
					'updated'     => array(
						'type'  => 'datetime',
						'label' => __( 'Updated Time', 'indieweb-post-kinds' ),
					),
					'category'    => array(
						'type'  => 'list',
						'label' => __(
							'Tags',
							'indieweb-post-kinds'
						),
					),
					'featured'    => array(
						'type'  => 'url',
						'label' => __(
							'Featured Media',
							'indieweb-post-kinds'
						),
					),
				),
			),
		),
		'format'          => 'link',
		'description'     => __( 'a reply to content typically on another site', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/reply',
		'shortlink'       => 't',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'repost',
	array(
		'singular_name'   => __( 'Repost', 'indieweb-post-kinds' ),
		'name'            => __( 'Reposts', 'indieweb-post-kinds' ),
		'verb'            => __( 'Reposted', 'indieweb-post-kinds' ),
		'property'        => 'repost-of',
		'properties'      => array(
			'repost-of' => array(
				'type'       => 'cite',
				'label'      => __( 'Repost', 'indieweb-post-kinds' ),
				'properties' => array(
					'name'        => array(
						'type'  => 'text',
						'label' => __( 'Name', 'indieweb-post-kinds' ),
					),
					'url'         => array(
						'type'  => 'url',
						'label' => __( 'URL', 'indieweb-post-kinds' ),
					),
					'author'      => array(
						'type'  => 'author',
						'label' => __(
							'Author',
							'indieweb-post-kinds'
						),
					),
					'summary'     => array(
						'type'  => 'textarea',
						'label' => __( 'Summary', 'indieweb-post-kinds' ),
					),
					'publication' => array(
						'type'  => 'text',
						'label' => __( 'Publication/Website', 'indieweb-post-kinds' ),
					),
					'published'   => array(
						'type'  => 'datetime',
						'label' => __( 'Publish Date', 'indieweb-post-kinds' ),
					),
					'updated'     => array(
						'type'  => 'datetime',
						'label' => __( 'Updated Time', 'indieweb-post-kinds' ),
					),
					'category'    => array(
						'type'  => 'list',
						'label' => __(
							'Tags',
							'indieweb-post-kinds'
						),
					),
					'featured'    => array(
						'type'  => 'url',
						'label' => __(
							'Featured Media',
							'indieweb-post-kinds'
						),
					),
				),
			),
		),
		'format'          => '',
		'description'     => __( 'a complete reposting of content from another site', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/repost',
		'shortlink'       => 'b',
		'title'           => true,
		'show'            => true,
	)
);

register_post_kind(
	'like',
	array(
		'singular_name'   => __( 'Like', 'indieweb-post-kinds' ),
		'name'            => __( 'Likes', 'indieweb-post-kinds' ),
		'verb'            => __( 'Liked', 'indieweb-post-kinds' ),
		'property'        => 'like-of',
		'properties'      => array(
			'like-of' => array(
				'type'       => 'cite',
				'label'      => __( 'Like', 'indieweb-post-kinds' ),
				'properties' => array(
					'name'        => array(
						'type'  => 'text',
						'label' => __( 'Name', 'indieweb-post-kinds' ),
					),
					'url'         => array(
						'type'  => 'url',
						'label' => __( 'URL', 'indieweb-post-kinds' ),
					),
					'author'      => array(
						'type'  => 'author',
						'label' => __(
							'Author',
							'indieweb-post-kinds'
						),
					),
					'summary'     => array(
						'type'  => 'textarea',
						'label' => __( 'Summary', 'indieweb-post-kinds' ),
					),
					'publication' => array(
						'type'  => 'text',
						'label' => __( 'Publication/Website', 'indieweb-post-kinds' ),
					),
					'published'   => array(
						'type'  => 'datetime',
						'label' => __( 'Publish Date', 'indieweb-post-kinds' ),
					),
					'updated'     => array(
						'type'  => 'datetime',
						'label' => __( 'Updated Time', 'indieweb-post-kinds' ),
					),
					'category'    => array(
						'type'  => 'list',
						'label' => __(
							'Tags',
							'indieweb-post-kinds'
						),
					),
					'featured'    => array(
						'type'  => 'url',
						'label' => __(
							'Featured Media',
							'indieweb-post-kinds'
						),
					),
				),
			),
		),
		'format'          => 'link',
		'description'     => __( 'a way to pay compliments to the original post/poster of external content', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/like',
		'shortlink'       => 'f',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'favorite',
	array(
		'singular_name'   => __( 'Favorite', 'indieweb-post-kinds' ),
		'name'            => __( 'Favorites', 'indieweb-post-kinds' ),
		'verb'            => __( 'Favorited', 'indieweb-post-kinds' ),
		'property'        => 'favorite-of',
		'properties'      => array(
			'favorite-of' => array(
				'type'       => 'cite',
				'label'      => __( 'Favorited', 'indieweb-post-kinds' ),
				'properties' => array(
					'name'        => array(
						'type'  => 'text',
						'label' => __( 'Name', 'indieweb-post-kinds' ),
					),
					'url'         => array(
						'type'  => 'url',
						'label' => __( 'URL', 'indieweb-post-kinds' ),
					),
					'author'      => array(
						'type'  => 'author',
						'label' => __(
							'Author',
							'indieweb-post-kinds'
						),
					),
					'summary'     => array(
						'type'  => 'textarea',
						'label' => __( 'Summary', 'indieweb-post-kinds' ),
					),
					'publication' => array(
						'type'  => 'text',
						'label' => __( 'Publication/Website', 'indieweb-post-kinds' ),
					),
					'published'   => array(
						'type'  => 'datetime',
						'label' => __( 'Publish Date', 'indieweb-post-kinds' ),
					),
					'updated'     => array(
						'type'  => 'datetime',
						'label' => __( 'Updated Time', 'indieweb-post-kinds' ),
					),
					'category'    => array(
						'type'  => 'list',
						'label' => __(
							'Tags',
							'indieweb-post-kinds'
						),
					),
					'featured'    => array(
						'type'  => 'url',
						'label' => __(
							'Featured Media',
							'indieweb-post-kinds'
						),
					),
				),
			),
		),
		'format'          => 'link',
		'description'     => __( 'special to the author', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/favorite',
		'shortlink'       => 'f',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'bookmark',
	array(
		'singular_name'   => __( 'Bookmark', 'indieweb-post-kinds' ),
		'name'            => __( 'Bookmarks', 'indieweb-post-kinds' ),
		'verb'            => __( 'Bookmarked', 'indieweb-post-kinds' ),
		'property'        => 'bookmark-of',
		'properties'      => array(
			'bookmark-of' => array(
				'type'       => 'cite',
				'label'      => __( 'Bookmark', 'indieweb-post-kinds' ),
				'properties' => array(
					'name'        => array(
						'type'  => 'text',
						'label' => __( 'Name', 'indieweb-post-kinds' ),
					),
					'url'         => array(
						'type'  => 'url',
						'label' => __( 'URL', 'indieweb-post-kinds' ),
					),
					'author'      => array(
						'type'  => 'author',
						'label' => __(
							'Author',
							'indieweb-post-kinds'
						),
					),
					'summary'     => array(
						'type'  => 'textarea',
						'label' => __( 'Summary', 'indieweb-post-kinds' ),
					),
					'publication' => array(
						'type'  => 'text',
						'label' => __( 'Publication/Website', 'indieweb-post-kinds' ),
					),
					'published'   => array(
						'type'  => 'datetime',
						'label' => __( 'Publish Date', 'indieweb-post-kinds' ),
					),
					'updated'     => array(
						'type'  => 'datetime',
						'label' => __( 'Updated Time', 'indieweb-post-kinds' ),
					),
					'category'    => array(
						'type'  => 'list',
						'label' => __(
							'Tags',
							'indieweb-post-kinds'
						),
					),
					'featured'    => array(
						'type'  => 'url',
						'label' => __(
							'Featured Media',
							'indieweb-post-kinds'
						),
					),
				),
			),
		),
		'format'          => 'link',
		'description'     => __( 'storing a link/bookmark for personal use or sharing with others', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/bookmark',
		'shortlink'       => 'h',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'photo',
	array(
		'singular_name'   => __( 'Photo', 'indieweb-post-kinds' ),
		'name'            => __( 'Photos', 'indieweb-post-kinds' ),
		'verb'            => ' ',
		'property'        => 'photo',
		'format'          => 'image',
		'description'     => __( 'a post with an embedded image/photo as its primary focus', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/photo',
		'shortlink'       => 'p',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'video',
	array(
		'singular_name'   => __( 'Video', 'indieweb-post-kinds' ),
		'name'            => __( 'Videos', 'indieweb-post-kinds' ),
		'verb'            => ' ',
		'property'        => 'video',
		'format'          => 'video',
		'description'     => __( 'a post with an embedded video as its primary focus', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/video',
		'shortlink'       => 'a',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'audio',
	array(
		'singular_name'   => __( 'Audio', 'indieweb-post-kinds' ),
		'name'            => __( 'Audios', 'indieweb-post-kinds' ),
		'verb'            => ' ',
		'property'        => 'audio',
		'format'          => 'audio',
		'description'     => __( 'a post with an embedded audio file as its primary focus', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/audio',
		'shortlink'       => 'a',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'tag',
	array(
		'singular_name'   => __( 'Tag', 'indieweb-post-kinds' ),
		'name'            => __( 'Tags', 'indieweb-post-kinds' ),
		'verb'            => __( 'Tagged', 'indieweb-post-kinds' ),
		'property'        => 'tag-of',
		'format'          => 'link',
		'description'     => __( 'allows you to tag a post as being of a specific category or tag, or for person tagging', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/tag',
		'shortlink'       => 'd',
		'title'           => false,
		'show'            => false,
	)
);

register_post_kind(
	'rsvp',
	array(
		'singular_name'   => __( 'RSVP', 'indieweb-post-kinds' ),
		'name'            => __( 'RSVPs', 'indieweb-post-kinds' ),
		'verb'            => __( 'RSVPed', 'indieweb-post-kinds' ),
		'property'        => 'in-reply-to',
		'properties'      => array(
			'rsvp'        => array(
				'type'    => 'select',
				'options' => array(
					'yes'        => __( 'Yes', 'indieweb-post-kinds' ),
					'no'         => __( 'No', 'indieweb-post-kinds' ),
					'maybe'      => __( 'Maybe', 'indieweb-post-kinds' ),
					'interested' => __( 'Interested', 'indieweb-post-kinds' ),
					'remote'     => __( 'Remote', 'indieweb-post-kinds' ),
				),
			),
			'in-reply-to' => array(
				'type'       => 'cite',
				'label'      => __( 'Event Details', 'indieweb-post-kinds' ),
				'properties' => array(
					'name'        => array(
						'type'  => 'text',
						'label' => __( 'Name', 'indieweb-post-kinds' ),
					),
					'url'         => array(
						'type'  => 'url',
						'label' => __( 'URL', 'indieweb-post-kinds' ),
					),
					'summary'     => array(
						'type'  => 'textarea',
						'label' => __( 'Summary', 'indieweb-post-kinds' ),
					),
					'publication' => array(
						'type'  => 'text',
						'label' => __( 'Publication/Website', 'indieweb-post-kinds' ),
					),
					'start'       => array(
						'type'  => 'datetime',
						'label' => __( 'Start Date', 'indieweb-post-kinds' ),
					),
					'end'         => array(
						'type'  => 'datetime',
						'label' => __( 'End Date', 'indieweb-post-kinds' ),
					),
					'category'    => array(
						'type'  => 'list',
						'label' => __(
							'Tags',
							'indieweb-post-kinds'
						),
					),
					'featured'    => array(
						'type'  => 'url',
						'label' => __(
							'Featured Media',
							'indieweb-post-kinds'
						),
					),
				),
			),
		),
		'format'          => 'link',
		'description'     => __( 'a specific type of reply regarding attendance of an event', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/rsvp',
		'shortlink'       => 't',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'listen',
	array(
		'singular_name'   => __( 'Listen', 'indieweb-post-kinds' ),
		'name'            => __( 'Listens', 'indieweb-post-kinds' ),
		'verb'            => __( 'Listened', 'indieweb-post-kinds' ),
		'property'        => 'listen-of',
		'properties'      => array(
			'listen-of' => array(
				'type'       => 'cite',
				'label'      => __( 'Listen To', 'indieweb-post-kinds' ),
				'properties' => array(
					'name'        => array(
						'type'  => 'text',
						'label' => __( 'Name', 'indieweb-post-kinds' ),
					),
					'url'         => array(
						'type'  => 'url',
						'label' => __( 'URL', 'indieweb-post-kinds' ),
					),
					'author'      => array(
						'type'  => 'author',
						'label' => __(
							'Artist',
							'indieweb-post-kinds'
						),
					),
					'summary'     => array(
						'type'  => 'textarea',
						'label' => __( 'Summary', 'indieweb-post-kinds' ),
					),
					'publication' => array(
						'type'  => 'text',
						'label' => __( 'Album', 'indieweb-post-kinds' ),
					),
					'published'   => array(
						'type'  => 'datetime',
						'label' => __( 'Release Date', 'indieweb-post-kinds' ),
					),
					'category'    => array(
						'type'  => 'list',
						'label' => __(
							'Tags',
							'indieweb-post-kinds'
						),
					),
					'featured'    => array(
						'type'  => 'url',
						'label' => __(
							'Album Art',
							'indieweb-post-kinds'
						),
					),
				),
			),
		),
		'format'          => 'audio',
		'description'     => __( 'listening to audio; sometimes called a scrobble', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/listen',
		'shortlink'       => 'x',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'watch',
	array(
		'singular_name'   => __( 'Watch', 'indieweb-post-kinds' ),
		'name'            => __( 'Watches', 'indieweb-post-kinds' ),
		'verb'            => __( 'Watched', 'indieweb-post-kinds' ),
		'property'        => 'watch-of',
		'properties'      => array(
			'watch-of' => array(
				'type'       => 'cite',
				'label'      => __( 'Watching To', 'indieweb-post-kinds' ),
				'properties' => array(
					'name'        => array(
						'type'  => 'text',
						'label' => __( 'Name', 'indieweb-post-kinds' ),
					),
					'url'         => array(
						'type'  => 'url',
						'label' => __( 'URL', 'indieweb-post-kinds' ),
					),
					'author'      => array(
						'type'  => 'author',
						'label' => __(
							'Artist',
							'indieweb-post-kinds'
						),
					),
					'summary'     => array(
						'type'  => 'textarea',
						'label' => __( 'Summary', 'indieweb-post-kinds' ),
					),
					'publication' => array(
						'type'  => 'text',
						'label' => __( 'Series', 'indieweb-post-kinds' ),
					),
					'published'   => array(
						'type'  => 'datetime',
						'label' => __( 'Release Date', 'indieweb-post-kinds' ),
					),
					'category'    => array(
						'type'  => 'list',
						'label' => __(
							'Tags',
							'indieweb-post-kinds'
						),
					),
					'featured'    => array(
						'type'  => 'url',
						'label' => __(
							'Poster',
							'indieweb-post-kinds'
						),
					),
				),
			),
		),
		'format'          => 'video',
		'description'     => __( 'watching a movie, television show, online video, play or other visual-based event', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/watch',
		'shortlink'       => 'x',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'checkin',
	array(
		'singular_name'   => __( 'Checkin', 'indieweb-post-kinds' ),
		'name'            => __( 'Checkins', 'indieweb-post-kinds' ),
		'verb'            => __( 'Checked into', 'indieweb-post-kinds' ),
		'property'        => 'checkin',
		'properties'      => array(
			'checkin' => array(
				'type'       => 'venue',
				'label'      => __( 'Checked In', 'indieweb-post-kinds' ),
				'properties' => array(
					'name'           => array(
						'type'  => 'name',
						'label' => __( 'Name', 'indieweb-post-kinds' ),
					),
					'url'            => array(
						'type'  => 'url',
						'label' => __( 'URL', 'indieweb-post-kinds' ),
					),
					'latitude'       => array(
						'type'  => 'number',
						'label' => __(
							'Latitude',
							'indieweb-post-kinds'
						),
						'step'  => 0.0000001,
					),
					'longitude'      => array(
						'type'  => 'number',
						'label' => __(
							'Longitude',
							'indieweb-post-kinds'
						),
						'step'  => 0.0000001,
					),
					'street-address' => array(
						'type'  => 'text',
						'label' => __( 'Street Address', 'indieweb-post-kinds' ),
					),
					'locality'       => array(
						'type'  => 'text',
						'label' => __( 'Locality', 'indieweb-post-kinds' ),
					),
					'region'         => array(
						'type'  => 'text',
						'label' => __( 'Region', 'indieweb-post-kinds' ),
					),
					'country-name'   => array(
						'type'  => 'text',
						'label' => __( 'Country Name', 'indieweb-post-kinds' ),
					),
					'postal-code'    => array(
						'type'  => 'text',
						'label' => __( 'Postal Code', 'indieweb-post-kinds' ),
					),
				),
			),
		),
		'format'          => 'status',
		'description'     => __( 'identifying you are at a particular geographic location', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/checkin',
		'shortlink'       => 'g',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'wish',
	array(
		'singular_name'   => __( 'Wish', 'indieweb-post-kinds' ),
		'name'            => __( 'Wishes', 'indieweb-post-kinds' ),
		'verb'            => __( 'Wished', 'indieweb-post-kinds' ),
		'property'        => 'wish-of',
		'format'          => 'link',
		'description'     => __( 'a post indicating a desire/wish. The archive of which would be a wishlist, such as a gift registry or similar', 'indieweb-post-kinds' ),
		'description_url' => '',
		'shortlink'       => 'f',
		'title'           => false,
		'show'            => false,
	)
);

register_post_kind(
	'play',
	array(
		'singular_name'   => __( 'Play', 'indieweb-post-kinds' ),
		'name'            => __( 'Playing', 'indieweb-post-kinds' ),
		'verb'            => __( 'Played', 'indieweb-post-kinds' ),
		'property'        => 'play-of',
		'format'          => 'status',
		'description'     => __( 'playing a game', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/game_play',
		'shortlink'       => 'x',
		'title'           => false,
		'show'            => true,
	)
);

register_post_kind(
	'weather',
	array(
		'singular_name'   => __( 'Weather', 'indieweb-post-kinds' ),
		'name'            => __( 'Weather', 'indieweb-post-kinds' ),
		'verb'            => ' ',
		'property'        => 'weather',
		'format'          => 'status',
		'description'     => __( 'current weather conditions', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/weather',
		'shortlink'       => 'u',
		'title'           => false,
		'show'            => false,
	)
);

register_post_kind(
	'exercise',
	array(
		'singular_name'   => __( 'Exercise', 'indieweb-post-kinds' ),
		'name'            => __( 'Exercise', 'indieweb-post-kinds' ),
		'verb'            => __( 'Exercised', 'indieweb-post-kinds' ),
		'property'        => 'exercise',
		'format'          => 'status',
		'description'     => __( 'some form of physical activity or workout (examples: walk, run, cycle, hike, yoga, etc.)', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/exercise',
		'shortlink'       => 'm',
		'title'           => false,
		'show'            => false,
	)
);

register_post_kind(
	'trip',
	array(
		'singular_name'   => __( 'Trip', 'indieweb-post-kinds' ),
		'name'            => __( 'Trips', 'indieweb-post-kinds' ),
		'verb'            => __( 'Travelled', 'indieweb-post-kinds' ),
		'property'        => 'trip',
		'format'          => '',
		'description'     => __( 'represents a geographic journey', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/trip',
		'shortlink'       => 'e',
		'title'           => false,
		'show'            => false,
	)
);

register_post_kind(
	'itinerary',
	array(
		'singular_name'   => __( 'Itinerary', 'indieweb-post-kinds' ),
		'name'            => __( 'Itineraries', 'indieweb-post-kinds' ),
		'verb'            => __( 'Travelled', 'indieweb-post-kinds' ),
		'property'        => 'itinerary',
		'format'          => '',
		'description'     => __( 'parts of a scheduled trip including transit by car, plane, train, etc.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/trip',
		'shortlink'       => 'e',
		'title'           => false,
		'show'            => false,
	)
);


register_post_kind(
	'eat',
	array(
		'singular_name'   => __( 'Eat', 'indieweb-post-kinds' ),
		'name'            => __( 'Eat', 'indieweb-post-kinds' ),
		'verb'            => __( 'Ate', 'indieweb-post-kinds' ),
		'property'        => 'ate',
		'format'          => 'status',
		'description'     => __( 'what you are eating, perhaps for a food diary', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/food',
		'shortlink'       => 'u',
		'title'           => false,
		'show'            => true,
	)
);


register_post_kind(
	'drink',
	array(
		'singular_name'   => __( 'Drink', 'indieweb-post-kinds' ),
		'name'            => __( 'Drinks', 'indieweb-post-kinds' ),
		'verb'            => __( 'Drank', 'indieweb-post-kinds' ),
		'property'        => 'drank',
		'format'          => 'status',
		'description'     => __( 'what you are drinking, perhaps for a food dairy', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/food',
		'shortlink'       => 'u',
		'title'           => false,
		'show'            => true,
	)
);


register_post_kind(
	'follow',
	array(
		'singular_name'   => __( 'Follow', 'indieweb-post-kinds' ),
		'name'            => __( 'Follows', 'indieweb-post-kinds' ),
		'verb'            => __( 'Followed', 'indieweb-post-kinds' ),
		'property'        => 'follow-of',
		'format'          => '',
		'description'     => __( 'indicating you are now following or subscribing to another person`s activities online', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/follow',
		'shortlink'       => 'f',
		'title'           => false,
		'show'            => false,
	)
);


register_post_kind(
	'jam',
	array(
		'singular_name'   => __( 'Jam', 'indieweb-post-kinds' ),
		'name'            => __( 'Jams', 'indieweb-post-kinds' ),
		'verb'            => __( 'Listened to', 'indieweb-post-kinds' ),
		'property'        => 'jam-of',
		'format'          => 'audio',
		'description'     => __( 'a particularly personally meaningful song (a listen with added emphasis)', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/jam',
		'shortlink'       => 'f',
		'title'           => false,
		'show'            => true,
	)
);


register_post_kind(
	'read',
	array(
		'singular_name'   => __( 'Read', 'indieweb-post-kinds' ),
		'name'            => __( 'Reads', 'indieweb-post-kinds' ),
		'verb'            => __( 'Read', 'indieweb-post-kinds' ),
		'property'        => 'read-of',
		'format'          => 'status',
		'description'     => __( 'reading a book, magazine, newspaper, other physical document, or online post', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/read',
		'shortlink'       => 'x',
		'title'           => false,
		'show'            => true,
	)
);


register_post_kind(
	'quote',
	array(
		'singular_name'   => __( 'Quote', 'indieweb-post-kinds' ),
		'name'            => __( 'Quotes', 'indieweb-post-kinds' ),
		'verb'            => __( 'Quoted', 'indieweb-post-kinds' ),
		'property'        => 'quotation-of',
		'format'          => 'quote',
		'description'     => __( 'quoted content', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/quote',
		'shortlink'       => 't',
		'title'           => false,
		'show'            => true,
	)
);


register_post_kind(
	'mood',
	array(
		'singular_name'   => __( 'Mood', 'indieweb-post-kinds' ),
		'name'            => __( 'Moods', 'indieweb-post-kinds' ),
		'verb'            => __( 'Felt', 'indieweb-post-kinds' ),
		'property'        => 'mood',
		'format'          => 'status',
		'description'     => __( 'how you are feeling (example: happy, sad, indifferent, etc.)', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/mood',
		'shortlink'       => 't',
		'title'           => false,
		'show'            => false,
	)
);


register_post_kind(
	'recipe',
	array(
		'singular_name'   => __( 'Recipe', 'indieweb-post-kinds' ),
		'name'            => __( 'Recipes', 'indieweb-post-kinds' ),
		'verb'            => __( 'Cooked', 'indieweb-post-kinds' ),
		'property'        => 'recipe',
		'format'          => '',
		'description'     => __( 'list of ingredients and directions for making food or drink', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/recipe',
		'shortlink'       => 'b',
		'title'           => true,
		'show'            => false,
	)
);


register_post_kind(
	'issue',
	array(
		'singular_name'   => __( 'Issue', 'indieweb-post-kinds' ),
		'name'            => __( 'Issues', 'indieweb-post-kinds' ),
		'verb'            => __( 'Filed an Issue', 'indieweb-post-kinds' ),
		'property'        => 'in-reply-to',
		'format'          => '',
		'description'     => __( 'Issue is a special kind of article post that is a reply to typically some source code, though potentially anything at a source control repository.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/issue',
		'shortlink'       => 't',
		'title'           => true,
		'show'            => true,
	)
);


register_post_kind(
	'question',
	array(
		'singular_name'   => __( 'Question', 'indieweb-post-kinds' ),
		'name'            => __( 'Questions', 'indieweb-post-kinds' ),
		'verb'            => __( 'Asked a question', 'indieweb-post-kinds' ),
		'property'        => 'question',
		'format'          => '',
		'description'     => __( 'Question is a post type for soliciting answer replies, which are then typically up/down voted by others and then displayed underneath the question post ordered by highest positive vote count rather than time ordered.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/question',
		'shortlink'       => 'q',
		'title'           => false,
		'show'            => false,
	)
);


register_post_kind(
	'sleep',
	array(
		'singular_name'   => __( 'Sleep', 'indieweb-post-kinds' ),
		'name'            => __( 'Sleeps', 'indieweb-post-kinds' ),
		'verb'            => __( 'Slept', 'indieweb-post-kinds' ),
		'property'        => 'sleep',
		'format'          => '',
		'description'     => __( 'Sleep is a passive metrics post type that indicates how much time (and often a graph of how deeply) a person has slept.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/sleep',
		'shortlink'       => 'm',
		'title'           => false,
		'show'            => false,
	)
);


register_post_kind(
	'event',
	array(
		'singular_name'   => __( 'Event', 'indieweb-post-kinds' ),
		'name'            => __( 'Events', 'indieweb-post-kinds' ),
		'verb'            => __( 'Planned', 'indieweb-post-kinds' ),
		'property'        => 'event',
		'format'          => '',
		'description'     => __( 'An event is a type of post that in addition to a post name (event title) has a start datetime (likely end datetime), and a location.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/event',
		'shortlink'       => 'e',
		'title'           => true,
		'show'            => false,
	)
);

register_post_kind(
	'review',
	array(
		'singular_name'   => __( 'Review', 'indieweb-post-kinds' ),
		'name'            => __( 'Reviews', 'indieweb-post-kinds' ),
		'verb'            => __( 'Reviewed', 'indieweb-post-kinds' ),
		'property'        => 'review-of',
		'format'          => '',
		'description'     => __( 'A review is a post evaluating a product or service, usually involving a written description, sometimes with summary numerical evaluations, also known as just a rating.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/review',
		'shortlink'       => 't',
		'title'           => false,
		'show'            => true,
	)
);


register_post_kind(
	'acquisition',
	array(
		'singular_name'   => __( 'Acquisition', 'indieweb-post-kinds' ),
		'name'            => __( 'Acquisitions', 'indieweb-post-kinds' ),
		'verb'            => __( 'Acquired', 'indieweb-post-kinds' ),
		'property'        => 'acquired-of',
		'format'          => 'status',
		'description'     => __( 'Purchases, gifts, found things, or objects donated', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/acquisition',
		'shortlink'       => 'f',
		'title'           => false,
		'show'            => false,
	)
);

register_post_kind(
	'craft',
	array(
		'singular_name'   => __( 'Craft', 'indieweb-post-kinds' ),
		'name'            => __( 'Crafts', 'indieweb-post-kinds' ),
		'verb'            => __( 'Crafted', 'indieweb-post-kinds' ),
		'property'        => 'craft-of',
		'format'          => 'status',
		'description'     => __( 'Activities like knitting, crocheting, cross stitch, wood working, restoration, 3d printing...the activity of building something.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/crafts',
		'shortlink'       => 'x',
		'title'           => false,
		'show'            => true,
	)
);

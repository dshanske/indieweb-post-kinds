<?php
/* Registers built-in Post Kinds
 */

register_post_kind(
	'article',
	array(
		'singular_name'   => __( 'Article', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Articles', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => ' ', // The string for the verb or action (liked this)
		'property'        => '', // microformats 2 property
		'properties'      => array(),
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'traditional long form content: a post with an explicit title and body', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/article',
		'shortlink'       => 'b',
		'title'           => true, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'note',
	array(
		'singular_name'   => __( 'Note', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Notes', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => ' ', // The string for the verb or action (liked this)
		'property'        => '', // microformats 2 property
		'properties'      => array(),
		'format'          => 'aside', // Post Format that maps to this
		'description'     => __( 'short content: a post or status update with just plain content and typically without a title', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/note',
		'shortlink'       => 't',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);

register_post_kind(
	'reply',
	array(
		'singular_name'   => __( 'Reply', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Replies', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Replied to', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'in-reply-to', // microformats 2 property
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
		'format'          => 'link', // Post Format that maps to this
		'description'     => __( 'a reply to content typically on another site', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/reply',
		'shortlink'       => 't',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'repost',
	array(
		'singular_name'   => __( 'Repost', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Reposts', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Reposted', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'repost-of', // microformats 2 property
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
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'a complete reposting of content from another site', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/repost',
		'shortlink'       => 'b',
		'title'           => true, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'like',
	array(
		'singular_name'   => __( 'Like', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Likes', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Liked', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'like-of', // microformats 2 property
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
		'format'          => 'link', // Post Format that maps to this
		'description'     => __( 'a way to pay compliments to the original post/poster of external content', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/like',
		'shortlink'       => 'f',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'favorite',
	array(
		'singular_name'   => __( 'Favorite', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Favorites', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Favorited', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'favorite-of', // microformats 2 property
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
		'format'          => 'link', // Post Format that maps to this
		'description'     => __( 'special to the author', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/favorite',
		'shortlink'       => 'f',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'bookmark',
	array(
		'singular_name'   => __( 'Bookmark', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Bookmarks', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Bookmarked', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'bookmark-of', // microformats 2 property
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
		'format'          => 'link', // Post Format that maps to this
		'description'     => __( 'storing a link/bookmark for personal use or sharing with others', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/bookmark',
		'shortlink'       => 'h',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'photo',
	array(
		'singular_name'   => __( 'Photo', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Photos', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => ' ', // The string for the verb or action (liked this)
		'property'        => 'photo', // microformats 2 property
		'format'          => 'image', // Post Format that maps to this
		'description'     => __( 'a post with an embedded image/photo as its primary focus', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/photo',
		'shortlink'       => 'p',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'video',
	array(
		'singular_name'   => __( 'Video', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Videos', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => ' ', // The string for the verb or action (liked this)
		'property'        => 'video', // microformats 2 property
		'format'          => 'video', // Post Format that maps to this
		'description'     => __( 'a post with an embedded video as its primary focus', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/video',
		'shortlink'       => 'a',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'audio',
	array(
		'singular_name'   => __( 'Audio', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Audios', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => ' ', // The string for the verb or action (liked this)
		'property'        => 'audio', // microformats 2 property
		'format'          => 'audio', // Post Format that maps to this
		'description'     => __( 'a post with an embedded audio file as its primary focus', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/audio',
		'shortlink'       => 'a',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'tag',
	array(
		'singular_name'   => __( 'Tag', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Tags', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Tagged', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'tag-of', // microformats 2 property
		'format'          => 'link', // Post Format that maps to this
		'description'     => __( 'allows you to tag a post as being of a specific category or tag, or for person tagging', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/tag',
		'shortlink'       => 'd',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);

register_post_kind(
	'rsvp',
	array(
		'singular_name'   => __( 'RSVP', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'RSVPs', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'RSVPed', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'in-reply-to', // microformats 2 property
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
		'format'          => 'link', // Post Format that maps to this
		'description'     => __( 'a specific type of reply regarding attendance of an event', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/rsvp',
		'shortlink'       => 't',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'listen',
	array(
		'singular_name'   => __( 'Listen', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Listens', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Listened', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'listen-of', // microformats 2 property
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
		'format'          => 'audio', // Post Format that maps to this
		'description'     => __( 'listening to audio; sometimes called a scrobble', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/listen',
		'shortlink'       => 'x',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'watch',
	array(
		'singular_name'   => __( 'Watch', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Watches', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Watched', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'watch-of', // microformats 2 property
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
		'format'          => 'video', // Post Format that maps to this
		'description'     => __( 'watching a movie, television show, online video, play or other visual-based event', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/watch',
		'shortlink'       => 'x',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'checkin',
	array(
		'singular_name'   => __( 'Checkin', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Checkins', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Checked into', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'checkin', // microformats 2 property
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
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'identifying you are at a particular geographic location', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/checkin',
		'shortlink'       => 'g',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'wish',
	array(
		'singular_name'   => __( 'Wish', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Wishes', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Wished', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'wish-of', // microformats 2 property
		'format'          => 'link', // Post Format that maps to this
		'description'     => __( 'a post indicating a desire/wish. The archive of which would be a wishlist, such as a gift registry or similar', 'indieweb-post-kinds' ),
		'description_url' => '',
		'shortlink'       => 'f',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);

register_post_kind(
	'play',
	array(
		'singular_name'   => __( 'Play', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Playing', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Played', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'play-of', // microformats 2 property
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'playing a game', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/game_play',
		'shortlink'       => 'x',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

register_post_kind(
	'weather',
	array(
		'singular_name'   => __( 'Weather', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Weather', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => ' ', // The string for the verb or action (liked this)
		'property'        => 'weather', // microformats 2 property
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'current weather conditions', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/weather',
		'shortlink'       => 'u',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);

register_post_kind(
	'exercise',
	array(
		'singular_name'   => __( 'Exercise', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Exercise', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Exercised', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'exercise', // microformats 2 property
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'some form of physical activity or workout (examples: walk, run, cycle, hike, yoga, etc.)', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/exercise',
		'shortlink'       => 'm',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);

register_post_kind(
	'trip',
	array(
		'singular_name'   => __( 'Trip', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Trips', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Travelled', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'trip', // microformats 2 property
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'represents a geographic journey', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/trip',
		'shortlink'       => 'e',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);

register_post_kind(
	'itinerary',
	array(
		'singular_name'   => __( 'Itinerary', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Itineraries', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Travelled', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'itinerary', // microformats 2 property
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'parts of a scheduled trip including transit by car, plane, train, etc.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/trip',
		'shortlink'       => 'e',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);


register_post_kind(
	'eat',
	array(
		'singular_name'   => __( 'Eat', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Eat', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Ate', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'ate', // microformats 2 property
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'what you are eating, perhaps for a food diary', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/food',
		'shortlink'       => 'u',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);


register_post_kind(
	'drink',
	array(
		'singular_name'   => __( 'Drink', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Drinks', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Drank', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'drank', // microformats 2 property
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'what you are drinking, perhaps for a food dairy', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/food',
		'shortlink'       => 'u',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);


register_post_kind(
	'follow',
	array(
		'singular_name'   => __( 'Follow', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Follows', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Followed', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'follow-of', // microformats 2 property
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'indicating you are now following or subscribing to another person`s activities online', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/follow',
		'shortlink'       => 'f',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);


register_post_kind(
	'jam',
	array(
		'singular_name'   => __( 'Jam', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Jams', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Listened to', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'jam-of', // microformats 2 property
		'format'          => 'audio', // Post Format that maps to this
		'description'     => __( 'a particularly personally meaningful song (a listen with added emphasis)', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/jam',
		'shortlink'       => 'f',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);


register_post_kind(
	'read',
	array(
		'singular_name'   => __( 'Read', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Reads', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Read', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'read-of', // microformats 2 property
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'reading a book, magazine, newspaper, other physical document, or online post', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/read',
		'shortlink'       => 'x',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);


register_post_kind(
	'quote',
	array(
		'singular_name'   => __( 'Quote', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Quotes', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Quoted', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'quotation-of', // microformats 2 property
		'format'          => 'quote', // Post Format that maps to this
		'description'     => __( 'quoted content', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/quote',
		'shortlink'       => 't',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);


register_post_kind(
	'mood',
	array(
		'singular_name'   => __( 'Mood', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Moods', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Felt', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'mood', // microformats 2 property
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'how you are feeling (example: happy, sad, indifferent, etc.)', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/mood',
		'shortlink'       => 't',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);


register_post_kind(
	'recipe',
	array(
		'singular_name'   => __( 'Recipe', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Recipes', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Cooked', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'recipe', // microformats 2 property
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'list of ingredients and directions for making food or drink', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/recipe',
		'shortlink'       => 'b',
		'title'           => true, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);


register_post_kind(
	'issue',
	array(
		'singular_name'   => __( 'Issue', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Issues', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Filed an Issue', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'in-reply-to', // microformats 2 property
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'Issue is a special kind of article post that is a reply to typically some source code, though potentially anything at a source control repository.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/issue',
		'shortlink'       => 't',
		'title'           => true, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);


register_post_kind(
	'question',
	array(
		'singular_name'   => __( 'Question', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Questions', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Asked a question', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'question', // microformats 2 property
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'Question is a post type for soliciting answer replies, which are then typically up/down voted by others and then displayed underneath the question post ordered by highest positive vote count rather than time ordered.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/question',
		'shortlink'       => 'q',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);


register_post_kind(
	'sleep',
	array(
		'singular_name'   => __( 'Sleep', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Sleeps', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Slept', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'sleep', // microformats 2 property
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'Sleep is a passive metrics post type that indicates how much time (and often a graph of how deeply) a person has slept.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/sleep',
		'shortlink'       => 'm',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);


register_post_kind(
	'event',
	array(
		'singular_name'   => __( 'Event', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Events', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Planned', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'event', // microformats 2 property
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'An event is a type of post that in addition to a post name (event title) has a start datetime (likely end datetime), and a location.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/event',
		'shortlink'       => 'e',
		'title'           => true, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);

register_post_kind(
	'review',
	array(
		'singular_name'   => __( 'Review', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Reviews', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Reviewed', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'review-of', // microformats 2 property
		'format'          => '', // Post Format that maps to this
		'description'     => __( 'A review is a post evaluating a product or service, usually involving a written description, sometimes with summary numerical evaluations, also known as just a rating.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/review',
		'shortlink'       => 't',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);


register_post_kind(
	'acquisition',
	array(
		'singular_name'   => __( 'Acquisition', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Acquisitions', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Acquired', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'acquired-of', // microformats 2 property
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'Purchases, gifts, found things, or objects donated', 'indieweb-post-kinds' ),
		'description_url' => 'http://indieweb.org/acquisition',
		'shortlink'       => 'f',
		'title'           => false, // Should this kind have an explicit title
		'show'            => false, // Show in Settings
	)
);

register_post_kind(
	'craft',
	array(
		'singular_name'   => __( 'Craft', 'indieweb-post-kinds' ), // Name for one instance of the kind
		'name'            => __( 'Crafts', 'indieweb-post-kinds' ), // General name for the kind plural
		'verb'            => __( 'Crafted', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
		'property'        => 'craft-of', // microformats 2 property
		'format'          => 'status', // Post Format that maps to this
		'description'     => __( 'Activities like knitting, crocheting, cross stitch, wood working, restoration, 3d printing...the activity of building something.', 'indieweb-post-kinds' ),
		'description_url' => 'https://indieweb.org/crafts',
		'shortlink'       => 'x',
		'title'           => false, // Should this kind have an explicit title
		'show'            => true, // Show in Settings
	)
);

=== Indieweb Post Kinds ===
Contributors: dshanske
Tags: indieweb
Stable tag: 1.1.0
Requires at least: 4.0
Tested up to: 4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Post Kinds adds support for responding to and interacting with other sites using the standards developed by IndieWebCamp

== Description == 

Post Kinds implements [kinds of posts](http://indiewebcamp.com/post_kinds).

Post Kinds makes it clear when your post is a reply or a like of someone else's post. It can also distinguish notes, articles and photos in a manner similar to [post formats](http://codex.wordpress.org/Post_Formats).

== Screenshots ==

1. Here is an example of what a like looks like
2. Here is a reply to a Youtube video

== Frequently Asked Questions ==

= What is a Post Kind? = 

There are many different kinds of posts. 

This plugin allows posts to be classified as replies, likes, etc. and to mark them up appropriately with microformats.

= How does it work? = 

1. Bob wants to reply to Sue on his own website.
2. Sue enables webmentions on her site.
3. Bob creates a post and sets it as a reply to Sue's post.
4. A webmention is sent to Sue's site, and stored as a comment on Sue's post.

= Why are Audio and Video are listed as both non-response and response kinds? =

If the response box is blank(no URL or title), audio and video are defined as 
the posting of an audio or video clip. If it is not, they become passive posts.

Audio with a response is Listening, so it can be used for scrobbling.
Video with a response is Watching, for same.

= How do I learn more? =

For more information on the Indieweb and tools for WordPress, visit [Getting Strted on WordPress](http://indiewebcamp.com/Getting_Started_on_WordPress).

== Installation ==

The plugin requires the [webmention](https://wordpress.org/plugins/webmention/) plugin to support sending/receiving notice of a reply/like to another site which will appear as a comment. The [Semantic Linkbacks](https://wordpress.org/plugins/semantic-linkbacks/) plugin is available to more richly display the comment.

== Post Kinds ==

To exclude kinds from the post selector, add the following to your wp-config.php file.

define('POST_KIND_EXCLUDE', 'note,article');

This will exclude the note and article from showing up in the post editor. It will not disable them on existing posts.

= The Non-Response Kinds =

These kinds have an analog in post formats. Adding context to one of these
may make it a Passive Kind.

 * **Article** - traditional long form content - a post with an explicit post name(title)
 * **Note** - short content - a post with just plain content
 * **Photo** - image or photo post - a post with an embedded image as its primary focus
 * **Audio** - audio post - a post with an embedded piece of audio content as its primary focus
 * **Video** - video post - a post with an embedded video as its primary focus  
 * **Check-In** - Identifying you are at a place. This would use the extended
WordPress Geodata. (Under Development)


= The Response Kinds =

 * **Reply** - Replying to someone else's comment
 * **Repost** - a complete repost of someone else's content
 * **Like** -  props/compliments to the original post/poster
 * **Favorite** - special to the favoriter
 * **Bookmark** - also known as a linkblog. This is basically sharing/storing a link/bookmark.
 * **Tag** - Allows you to tag a post as being of a specific tag, or person tagging.
 * **RSVP** - A specific type of Reply regarding an event (Unfinished)

= The Passive Kinds =

To "Scrobble" a song is when listening to it, you would make it a post on
your website. This is the most well-known example of a passive kind of post.

They are formed by having content in the context box on one of these types of
posts.

 * **Audio** - audio post(scrobble) - listening to an audio post 
 * **Video** - video post - watching a video
 * **Game** - playing a game

= Future Kinds =

With the recent expansion of kinds into passive types of posts, there may be
additional ones added. Feedback appreciated
* **Weather** - A weather post would be current weather conditions
* **Exercise** - Representing some form of physical activity

== WordPress GeoData ==

[WordPress Geodata](http://codex.wordpress.org/Geodata) is an existing standard
used to store geodata about a post.

It consists of four fields: latitude, longitude, public, and address. For 
Indieweb Compatibility, intention is to support a 5th parameter, venue.

Address would be a physical address. Venue would identify the location's
name. This is necessary to improve check-in display.

== Future Plans ==

* Automatic import/parsing of information based on click of button
* Contextual response box, hiding/changing options based on Kind selected. Example, an RSVP that shows Yes/No/Maybe.

== SNAP ==

Indieweb Post Kinds already has support for replying to Twitter posts using the Social Network Auto Poster plugin.

== Functions ==

`get_post_kind_slug($id)` - Return the kind slug for a given post. If `$id` is not specified, use current post.
`get_post_kind($id)` - Return the kind string for a given post. If `$id` is not specified, use current post.

`has_post_kind($kind, $post)` - Returns true/false if kind is in post. If post is empty, then use current post

`get_kind_context_class( $class, $classtype )` - Returns the CSS class to be applied to the response/context if the kind is one for which there is context. Classtype defaults to u, other option is usually p. Sets the class to the kind slug and for specially specified slugs, sets appropriate mf2 classes as well. $class specifies any additional classes to be added.



==  Filters == 

`get_the_kind` - Filter get_the_kinds

`the_kinds` - Filter get_the_kinds_list

`kind_classes` - Filter get_kind_class

`kind_verb` - Filter get_kind_verbs

`kind-response-display` - Filters the output being added to the_content or to custom location in theme


== Changelog == 
 * *Version 1.1.0* - Added new kinds - audio, video, check-in, game at the 
suggestion of acegiak. Adds support for passive kinds. Some code cleanup and 
commenting.
 * *Version 1.0.2* - Bug fixes
 * *Version 1.0.1* - Update Readme to better describe 
 * *Version 1.0*  - Now in the WordPress repository
 * *Version 0.3*  - Custom Code for Generating the Kind Select Box to Allow for Default Kind. Defined constant POST_KIND_EXCLUDE to hide kinds from the selector 
 * *Version 0.24* - Added Grunt/SASS support to more easily control changes and support internationalization
 * *Version 0.23* - Option to refresh the cache on each load added

 * *Version 0.22* - Complete response html cached to post-meta and purged on post update.

 * *Version 0.21* - Temporary fix for slow embed code in preparation for caching to reduce calls. Removed defaultterms check to only run on settings page save or plugin activation instead of on each load.

 * *Version 0.2* - Forked to Indieweb Post Kinds to reflect a change to act as an alternative to the post formats feature of WordPress. Removed multikind option. Prior to this, the plugin functions mirrored those of tags. With the removal of multikind support, each post can only have one kind, and the functions will more closely mirror the Post Format. Complete rewrite of the display functionality.

 * *Version 0.11* - Option to Update Metadata Deliberately Commented Out. Anyone who used the old plugin should backup their database before considering migration. The data should still be in the database regardless.

 * *Version 0.1* - Revised settings page to use WordPress Settings API. Alert: This version switches to using an array for storage of response data for future development. To migrate your data, please backup your database and then check "Migrate to new data structure on update" on the options page.

 * *Version 0.06* - Added in support for Favorite, fixed webmention support.

 * *Version 0.05* - Set up embed handler for commonly linked sites. Add options to turn on embeds for these sites. Plugin is probably now feature complete enough for test deployment to a live site.

 * *Version 0.04* - Add function to generate verbs(Like to Liked...) for a given kind. Add Display functionality in Beta. Include Genericons and Dashicons for icon options for the various kinds. Add plugin option to add the response URL to the top or bottom of the content section. Plugin near point at which can be deployed for basic use.

 * *Version 0.03* - Location function removed and split into separate plugin called Simple Location. Any check-in kind functionality will have this as a dependency.

 * *Version 0.02* - Location meta box with HTML5 geolocation fill-in added. This allows posts to optionally have a location. This is as per the Wordpress Geodata specifications, so the Wordpress Android app will fill them in. There is no display functionality. Various functions that mimic the built-in functions for other taxonomies were added, including filters to add additional behaviors. Default terms now prepopulate if no terms exist.

 * *Version 0.01* - Registers a custom taxonomy, adds in code snippets to turn the post meta box from checkboxes to radio buttons, adds code to allow a custom permalink tag if needed.

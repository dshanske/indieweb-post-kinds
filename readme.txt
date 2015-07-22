=== Indieweb Post Kinds ===
Contributors: dshanske
Tags: indieweb, interaction, posts, webmention
Stable tag: 2.0.2
Requires at least: 4.1
Tested up to: 4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?

== Description == 

Post Kinds adds support for responding to and interacting with other sites using the standards developed by IndieWebCamp by implementing
[kinds of posts](http://indiewebcamp.com/post_kinds).

It can also distinguish certain types of passive posts in a manner similar to [post formats](http://codex.wordpress.org/Post_Formats).

== Screenshots ==

1. Here is an example of what a like looks like
2. Here is a reply to a Youtube video

== Frequently Asked Questions ==

= How does it work? = 

1. Bob wants to reply to Sue on his own website.
2. Sue enables webmentions(separate plugin) on her site.
3. Bob creates a post and sets it as a reply to or a like of Sue's post.
4. A webmention is sent to Sue's site, and stored as a comment on Sue's post.

= How do I learn more? =

For more information on the Indieweb and tools for WordPress, visit [Getting Started on WordPress](http://indiewebcamp.com/Getting_Started_on_WordPress).

= How do I get support? = 

The Development version of the plugin is hosted at [Github](https://github.com/dshanske/indieweb-post-kinds).

== Installation ==

The plugin requires the [webmention](https://wordpress.org/plugins/webmention/) plugin to support sending/receiving notice of a reply/like to another site which will appear as a comment. The [Semantic Linkbacks](https://wordpress.org/plugins/semantic-linkbacks/) plugin is available to more richly display the comment.

To declare your theme supports post kinds(if it does):

add_theme_support('post-kinds');

If your theme does not declare support, then the context information will be added to the content 100% 
of the time. It is assumed if your theme declares support, then it declares the alternate location for 
presentation.

== Upgrade Notice ==

= 2.0.0 = 

This version makes some changes to the presentation. Backup your installation
first as a precaution. Lots of changes under the hood which will bear fruit in future.

= 1.2.0 =
This version migrates data to a new storage formats. Backup your installation
first as a precaution.

== Post Kinds ==

Many sites will not need all of the kinds set up. What kinds of posts you wish
to publish are specific to your needs.

By default, only note, article, photo and reply are enabled so you can get used to them.

You can add Media Check-Ins(watching or listening), or Link Sharing(likes, favorites, or bookmark) directly from the settings page.

You can enable/disable more based on preference. Some may be enabled by plugins.

Not having a post kind enabled will not disable the functionality on existing 
posts.

There is also a filter to do this on the fly.

= The Non-Response Kinds =

These kinds have an analog in post formats. Adding context to one of these
may make it a Passive Kind.

 * **Article** - traditional long form content - a post with an explicit post name(title)
 * **Note** - short content - a post with just plain content
 * **Photo** - image or photo post - a post with an embedded image as its primary focus

= The Response Kinds =

 * **Reply** - Replying to someone else's comment
 * **Repost** - a complete repost of someone else's content
 * **Like** -  props/compliments to the original post/poster
 * **Favorite** - special to the favoriter
 * **Bookmark** - also known as a linkblog. This is basically sharing/storing a link/bookmark.
 * **Tag** - Allows you to tag a post as being of a specific tag, or person tagging.
 * **RSVP** - A specific type of Reply regarding an event (Under Development)

= The Passive Kinds =

To "Scrobble" a song is when listening to it, you would make it a post on
your website. This is the most well-known example of a passive kind of post.

They are formed by having content in the context box on one of these types of
posts.

 * **Listen** - scrobble - listening to an audio post 
 * **Watch** - video - watching a video
 * **Play** - playing a game (Under Development)
 * **Wish** - a post indicating a desire/wish. The archive of which would be
	a wishlist, such as a gift registry or similar. (Under Development)
 * **Weather** - A weather post would be current weather conditions
 * **Exercise** - Representing some form of physical activity
 * **Travel** - Representing a trip
 * **Check-In** - Identifying you are at a place. This would use the extended WordPress Geodata.
   It will require the Simple Location plugin to add location awareness to posts.(Under Development)


= Future Kinds =

With the recent expansion of kinds into passive types of posts, there may be
additional ones added. Feedback appreciated.

== Future Plans ==

	* Contextual response box, hiding/changing options based on Kind selected. Example, an RSVP that shows Yes/No/Maybe.
	* Support Micropub plugin
	* Validate inputs based on type
	* As of 2.0, customizable displays by type are set to be supported in the code. Will be writing some


== MultiKind Support (Experimental) ==

By request, multikind support has returned as a hidden mode that is not complete.

By design, the plugin only allows one kind to be selected per post. Setting 
this flag in your wp-config would change that.

define('MULTIKIND', '1');

== SNAP ==

Indieweb Post Kinds already has support for replying to Twitter posts using the Social Network Auto Poster plugin.

== Changelog == 
	= Version 2.0.2 =
		* Change markup to use e-content instead of p-content
		* Add versioning for CSS
	= Version 2.0.1 =
		* Bugfixes
		* Accepted fix for webmention sending from acegiak
		* Add option to disable sideloading of author images
	= Version 2.0.0 =
		* Rewrite completed
		* Multi-author/multi-reply temporarily removed in storage, but it wasn't being used yet anyway
		* Customizable display objects now supported
		* Open Graph Protocol Parsing Added to Extract Title/Description/Image from URLs
		* Sideloads Author Picture
		* Add Duration Meta Field by request of Acegiak. This can be used for duration of activity...
			for example, watching/playing/listening/exercising/etc.
	= Version 1.4.1 =
		* Bug fixes for changes made in 1.4.0. 
	= Version 1.4.0 = 
		* Begin rewrite to class-scoped functions in line with WordPress Guidelines.
		* Part of a major refinement and tightening of code
	= Version 1.3.1  = 
		* Additional refinements to kind defaults and minor bugfixes
	= Version 1.3.0 =
		* Add functionality to better customize displays for different types.
		* Add default options on activation. 
		* Show/hide check-in if Simple Location plugin is enabled. 
		* Change exclude types to include types. 
		* Add filter for plugins to show/hide kinds in the selector.
		* Reduce default to a handful of kinds for new users. The plan going forward is to have some functionality enabled by secondary plugins
	= Version 1.2.3 = 
		* Fix bug introduced in 1.2.2 with URL storage. 
		* Add publication as a field option
	= Version 1.2.2 =
		* Add option to disable KSES protection on the content box(courtesy/request of acegiak). 
		* Minor cleanup
	= Version 1.2.1 = 
		* Add filter to support post kinds for Semantic Linkbacks plugin. 
		* Add responses to feed content. 
	= Version 1.2.0 = 
		* Change to store meta using [WordPress Data](https://indiewebcamp.com/WordPress_Data) proposal. 
		* Display functionality broken into individual pieces to make it easier to customize and edit
		* Multi-reply support in the data structure/display but not in entry
	= Version 1.1.1 =
		* Adds theme support and removes embed in content option
	= Version 1.1.0 = 
		* Added new kinds - listen, watch, check-in, play at the suggestion of acegiak. 
		* Adds support for passive kinds. 
		* Some code cleanup and commenting. 
		* Start of add_theme_support function. This will replace the setting to embed in content in a future version.
 	= Version 1.0.2 = 
		* Bug fixes
 	= Version 1.0.1 = 
		* Update Readme to better describe 
	= Version 1.0 =
		* Now in the WordPress repository
	= Version 0.3 = 
		* Custom Code for Generating the Kind Select Box to Allow for Default Kind. 
		* Defined constant POST_KIND_EXCLUDE to hide kinds from the selector 
 	= Version 0.24 = 
		* Added Grunt/SASS support to more easily control changes and support internationalization
	= Version 0.23 =
		* Option to refresh the cache on each load added
	= Version 0.22 = 
		* Complete response html cached to post-meta and purged on post update.
	= Version 0.21 = 
		* Temporary fix for slow embed code in preparation for caching to reduce calls. 
		* Removed defaultterms check to only run on settings page save or plugin activation instead of on each load.
	= Version 0.2 = 
		* Forked to Indieweb Post Kinds to reflect a change to act as an alternative to the post formats feature of WordPress. 
		* Removed multikind option. Prior to this, the plugin functions mirrored those of tags. With the removal of multikind support, each post can only have one kind, and the functions will more closely mirror the Post Format. 
		* Complete rewrite of the display functionality.
	= Version 0.11 =
		* Option to Update Metadata Deliberately Commented Out. 
		* Anyone who used the old plugin should backup their database before considering migration. The data should still be in the database regardless.
	= Version 0.1 = 
		* Revised settings page to use WordPress Settings API. Alert: This version switches to using an array for storage of response data for future development. To migrate your data, please backup your database and then check "Migrate to new data structure on update" on the options page.
	= Version 0.06 = 
		* Added in support for Favorite
		* fixed webmention support.
	= Version 0.05 = 
		* Set up embed handler for commonly linked sites. 
		* Add options to turn on embeds for these sites. 
		* Plugin is probably now feature complete enough for test deployment to a live site.
	= Version 0.04 =
		* Add function to generate verbs(Like to Liked...) for a given kind. 
		* Add Display functionality in Beta. 
		* Include Genericons and Dashicons for icon options for the various kinds. 
		* Add plugin option to add the response URL to the top or bottom of the content section.
		* Plugin near point at which can be deployed for basic use.
	= Version 0.03 = 
		* Location function removed and split into separate plugin called Simple Location. 
		* Any check-in kind functionality will have this as a dependency.
	= Version 0.02 = 
		* Location meta box with HTML5 geolocation fill-in added. This allows posts to optionally have a location. This is as per the Wordpress Geodata specifications, so the Wordpress Android app will fill them in. There is no display functionality. 
		* Various functions that mimic the built-in functions for other taxonomies were added, including filters to add additional behaviors. Default terms now prepopulate if no terms exist.
	= Version 0.01 = 
		* Registers a custom taxonomy
		* adds in code snippets to turn the post meta box from checkboxes to radio buttons
		* adds code to allow a custom permalink tag if needed.

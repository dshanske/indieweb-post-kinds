=== Post Kinds ===
Contributors: dshanske
Tags: indieweb, interaction, posts, webmention, share, like, scrobble
Stable tag: 3.2.6
Requires at least: 4.9.6
Requires PHP: 5.4
Tested up to: 5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?

== Description == 

Post Kinds adds support to the Classic Editor for responding to and interacting with other sites using the standards
developed by the IndieWeb by implementing [kinds of posts](http://indieweb.org/post_kinds). It is not compatible with Gutenberg.

It can also distinguish certain types of passive posts in a manner similar to [post formats](http://codex.wordpress.org/Post_Formats). While it can work alongside
post formats, it is recommended as a replacement as it functions in a similar manner.

Many sites will not need all of the kinds set up. What kinds of posts you wish to publish are specific to your needs. 


== Screenshots ==

1. Here is an example of what a like looks like
2. Here is a reply to a Youtube video

== Installation ==

The plugin requires the [webmention](https://wordpress.org/plugins/webmention/) plugin to support sending/receiving notice of a reply/like to another site which will appear as a comment. The [Semantic Linkbacks](https://wordpress.org/plugins/semantic-linkbacks/) plugin is available to more richly display the comment.

== Privacy and Data Storage Notice ==

Post Kinds stores extra data in a post indicating what you are responding to. This data is either hand-added or can be parsed from the source URL if provided. This means you have additional
responsibilities to responsibly use this data, and to remove information on request.

==  Credits ==

1. Kind Icons are currently provided by [Font Awesome](http://fontawesome.io) and are licensed as [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/). A copy of the license notice is bundled.
2. [Chris Aldrich](http://boffosocko.com) always receives a credit on my plugins due his regular feedback, input, and usage.

== Upgrade Notice ==

= 3.1.4 =
This version is compatible with 5.0 of WordPress but is not integrated with the Gutenberg editor and requires the Classic Editor to function correctly.

= 3.1.1 =
* Bugs were reported in 3.1.0 and this is a quick fix for the ones initially reported

= 3.1.0 =

* Custom post kind registration, previously done by filter, is now done by registration. This will cause issues who had been using the filter. Due to a particular
user who created many custom kinds, decided to create a better way to do this. 
* Posting capability via custom REST API endpoint has been removed due improved Micropub support
* Kind_Meta class deprecated as promiseda
* Media Data is no longer stored in the post instead being stored in the attachment
* This version introduces a lot of base changes. Before making additional ones will be releasing this version so that things can stabilize

= 3.0.0 = 

Major refactoring of the plugin. The interface has been completely changed to improve simplicity. Please submit all bugs to our Github page.

= 2.7.0 = 

Storage locations have now changed as part of the nested properties settings and things should migrate automatically but pleasse backup before upgrade.
Kind_Meta function is deprecated and will be removed in 2.8.0, so if you depend on this, please update to use MF2_Post

= 2.5.0 =

Settings have been completely changed and you will have to reset.

= 2.4.2 =

Due to changes in the Micropub plugin, multiple values for properties are now supported. 
Until this feature is supported in this plugin it will only look at the first value and ignore the others.

= 2.4.0 = 

I have changed the icons again, to go back to something closer to the original. If anyone wants to
contribute alternate icon sets for the project, willing to consider adding them. Also, coming up is
another change to how the plugin stores and works with data to continue to move toward something
closer to Microformats. Looking at a JF2 representation.

= 2.0.0 = 

This version makes some changes to the presentation. Backup your installation
first as a precaution. Lots of changes under the hood which will bear fruit in future.

= 1.2.0 =
This version migrates data to a new storage formats. Backup your installation
first as a precaution.

== Frequently Asked Questions ==

= How does it work? = 

Kinds, like Post Formats built into WordPress, allow you to specify that a post contains a certain type of content. It also, based
on you classifying it that way, displays it appropriately and marks it up accordingly so outside sites can identify it. Kinds are
either a response to something, such as a URL, or a more passive type of post where you are recording/logging something you did, for 
example, watched a movie. 

= How do I interact with other sites? =

This is added by webmention support.

1. Bob wants to reply to Sue on his own website.
2. Sue enables webmentions(separate plugin) on her site.
3. Bob creates a post and sets it as a reply to or a like of Sue's post.
4. A webmention is sent to Sue's site, and Sue's site uses the markup added by this plugin to determine what kind of post it is.
5. Sue's site stores and displays data from the post as a comment on Sue's post.

= How do I learn more? =

For more information on the Indieweb and tools for WordPress, visit [Getting Started on
WordPress](http://indieweb.org/Getting_Started_on_WordPress).

= There are too many Post Kinds =

You can enable/disable more based on preference. Some may be enabled by plugins. You do not have to use ones you don't want. Not having a post kind enabled 
will not disable the functionality on existing posts, it only hides the selection in adding new posts.

= What are the kinds of posts I can make? =

These kinds have an analog in post formats. Adding context to one of these may make it a Passive Kind.

 * **Article** - traditional long form content - a post with an explicit post name(title)
 * **Note** - short content - a post with just plain content (also the default)
 * **Photo** - image or photo post - a post with an embedded image as its primary focus. This uses either the featured image or attached images depending on theme.
 * **Video** - video post - a post with an embedded image as its primary focus.
 * **Audio** - audio post - a post with an embedded sound file as its primary focus.

The Response Kinds

 * **Reply** - Replying to someone else's comment
 * **Repost** - a complete repost of someone else's content
 * **Like** -  props/compliments to the original post/poster
 * **Favorite** - special to the favoriter
 * **Bookmark** - This is basically sharing/storing a link/bookmark. 
 * **Quote** - Quoted Content
 * **RSVP** - A specific type of Reply regarding an event
 * **Check-In** - Identifying you are at a place. There is currently only limited support for this.
 * **Issue** - Issue is a special kind of article post that is a reply to typically some source code, though potentially anything at a source control repository.


The Passive Kinds

To "Scrobble" a song is when listening to it, you would make it a post on
your website. This is the most well-known example of a passive kind of post. They are formed by having content in the context box on one of these types of
posts.

 * **Listen** - scrobble - listening to an audio post 
 * **Jam** - Indicating a particularly personally meaningful song
 * **Watch** - video - watching a video
 * **Play** - playing a game
 * **Read** - reading a book, compared to online material
 * **Eat** - Representing recording what you eat, perhaps for a food diary
 * **Drink** - Similar to Eat

= What kinds do you plan to add in the future? =

The following Kinds are reserved for future use but will not show up in the 
interface at this time.

 * **Wish** - a post indicating a desire/wish. The archive of which would be
  a wishlist, such as a gift registry or similar. 
 * **Weather** - A weather post would be current weather conditions
 * **Exercise** - Representing some form of physical activity
 * **Trip** - Representing a trip...this represents a geographic journey and would require location awareness.
 * **Itinerary** - Itinerary - this would refer to scheduled transit, plane, train, etc. and does not require location awareness
 * **Tag** - Allows you to tag a post as being of a specific tag, or person tagging.
 * **Follow** - A post indicating you are now following someone's activities
 * **Mood** - Mood - Feeling
 * **Recipe** - Recipe
 * **Event** - An event is a type of post that in addition to a post name (event title) has a start datetime (likely end datetime), and a location.
 * **Sleep** - Sleep is a passive metrics post type that indicates how much time (and often a graph of how deeply) a person has slept.
 * **Acquisition** - Purchased, Donated, or otherwise acquired an object
 * **Question** - Question is a post type for soliciting answer replies, which are then typically up/down voted by others and then displayed underneath the question post ordered by highest positive vote count rather than time ordered.

= Can I add my own kinds? = 

I would prefer if something is popular enough to merge it into the plugin. However if you are interested in creating your own there is functionality around it.

register_post_kind(
	'reply',
		array(
			'singular_name'   => __( 'Reply', 'indieweb-post-kinds' ), // Name for one instance of the kind
			'name'            => __( 'Replies', 'indieweb-post-kinds' ), // General name for the kind plural
		       	'verb'            => __( 'Replied to', 'indieweb-post-kinds' ), // The string for the verb or action (liked this)
			'property'        => 'in-reply-to', // microformats 2 property
                        'format'          => 'link', // Post Format that maps to this
                        'description'     => __( 'a reply to content typically on another site', 'indieweb-post-kinds' ),
                        'description-url' => 'http://indieweb.org/reply',
                        'title'           => false, // Should this kind have an explicit title
                        'show'            => true, // Show in Settings
                        )
		)
);

Add a function with your kind in the above format, hooking it in the init hook and it will add the Kind to the system. 

= Can I enable one of the Kinds you plan to offer in future? =

`set_post_kind_visibility( $slug, $show = true )` - If you add this function in early on, it will change the visibility of a kind.

= Can I create archives for each kind? ==

Post Kinds automatically handles the display of archives of individual types. So to view all the posts marked as "note", for example, one could visit the URL http://www.YOURSITE.COM/kind/note/. 
Simply replace YOURSITE.COM with your particular site name and the particular post kind name to access the others.

You can also add the date /kind/note/2018/12/24 to see date-based archives. Or /kind/note/tag/tagname using the slug of a tag to see an archive of tagged posts of a specific kind

For archives if you add exclude_kind as a query variable it will exclude specific kinds from the query `?exclude_kind=note`. You can also do this as /exclude/kind/note,checkin as it accepts multiple values

= Do you have RSS feeds for each kind? = 

Post Kinds also automatically handles RSS feeds which can be made available or subscribed to for any of the particular kinds. The RSS feed for all the posts marked as "note", for example could be found at either the URL `http://www.example.com/kind/note/feed` or  `http://www.example.com/feed/?kind=note` (if one doesn't have pretty permalinks enabled). Others can be obtained by replacing "note" with the other kinds.

= Do you support bookmarklets? =

At the moment, a fully automatic bookmarklet is not yet part of the plugin but you can send data directly to the Post Editor

* If you add `?kindurl=URL` to the post editor URL, it will automatically fill this into the URL box in post properties
* If you add `?kind=like` to the post editor URL, it will automatically set the kind.

So - `https://www.example.com/wp-admin/post-new.php?kindurl=URL&kind=like` will automatically set a like with the URL

= Can I post automatically outside the Post Editor? =

Using the [Micropub](https://wordpress.org/plugins/micropub) plugin for WordPress is the easiest way to post outside of the Post Editor. This will work with any Micropub client.

= I installed JetPack and I am no longer getting context added to my posts =

The JetPack sharing module conflicts with this plugin.

= When will this plugin support Gutenberg? =

I am not sure. It is not a strict priority. At this time, there is no definite time for this support.

= How do I get support? = 

The Development version of the plugin is hosted at [Github](https://github.com/dshanske/indieweb-post-kinds). You can file issues there.


== Theme Support ==

Post Kinds automatically adds information to `the_content` and `the_excerpt` filter. Being as this is inside the content block, which may or may not be desirable, you may remove these filters as noted
below and call `kind_display` directly. This will allow it to appear outside the content block.
	* `remove_filter( 'the_content', array( 'Kind_View', 'content_response' ), 20 );`
	* `remove_filter( 'the_excerpt', array( 'Kind_View', 'excerpt_response' ), 20 );`

The functions `has_post_kind`, `set_post_kind`, and `set_post_kind` will allow you to manipulate the kind settings in a post. `get_post_kind_string` will return the display name of a kind.

If you want to customize the look of the display, you can create a directory in your theme called `kind_views`, copy the file from the views directory of the plugin, and modify it. This will persist
through future plugin updates.


== Changelog ==

= 3.2.6 ( 2019-05-16 ) =
* Only allow citations for more than one video until improvement to media display
* Adjust inline style again at request of theme developer
* Fix issue with WordPress filters not being used by moving Post Kinds additions to before they are run
* Add Nag for Classic Editor

= 3.2.5 ( 2019-05-14 ) =
* Merge in update from Parse This that did not make it into 3.2.4 fixing saving of citation tags
* Fix issue in new markup

= 3.2.4 ( 2019-05-12 ) =
* Add exclude kind query var and rewrite
* Adjust icons to relative values and prevent fatwigoo
* Change checkin icon
* Fix issue where post date was not being passed to widget
* Attempt to fix permalink issue reported

= 3.2.3 ( 2019-04-27 ) =
* Fix issue with attached media
* Only suggest permalinks if not published
* Fix storage issue with tags in citation
* Hide media box when not media type
* Fix duration storage issues

= 3.2.2 ( 2019-03-24 ) =
* Fix issue with kindurl query
* If no title try to generate a slug from the content or excerpt
* Add food and drink templates and activate types

= 3.2.1 ( 2019-03-10 ) =
* Revert load change

= 3.2.0 ( 2019-03-10 ) =
* Switch from SVG Sprites to inline SVG
* Adjust storage locations for included libraries
* Refreshed version of Parse This that improves post type discovery for Micropub and parses more properties
* Add basic template to display events and itineraries from Quill

= 3.1.8 ( 2019-01-12 ) =
* Fix issue with Micropub posting caused by this plugin

= 3.1.7 ( 2019-01-05 ) =
* Fix issue with new tag rewrite overwriting feed by changing tag kind archive to /kind/????/tag/????? 

= 3.1.6 ( 2018-12-31) =
* Fix Micropub bug introduced by Parse This change.
* Written on a JetBlue flight as the last fix of 2018

= 3.1.5 ( 2018-12-28 ) =
* Update Parse This load to only load when needed
* Add title to Kind Menu widget
* Add Kind Posts Widget
* List minimum supported version as PHP5.4 as part of a slow bump up of version requirements

= 3.1.4 ( 2018-12-07 ) =
* Fix issue with widget
* Declare metaboxes are not compatible with Gutenberg

= 3.1.3 ( 2018-12-06 ) =
* Add Kind Menu Widget
* Add onthisday redirect
* Minor fixes

= 3.1.2 ( 2018-11-24 ) =
* Date archive view for kind archives
* Tag archive view for kind archives
* The Embed template allows for a template in the theme based on post format. This allows post kinds as an alternate
* Take over source queries for Micropub

= 3.1.1 ( 2018-10-14 ) =
* Fix save issues for Post Kind
* Fix auto-import for bookmarklets
* Fix author showing anonymously
* JSONFeed enhanced to use external url property

= 3.1.0 ( 2018-10-13 ) =
* Missing Add New Note in Dashbar
* Add itinerary to post type discovery
* Do not return a failed attempt to parse a URL
* New post kind registration function and classes replaces the previous filter and array.
* Parse This split into a more independent library.
* MF2 Debugger removed in favor of test parser inside admin
* Link Preview endpoint moved to Parse This and posting capability removed due improvements in Micropub
* Parse This now uses DOMDocument and XPath over regex. To avoid high overhead, since MF2 parsing also uses this, shift to generate DOMDocument only once.
* Parse This now generates compliant mf2 and jf2
* Kind_Meta class now removed as promised in prior version
* Parsing author URLs by making a second call to the URL and parsing that is now disabled by default as making multiple calls was slowing the parsing and therefore should be optional.
* Name of Kind Terms in Taxonomy adjusted to singular internationalized string per request
* Descriptions of kinds and names now updated on plugin activation or loading of settings page.
* For new posts citations should now be stored in compliant mf2 h-cites and will be refreshed on update of old posts.
* Delete old property when changing kind
* Finding photos, audio, and video is now in the MF2_Post class and the views are updated accordingly
* An instance of the MF2_Post class($mf2_post), the kind($kind), the mf2 property associated with that kind($type), as well as initializing $embed and $url are available to all views 
rather than having them instantiate them individually. This means some overhead.
* The MF2_Post class now caches the generated data in the event it is requested multiple times in a pageload.
* The MF2 post class now checks the photo property only for remote URLs and sideloads them.
* As of this version, Parse This is now a separate repository and all feature updates will be noted as of the next version in its separate changelog.
* Kind now appears in REST API post controller
* Photo Video and Audio now use the WordPress media selector and the citation should now be added in the attachment, as opposed to inside the post
* Photo Video and Audio presentation have new functions for display that are currently wrappers around the WordPress functions but hoping to add more customization in future


= 3.0.9 ( 2018-06-23 ) =
* If title is empty show start of excerpt in admin only
* Set default to article if post is a published post as opposed oto the default.

= 3.0.8 ( 2018-06-20 ) =
* Fix read template
* Add `change_kind` hook that triggers when a the kind on a post is changed
* Flush rewrite rules on plugin activation

= 3.0.7 ( 2018-06-17 ) =
* Add support for read-of property in micropub code
* Add direct links to post a new kind
* Add title property to kind info array

= 3.0.6 ( 2018-05-14 ) =
* Add option to move response to bottom
* Restore response to jsonFEED
* Only show text on feeds not icon

= 3.0.5 ( 2018-05-06 ) =
* Add privacy policy
* Change read property to read-of
* Add mf2 data to JSONFeed
* Improve feed handling of context in general

= 3.0.4 ( 2018-04-28 ) =
* Add support to automatically generate enclosures on photo, video, and audio kind
* Add player to audio and video template for provided URLs if not attached or not a known embed

= 3.0.3 ( 2018-04-14 ) =
* Update PHP-MF2 and add HTML5 parser
* Add support for checkedin-by property to parser
* Add ate and drank properties to post kind discovery
* Initial support for media files locally
* Update Kind Archive and Description Display to support multiple terms passed through( example.com/kind/checkin,photo ) See notes on unsupported WordPress status( https://core.trac.wordpress.org/ticket/34587 )
* Restore post type discovery behavior to note as testing seems to work now
* Reserve acquisition kind per request @chrisaldrich and assign icon
* Add hidden link with u-url to rich embeds in order to parse correctly

= 3.0.1/3.0.2 ( 2018-02-24 ) =
* Forgot to include a JS dependency

= 3.0.0 ( 2018-02-24 ) =
* Redo metabox into new more dynamic function
* Move metabox to default above editor
* Hide metabox when note or article is selected
* Show RSVP only when RSVP option is selected
* Show Duration and Start End only on appropriate kinds
* Add Duration selection
* Automatically retrieve details when URL box is updated allowing button to be removed
* Validate URL in box client-side
* Reserve question and sleep kind
* Activate Issue Kind due Github publish support added to Bridgy
* Override WordPress functionality to protect against empty posts if kind metadata is present
* Split time related functions into their own global function file
* Remove old tab templtes and replace with new templates for dynamic functioning
* Switch to dependency management for JS includes with npm
* Remove/consolidate admin JS

= 2.7.6 ( 2017-12-23 ) =
* Add audio kind view template
* Amend video kind template to include u-video
* Bypass micropub enhancements if micropub query as was generating error
* Add photo capability to checkin template
* Add safety check to Micropub filter to ensure not corrupting data

= 2.7.5 ( 2017-12-14 ) =
* Remove support for showing settings in REST API due issue with array property
* Add support for automatically retrieving when URL passed through from Micropub
* Add support for automatically retrieving when URL passed through as query variable in post UI
* Add support for simple API using REST to post
* Multi-author data no longer dropped but not fully supported

= 2.7.4 ( 2017-12-09 ) =
* Check for missing properties in all templates which should only happen if improperly filled 
* Switch entirely to icons from the new Font Awesome 5 release
* Bugfixes
= 2.7.3 ( 2017-12-03 ) =
* Change user agent
* Parser now captures video and audio tags and attempts to identify common file extensions
* Adding filters to make additional custom parsing possible
* Additional site tests
* Add specific featured image parsing
* Fix issue with author details generating fatal error when name only
* Fix underlying issue of collapsing single property associative arrays instead of just single property numeric arrays
* Parsing tweaks to allow for improved data to be passed, even if not yet displayed
= 2.7.2 ( 2017-11-30 ) =
* Remove PHP Shim library as not maintained and only used to get two extra properties from Twitter
* Switch to Composer for quick updates to PHP-MF2 (inspired by similar move by Semantic Linkbacks )
* Additional bugfixes for issues introduced in 2.7.0
= 2.7.1 ( 2017-11-25 ) =
* Fix check-in markup
* Fix errors caused by transition to arrays in 2.7.0 by not calling for single values in output
= 2.7.0 ( 2017-11-24 ) =
* Introduction of MF2_Post class to convert the Post into MF2 properties as a replacement for Kind_Meta
* Unit tests for Kind_Taxonomy
* Tests revealed issue in has_kind function - fixed
* Kind_Meta now deprecated and is a wrapper for retrieving using MF2_Post
* Storage has changed to a nested mf2 from a simplified jf2 however returns from MF2_Post are still in the simplified JF2 by default
* Checkin kind is now active but there is no full Post UI for it so this is primarily for use Micropub.
* Additional improvements in template and storage.
* Improvements in parsing different cases for the purpose of previewing links
= 2.6.6 =
* Fix eat svg icon
* Update travis and phpcs testing parameters
* Fixes in code based on phpcs discovered parameters
* Support additional properties in the parser and some nested microformats(h-adr)
= 2.6.5 =
* Add video kind template ( props @Ruxton )
* Restore ability to use text instead of icon
* Add option to select icon text or no display
* Add filter to disable icon or text
* Add new PHP requirement option to header
* Update Travis CI due changes
= 2.6.4 = 
* Enhance kind detection ( props @Ruxton )
= 2.6.3 =
* Hide KSES option behind POST_KINDS_KSES flag as it confused new users. (Sorry @acegiak)
* Allow meta tags with content first to be parsed and add Foursquare specific OGP tags
* Move enabling of kinds into the new unified settings
* Set checkin kind if checkin property is present in Micropub
* Preliminary checkin template
= 2.6.2 =
* Fix for absence of kind
= 2.6.1 =
* Fix photo template
* Add audio and video kind.
* Reserve event and issue kind.
* Fix overbroad u-photo.
= 2.6.0 =
* Remove h-as properties
* Add basic templates for some different kinds
* Improve duration display
* If post_ID not passed to display function will use get_the_ID
* Remove mf2 CSS from being styled
* Allow for child themes to add kind templates
* Photo post will now use either featured image or gallery of attached media automatically.
= 2.5.2 =
* Generation of strings being moved from individual functions to one unified function to make management easier
* Description now appears on Archives
* Description now appears on settings page
* Adding of `kind-type` css class only to posts
* Enhance setting of kind based on micropub properties
* Only set post format on initial save not subsequent ones
= 2.5.1 =
* Fix bug hiding metabox
* Add drop down filter for Kinds to View Posts
= 2.5.0 =
* Parsing code now rewritten to add Parse This class based on Press This parsing code
* MF2 parsing code rewritten and consolidated - future improvements coming
* Link Preview class now supports AJAX over REST API instead of admin-ajax
* Start/End and Published/Updated Separated in UI
* Duration is calculated and saved when post is saved based on start and end dates
* RSVP property created and the RSVP kind now available as an option
* Tags now an option for a reply-context and will be displayed as hashtags in future.
* Facebook manual embed code removed as Facebook is now supported by WordPress as of 4.7 for embeds
* Google Plus manual embed code removed even though Google Plus is not supported mostly because did not wish to maintain as sole exception
* Add whitelist - oembed will only be used if one of the officially whitelisted sites is there(Filter Available). Otherwise it will use the link-preview generation. Option to disable.
* Set default post format based on post kind.
* Redoing of options and removal of option to remove post formats support and theme compat
* Update help description
* Fix Mood SVG
* Cleanup and removal of older code
= 2.4.4 =
* Simplify coding standards issues
* Update settings
* Add quote kind ( props @miklb )
* Enhance parsing code in preparation for more enhancement
= 2.4.3 =
* Setting of Kind from Micropub now fixed due changes in Micropub plugin
* Micropub sets all properties as arrays including single properties and to match this will require more extensive changes.
In interim plugin will ignore multiple values and only use the first.
= 2.4.2 =
* Fix text domain
* Compatibility check for 4.6
* Change default to article from note, per commentary.
= 2.4.1 = 
* Fix error where Twitter shim is not loaded if other version of MF2 Parser is loaded
* Reserve Recipe per Request
* Removed the word travel and replaced with trip and itinerary...(idea from @aaronpk). 
= 2.4.0 =
* Refactor to initialize classes in new plugin loading class
* Remove additional global functions
* Switch from inline SVG to an SVG sprite
* Switch icons back to new Genericon svg icon set with supplements from FontAwesome
* Switch like to a heart to be in line with current thinking on this
* Views now use a function that looks for a directory called kind_views in the theme in the event any theme wants to customize display kinds
* Mood was added as a reserved kind per request @acegiak
* Made some adjustments to the meta parsing to improve results
= 2.3.7 =
* Remove auto-set function for kind if not post type post
* Move global functions to the class in which they were used
* Refine MF2 parser check to hide retrieve button if version is less than 5.3
= 2.3.6 = 
* Manually fix Parser which in latest version has one line that breaks compatibility with PHP 5.3
* Disable MF2 Parser usage if version lower than 5.3
= 2.3.5 = 
* Remove comment transition action in favor of proposing it be moved into webmentions plugin
* Replace send webmentions code with a hook
* Add PHP docblocks to php-mf-cleaner
* Update to latest version of MF2 Parser
* Theme Compatibility CSS separated from Basic CSS and Admin CSS again
* Remove helper functions no longer used
= 2.3.4 = 
* The CSS included with Post Kinds hides entry-title by default where applicable for non-aware themes.
* Attempted to fix reported issue with wrong URL being sent webmention
* Fix error with emoji decode test backcompat
* Separate parsing code into separate class
* Retire separate OGP Parser in favor of simpler built in code
= 2.3.3 =
* Fix issue with improper microformats generating bad parsed results.
* Add default kind option
* Change how plugin handles default options by adding Defaults function
* Bug fix by acegiak to content protection override
* Feature added by acegiak to set default kind if no kind is set when post is saved
* Add post_id to filter kind_response_display
* When retrieving information on a URL, set the title to the title of the URL if no title is set
* Add kindurl query variable to admin. If you add it to wp-admin/post-new.php with a URL then it will automatically put that URL in the URL box. For use by bookmarklets
= 2.3.2 = 
* Fix rendering issues when no response
* Add support for Indieweb Plugin
* Special rendering for excerpts
= 2.3.1 = 
* Changed method of retrieving svg files due server restrictions
* Jquery Date and Time Picker now enables when HTML5 input date/time not supported
= 2.3.0 = 
* Attempt to fix emoji issue reported by @acegiak
* Duration to be deprecated and replaced by start date and end date. The presence of a duration field will be used over start minus end date..
* Start/Published Date and End/Updated Date have an updated input field instead of a text string
* Response caching removed due limited utility
* Removal of older code in favor of new templating system for each kind. Have tried this before, but really want to make it work.
* Font icon replaced with SVG icons.
* Retrieve button now generates an alert if the URL box is blank or does not have a URL.
* Start of help system
* Hooks for possible future author data to be stored/retrieved from a nicknames cache
* Activation of the jam post kind, previously reserved.
* Addition of the read post kind, reflecting having read a book, as opposed to shorter content.
* Reserving of the quote post kind, for excerpting. Will be added in future version
= 2.2.1 = 
* Minor Tweaks and Bugfixes from changes in 2.2.0.
* Inputs Sanitized for Your Protection
* Summary currently shows summary of input if available. Full content is parsed if marked up with microformats, however this, like many elements, is not currently used.
* Rewrote storage protocol for better retrieval but still in intermediate state.
* Plan to rewrite and improve the display functionality with more templates for version 2.3.0.
= 2.2.0 = 
* New Tabbed Metabox - More Fields are Always Desired and this new design allows for more fields to be added without overwhelming the interface
* AJAX Enabled Retrieve Function instead of Automatic Retrieval
* New Fields for Citation (Published, Updated, Featured Image)
= 2.1.2 =
* Fix priority - user entered values should override parsed ones
* Add filters for parsing from additional markup and building the metadata
* Support for microformats2 import of metadata in addition to existing support for OpenGraph
* In the next version, will be rewriting the additions to the post editor. per request, it will give more control over the automatic parsing.
* Webmentions are now only sent if the new status is publish
= 2.1.1 =
* Bugfixes from Version 2.1.0
* Removal of Semantic Linkbacks code due upgrade in Semantic Linkbacks making it unnecessary 
= 2.1.0 = 
* Metadata Processing will be centralizing in the Kind_Meta class
* Continuing to move toward WordPress Coding Standards including inline documentation
* Fixes for the OpenGraph Parsing of Content to Fill Additional Metadata
* Metadata fields not part of the user interface to be stored for future use
* You can now pre-select a kind by adding ?kind=reply to wp-admin/post-new.php
* Merged the Kind_View and Kind_Display into a single class
* Plan for Customizable Display Objects will be replaced by hooks/filters in the next version.
* New Function get_post_mf2meta to retrieve the mf2 prefixed metadata
* Supports setting the post kind for posts created by Micropub plugin
* Rearrangement of classes and functions in an attempt to simplify
* Settings Page Now Allows You to Select which Kinds to Show in the New Post UI
* Clarified that some terms are reserved for use as future kinds.
* Added reserved terms eat, drink, follow and jam.
* Supports setting kind eat or drink when triggered by Teacup/Micropub only
= 2.0.2 =
* Change markup to use e-content instead of p-content
* Add versioning for CSS
= 2.0.1 =
* Bugfixes
* Accepted fix for webmention sending from acegiak
* Add option to disable sideloading of author images
= 2.0.0 =
* Rewrite completed
* Multi-author/multi-reply temporarily removed in storage, but it wasn't being used yet anyway
* Customizable display objects now supported
* Open Graph Protocol Parsing Added to Extract Title/Description/Image from URLs
* Sideloads Author Picture
* Add Duration Meta Field by request of Acegiak. This can be used for duration of activity...for example, watching/playing/listening/exercising/etc.
= 1.4.1 =
* Bug fixes for changes made in 1.4.0. 
= 1.4.0 = 
* Begin rewrite to class-scoped functions in line with WordPress Guidelines.
* Part of a major refinement and tightening of code
= 1.3.1  = 
* Additional refinements to kind defaults and minor bugfixes
= 1.3.0 =
* Add functionality to better customize displays for different types.
* Add default options on activation. 
* Show/hide check-in if Simple Location plugin is enabled. 
* Change exclude types to include types. 
* Add filter for plugins to show/hide kinds in the selector.
* Reduce default to a handful of kinds for new users. The plan going forward is to have some functionality enabled by secondary plugins
= 1.2.3 = 
* Fix bug introduced in 1.2.2 with URL storage. 
* Add publication as a field option
= 1.2.2 =
* Add option to disable KSES protection on the content box(courtesy/request of acegiak). 
* Minor cleanup
= 1.2.1 = 
* Add filter to support post kinds for Semantic Linkbacks plugin. 
* Add responses to feed content. 
= 1.2.0 = 
* Change to store meta using [WordPress Data](https://indieweb.org/WordPress_Data) proposal. 
* Display functionality broken into individual pieces to make it easier to customize and edit
* Multi-reply support in the data structure/display but not in entry
= 1.1.1 =
* Adds theme support and removes embed in content option
= 1.1.0 = 
* Added new kinds - listen, watch, check-in, play at the suggestion of acegiak. 
* Adds support for passive kinds. 
* Some code cleanup and commenting. 
* Start of add_theme_support function. This will replace the setting to embed in content in a future version.
= 1.0.2 = 
* Bug fixes
= 1.0.1 = 
* Update Readme to better describe 
= 1.0 =
* Now in the WordPress repository
= 0.3 = 
* Custom Code for Generating the Kind Select Box to Allow for Default Kind. 
* Defined constant POST_KIND_EXCLUDE to hide kinds from the selector 
= 0.24 = 
* Added Grunt/SASS support to more easily control changes and support internationalization
= 0.23 =
* Option to refresh the cache on each load added
= 0.22 = 
* Complete response html cached to post-meta and purged on post update.
= 0.21 = 
* Temporary fix for slow embed code in preparation for caching to reduce calls. 
* Removed defaultterms check to only run on settings page save or plugin activation instead of on each load.
= 0.2 = 
* Forked to Indieweb Post Kinds to reflect a change to act as an alternative to the post formats feature of WordPress. 
* Removed multikind option. Prior to this, the plugin functions mirrored those of tags. With the removal of multikind support, each post can only have one kind, and the functions will more closely mirror the Post Format. 
* Complete rewrite of the display functionality.
= 0.1.1 =
* Option to Update Metadata Deliberately Commented Out. 
* Anyone who used the old plugin should backup their database before considering migration. The data should still be in the database regardless.
= 0.1.0 = 
* Revised settings page to use WordPress Settings API. Alert: This version switches to using an array for storage of response data for future development. To migrate your data, please backup your database and then check Migrate to new data structure on update on the options page.
= 0.0.6 = 
* Added in support for Favorite
* fixed webmention support.
= 0.0.5 = 
* Set up embed handler for commonly linked sites. 
* Add options to turn on embeds for these sites. 
* Plugin is probably now feature complete enough for test deployment to a live site.
= 0.0.4 =
* Add function to generate verbs(Like to Liked...) for a given kind. 
* Add Display functionality in Beta. 
* Include Genericons and Dashicons for icon options for the various kinds. 
* Add plugin option to add the response URL to the top or bottom of the content section.
* Plugin near point at which can be deployed for basic use.
= 0.0.3 = 
* Location function removed and split into separate plugin called Simple Location. 
* Any check-in kind functionality will have this as a dependency.
= 0.0.2 = 
* Location meta box with HTML5 geolocation fill-in added. This allows posts to optionally have a location. This is as per the Wordpress Geodata specifications, so the Wordpress Android app will fill them in. There is no display functionality. 
* Various functions that mimic the built-in functions for other taxonomies were added, including filters to add additional behaviors. Default terms now prepopulate if no terms exist.
= 0.0.1 = 
* Registers a custom taxonomy
* adds in code snippets to turn the post meta box from checkboxes to radio buttons
* adds code to allow a custom permalink tag if needed.

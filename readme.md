# indieweb-post-kinds #
**Contributors:** dshanske  
**Tags:** indieweb  
**Requires at least:** 4.0  
**Tested up to:** 4.1  
**Stable tag:** 4.1  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Allows you to reply/like/RSVP etc to another site from your own, by adding support for kinds of posts to WordPress as a custom taxonomy.

## Description ##

Indieweb Post Kinds adds a Custom Taxonomy to the standard post type in Wordpress that allows posts to have a semantic component, based on the same conventions as the built-in Post Formats.

While the conventions are the same as post formats and thus can be used for theming, post kinds are less about formatting and more about responding to and interacting with other sites.

It also allows archives of replies, likes, reposts, etc. as well as adding the  appropriate classes to links within same.

Some of the kinds will not be used by everyone...the ability to hide/disable options not being used is a future feature.

## Post Kinds ##

### The Non-Response Kinds ###

These kinds have an analog in post formats.

 * **Article** - traditional long form content
 * **Note** - short content
 * **Photo** - image or photo post

### The Response Kinds ###

 * **Reply** - Replying to someone else's comment
 * **Repost** - a complete repost of someone else's content
 * **Like** -  props/compliments to the original post/poster
 * **Favorite** - special to the favoriter
 * **Bookmark** - also known as a linkblog. This is basically sharing/storing a link/bookmark.
 * **Tag** - Allows you to tag a post as being of a specific tag, or person tagging.
 * **RSVP** - A specific type of Reply regarding an event (Not Fully Fleshed Out)


### Future Kinds (Possibly) ###

 * **Check-In** - Identifying you are at a place


## Future Plans ##

* Add H-Card/Author Support using the functions in H-Card Tools Plugin
* Custom Meta Box for Kinds to replace the generic class
* Automatic import/parsing of information based on click of button
* Contextual response box, hiding/changing options based on Kind selected. Example, an RSVP that shows Yes/No/Maybe.

## Functions ##

`get_post_kind_slug($id)` - Return the kind slug for a given post. If `$id` is not specified, use current post.
`get_post_kind($id)` - Return the kind string for a given post. If `$id` is not specified, use current post.

`has_post_kind($kind, $post)` - Returns true/false if kind is in post. If post is empty, then use current post

`get_kind_context_class( $class, $classtype )` - Returns the CSS class to be applied to the response/context if the kind is one for which there is context. Classtype defaults to u, other option is usually p. Sets the class to the kind slug and for specially specified slugs, sets appropriate mf2 classes as well. $class specifies any additional classes to be added.



##  Filters ##

`get_the_kind` - Filter get_the_kinds

`the_kinds` - Filter get_the_kinds_list

`kind_classes` - Filter get_kind_class

`kind_verb` - Filter get_kind_verbs

`kind-response-display` - Filters the output being added to the_content or to custom location in theme


## Changelog ##
 * *Version 0.24* - Added Grunt/SASS support to more easily control changes and support internationalization
 * *Version 0.23* - Option to refresh the cache on each load added

 * *Version 0.22* - Complete response html cached to post-meta and purged on post update.

 * *Version 0.21* - Temporary fix for slow embed code in preparation for caching to reduce calls. Removed defaultterms check to only run on settings page save or plugin activation instead of on each load.

 * *Version 0.2* - Forked to Indieweb Post Kinds to reflect a change to act as an alternative to the post formats feature of WordPress. Removed multikind option. Prior to this, the plugin functions mirrored those of tags. With the removal of multikind support, each post can only have one kind, and the functions will more closely mirror the Post Format. Complete rewrite of the display functionality.

 * *Version 0.11* - Option to Update Metadata Deliberately Commented Out. Anyone who used the old plugin should backup their database before considering migration. The data should still be in the database regardless.

** * *Version 0.1* - Revised settings page to use WordPress Settings API. Alert:** This version switches to using an array for storage of response data for future development. To migrate your data, please backup your database and then check "Migrate to new data structure on update" on the options page.  

 * *Version 0.06* - Added in support for Favorite, fixed webmention support.

 * *Version 0.05* - Set up embed handler for commonly linked sites. Add options to turn on embeds for these sites. Plugin is probably now feature complete enough for test deployment to a live site.

 * *Version 0.04* - Add function to generate verbs(Like to Liked...) for a given kind. Add Display functionality in Beta. Include Genericons and Dashicons for icon options for the various kinds. Add plugin option to add the response URL to the top or bottom of the content section. Plugin near point at which can be deployed for basic use.

 * *Version 0.03* - Location function removed and split into separate plugin called Simple Location. Any check-in kind functionality will have this as a dependency.

 * *Version 0.02* - Location meta box with HTML5 geolocation fill-in added. This allows posts to optionally have a location. This is as per the Wordpress Geodata specifications, so the Wordpress Android app will fill them in. There is no display functionality. Various functions that mimic the built-in functions for other taxonomies were added, including filters to add additional behaviors. Default terms now prepopulate if no terms exist.

 * *Version 0.01* - Registers a custom taxonomy, adds in code snippets to turn the post meta box from checkboxes to radio buttons, adds code to allow a custom permalink tag if needed.

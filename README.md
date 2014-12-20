indieweb-post-kinds
=================

Indieweb Post Kinds adds a Custom Taxonomy to the standard post type in Wordpress that allows posts to have a semantic component, based on the same concept as the built-in Post Formats. This allows archives of replies, likes, reposts, etc. as well as theme support to add the appropriate classes to links within same.

Version 0.2 - Forked to Indieweb Post Kinds to reflect a change to act as an alternative to the post formats feature of WordPress. Removed multikind option.

Version 0.11 - Option to Update Metadata Deliberately Commented Out. Anyone who used the old plugin should backup their database before considering migration. The data should still be in the database regardless.

Version 0.1 - Revised settings page to use WordPress Settings API. Alert: This version switches to using an array for storage of response data for future development. To migrate your data, please backup your database and then check "Migrate to new data structure on update" on the options page.

Version 0.06 - Added in support for Favorite, fixed webmention support.

Version 0.05 - Set up embed handler for commonly linked sites. Add options to turn on embeds for these sites. Plugin is probably now feature complete enough for test deployment to a live site.

Version 0.04 - Add function to generate verbs(Like to Liked...) for a given kind. Add Display functionality in Beta. Include Genericons and Dashicons for icon options for the various kinds. Add plugin option to add the response URL to the top or bottom of the content section. Plugin near point at which can be deployed for basic use.

Version 0.03 - Location function removed and split into separate plugin called Simple Location. Any check-in kind functionality will have this as a dependency

Version 0.02 - Location meta box with HTML5 geolocation fill-in added. This allows posts to optionally have a location. This is as per the Wordpress Geodata specifications, so the Wordpress Android app will fill them in. There is no display functionality. Various functions that mimic the built-in functions for other taxonomies were added, including filters to add additional behaviors. Default terms now prepopulate if no terms exist.

Version 0.01 - Registers a custom taxonomy, adds in code snippets to turn the post meta box from checkboxes to radio buttons, adds code to allow a custom permalink tag if needed.

Roadmap - Refine display appearance. Add additional configuration options for customization.

== Functions == 

get_the_kinds($id) - Return the array of kinds for a given post. If $id is not specified, use current post.

get_the_kinds_list($before, $sep, $after, $id) - Returns a list of kinds for a given post with custom separators...

the_kinds($before, $sep, $after) - Echos the output of get_the_kinds_list for the current post.

has_kind($kind, $post) - Returns true/false if kind is in post. If post is empty, then use current post

get_kind_class( $class, $classtype ) - Returns the CSS class to be applied based on a kind. Classtype defaults to u, other option is usually p. Sets the class to the kind slug and for specially specified slugs, sets appropriate mf2 classes as well. $class specifies any additional classes to be added.

kind_class ($class) - echoes the output of get_kind_class

get_kind_verbs () - Returns the verbs reflected by the different kinds. Unspecified classes default to Mentioned. 

kind_verbs - Echoes the output of get_kind_verbs



== Filters ==

get_the_kind - Filter get_the_kinds

the_kinds - Filter get_the_kinds_list

kind_classes - Filter get_kind_class

kind_verb - Filter get_kind_verbs

response_display - Filters the output being added to the_content

=== Parse This ===
Contributors: dshanske
Tags: indieweb, microformats, jf2, json-ld, feeds
Stable tag: 2.0.0
Requires at least: 6.2
Requires PHP: 7.4
Tested up to: 7.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Parse This turns URLs into structured jf2 data.

== Description ==

Parse This fetches a URL and turns it into [jf2](https://jf2.spec.indieweb.org/), a simple JSON format for posts, people and feeds. The result can be used for link previews, feed readers, replies and likes, and similar features.

It started from the parsing code in Press This, which was removed from WordPress, and has grown from there. It runs as a standalone plugin that other plugins, such as Post Kinds and Yarns Microsub, can require or bundle.

It also runs on ClassicPress 2.x.

= What it parses =

* **Microformats2.** When a page is marked up with microformats, they are used first: entries, cards, events, reviews (including h-entry reviews with `review-of`), recipes, products and any other type. On a busy page it picks the item the page is about, and it follows the IndieWeb authorship algorithm.
* **Other metadata.** Other sources only fill in what microformats don't provide: a site-specific parser for YouTube or X (Twitter), then JSON-LD (articles, events, reviews, products, recipes, media and people) and Open Graph, Dublin Core and other meta tags. If there is still no content, the page's WordPress REST API version is fetched, when the site advertises one.
* **Parts of pages.** A link with a fragment (`#comment-12`) is parsed as just that part of the page. A WordPress comment link whose page has no microformats for it is read from the site's REST API as a reply.
* **Feeds.** RSS and Atom (through WordPress's SimplePie), JSON Feed 1 and 1.1, jf2 and mf2 JSON, and WordPress REST API post collections, including feeds served with the wrong content type.
* **Feed discovery.** It lists the feeds a page offers: alternates, `rel=feed` links, h-feeds and the WordPress REST API.

On PHP 8.4 and later, HTML is parsed with PHP's own HTML5 parser; on older PHP, with the bundled masterminds/html5 parser.

= Using it from PHP =

    $parse = new ParseThis\Parser( 'https://example.com/a-post/' );
    $result = $parse->fetch();
    if ( ! is_wp_error( $result ) ) {
        $parse->parse( array( 'return' => 'single' ) );
        $jf2 = $parse->get();          // jf2 array.
        $mf2 = $parse->get( 'mf2' );   // Or the same result as mf2.
    }

`ParseThis\Parser::parse()` accepts these arguments:

* `return`: `single` for one item (default) or `feed` for a list of items.
* `follow`: fetch and parse external author pages. Default false.
* `limit`: maximum number of feed items. Default 150.
* `jsonld`: try JSON-LD. Default true.
* `html`: fall back to meta tags. Default true.
* `references`: move nested objects with a URL (citations, cards, events, ...) into `refs`, as the jf2 spec describes. Default true.
* `location`: flatten a nested location into `latitude`, `longitude` and `altitude` properties, with `location` as a plain string. Default false.
* `alternate`: use a `rel=alternate` jf2 or mf2 version of the page if it has one. Default false.
* `require_content`: whether a summary alone isn't enough, so the page's WordPress REST API version is fetched for full content. Default: true for feeds, false otherwise.
* `always_arrays`: always return `category`, `photo`, `video`, `audio`, `syndication`, `like-of`, `repost-of`, `bookmark-of` and `in-reply-to` as arrays, as Microsub does. Default false, which follows jf2: a single value is not wrapped in an array.
* `debug`: include the raw data each source was read from (`_meta`, `_jsonld`, `_json`, `_yt`, `_ombed`, `_rest`). Default false. Before 2.0.0 this was added whenever `WP_DEBUG` was on.

To list a page's feeds instead, use `( new ParseThis\Discovery() )->fetch( $url )`.

= Output format =

Results follow [jf2](https://jf2.spec.indieweb.org/), with a few deliberate differences:

* A feed's entries are in `items`, as in Microsub timelines, rather than the JF2 Feed profile's `children`.
* Nested objects with a URL (citations, cards, events, products and any other type) are stored in `refs`, keyed by URL, as XRay does, rather than jf2's `references`. The property holds the URL. Authors stay cards.
* Microformats properties that Parse This doesn't know are passed through as they are.
* Entries get a `post-type` property from [Post Type Discovery](https://www.w3.org/TR/post-type-discovery/).
* Reviews can be an h-review, or an h-entry with `review-of` (a URL, or a nested h-cite, h-card, h-event, h-item or h-product) and `rating`, `best` and `worst`, as proposed in [microformats/h-entry#32](https://github.com/microformats/h-entry/issues/32) and published by Post Kinds. Both, and an h-entry that is also an h-review, get the post type `review`.
* `author` is always a card (or a list of cards), never a plain string.
* Properties starting with an underscore (`_links`, `_rest`, ...) are internal or debugging data.
* A fetched result has `_code`, the HTTP status (or the status the page declares with `<meta http-equiv="Status">`, such as 410 for a deleted post's stub), and `_source_format`, what it was read from: `mf2+html` (microformats), `html` (meta tags only), `mf2+json`, `jf2+json`, `feed+json` (JSON Feed), `xml` (RSS or Atom) or `wordpress` (REST API). After redirects, the document is read from, and relative links resolve against, the address it ended up at.
* When a URL can't be parsed, `fetch()` returns a `WP_Error`: `invalid-url`, `not_found` (404), `unauthorized` (401), `forbidden` (403), `http_error` (any other 4xx or 5xx, with the status as `response_code` in the error data), `content-type` (a format Parse This doesn't read), or the request error itself (a timeout, for example).

Values from fetched pages are sanitized: URL properties only contain `http` and `https` URLs, plain-text properties (`name`, `summary`, `category`, `content`'s `text`) have no HTML tags, and `content`'s `html` is limited to a safe set of tags. Escape values when you output them all the same, as with any remote data. Debugging data (`_jsonld`, `_meta`, ...) is not sanitized.

= REST API =

`GET /wp-json/parse-this/1.0/parse?url=https://example.com/`

* `url` (required): the URL to parse.
* `return`, `follow`, `references`, `location`, `require_content`, `always_arrays`, `debug`: as for `parse()` above.
* `mf2`: return mf2 instead of jf2.
* `discovery`: list the URL's feeds instead of parsing it.
* `nocache`: fetch the URL again instead of using a cached result.

Results are cached for 15 minutes per URL and set of parameters, so pasting the same link again doesn't fetch it again. Requests with `debug` are never cached.

The endpoint requires the `edit_posts` capability (Contributors and above); the `parse_this_rest_capability` filter changes it. Anyone who can use it can also try it from **Tools > Parse This**.

= Filters =

* `pt_rewrite_secure`: the list of domains whose `http://` URLs are upgraded to `https://` before fetching.
* `parse_this_img_filters`: an image URL found in a page, after the built-in exclusions (ads, spinners, tracking pixels and so on). Return an empty string to drop it.
* `parse_this_url_shorteners`: the hosts treated as link shorteners. Links to them in a summary are expanded to where they redirect; other links are left as they are, without a request.
* `parse_this_native_html_parser`: whether to parse HTML with PHP's own HTML5 parser on PHP 8.4 and later. Default true. Return false to use the bundled masterminds/html5 parser instead; the two only differ on invalid markup, where PHP's parser does what browsers do.
* `parse_this_max_requests`: how many further requests one parse may make for followed author pages and short-link expansion. Default 10. Receives the URL.
* `parse_this_cache_lifetime`: how long REST endpoint results are cached, in seconds. Default 15 minutes. Return 0 to turn caching off. Receives the URL.
* `parse_this_rest_api_jf2_type`: the jf2 type for a post read through a site's WordPress REST API. Default `entry`. Receives the REST API post object, which includes its WordPress post type, and the site's REST API root URL.

= Helper functions =

All classes and functions are in the `ParseThis` namespace.

* `ParseThis\mf2_to_jf2()` and `ParseThis\jf2_to_mf2()` convert between the two formats.
* `ParseThis\post_type_discovery()` returns the IndieWeb post type (note, article, reply, like, photo, ...) of a jf2 or mf2 entry.
* `ParseThis\jf2_references()` and `ParseThis\jf2_location()` apply the `references` and `location` transformations to any jf2 object.

Every function and class is documented in the source.

= Upgrading from 1.x =

Version 2.0.0 moved everything into the `ParseThis` namespace and dropped the `Parse_This_` prefix from class names (for example `Parse_This_MF2` is now `ParseThis\MF2`, and `Parse_This` is `ParseThis\Parser`). The old names that Post Kinds and Yarns use still work for now, as deprecated aliases:

* Classes: `Parse_This`, `Parse_This_Discovery`, `Parse_This_MF2`, `Parse_This_MF2_Utils` and `REST_Parse_This`.
* Functions: `mf2_to_jf2()`, `jf2_to_mf2()`, `post_type_discovery()`, `pt_load_domdocument()` and `seconds_to_iso8601()`.

Other old global names are no longer defined. Please switch to the namespaced names.

= Using Parse This from another plugin =

The recommended way is to require the Parse This plugin rather than bundle a copy. On WordPress 6.5 and later, add `Requires Plugins: parse-this` to your plugin's header. On older WordPress and ClassicPress, which don't read that header, check that Parse This is loaded and new enough before using it:

    add_action( 'plugins_loaded', function () {
        if ( ! defined( 'PARSE_THIS_VERSION' ) || version_compare( PARSE_THIS_VERSION, '2.0.0', '<' ) ) {
            // Show a notice asking for Parse This 2.0.0 or later, and skip the features that need it.
            return;
        }
        // Use ParseThis\Parser and the rest here.
    }, 11 );

`PARSE_THIS_VERSION` is the version of the copy of Parse This that loaded. It isn't defined by copies older than 2.0.0, so an older copy bundled in another plugin shows up as missing. The `parse_this_loaded` action fires, with the version, once Parse This has loaded.

= Bundling Parse This in another plugin =

If you do bundle it, copy the plugin into your plugin (for example under `lib/parse-this/`) and load it only if the standalone plugin hasn't already, from `plugins_loaded` at priority 10 or later:

    add_action( 'plugins_loaded', function () {
        if ( ! function_exists( 'parse_this_loader' ) ) {
            require_once __DIR__ . '/lib/parse-this/parse-this.php';
            parse_this_loader();
        }
    }, 11 );

The standalone plugin loads at priority 9, so when it is active it is used instead of your copy. Every function is also wrapped in `function_exists()` and classes are loaded by an autoloader, so if two copies do load, the first one wins.

Copies of Parse This 1.x bundled in other plugins keep working alongside 2.0: when the standalone plugin is active, their calls to the old names listed under "Upgrading from 1.x" are served by the standalone plugin.

== Frequently Asked Questions ==

= What is jf2? =

jf2 is a JSON format derived from microformats2, but simpler: single values aren't wrapped in arrays, and types drop the `h-` prefix. See the [jf2 specification](https://jf2.spec.indieweb.org/).

= Why does a page return only a title and a URL? =

That page has no microformats, JSON-LD or useful meta tags, or it blocks automated requests. The **Tools > Parse This** page shows exactly what was parsed for a URL.

= Which copy is used when several plugins bundle Parse This? =

The first one loaded. If the standalone plugin is active, it is that one. See "Bundling Parse This in another plugin" above.

= Does it work with ClassicPress? =

Yes. It is tested with ClassicPress 2.7 on PHP 7.4 to 8.3.

== Changelog ==

= 2.0.0 ( 2026-10-03 ) =

A major update. Please read "Upgrading from 1.x" if another plugin of yours uses Parse This.

Requirements:

* Requires PHP 7.4 and WordPress 6.2, or ClassicPress 2.x. Tested up to WordPress 7.1 and PHP 8.5.

Changes to be aware of:

* Everything is in the `ParseThis` namespace, and class names drop the `Parse_This_` prefix (`Parse_This` is now `ParseThis\Parser`). The old names Post Kinds and Yarns use still work, as deprecated aliases.
* Output: nested objects with a URL move to `refs`; authors are always cards; RSS and Atom items use `post-type` (not `post_type`); unknown microformats properties are kept; and fetched results carry `_code` and `_source_format`.
* `fetch()` returns a `WP_Error` for HTTP error pages (`not_found`, `unauthorized`, `forbidden`, `http_error`) instead of parsing them; a 410 Gone page is still parsed.
* The REST endpoint and the Tools > Parse This page require the `edit_posts` capability (filterable with `parse_this_rest_capability`), and its parameters are validated.
* Raw source data (`_meta`, `_jsonld` and so on) is only included with the new `debug` argument, not whenever `WP_DEBUG` is on.
* Plugins that use Parse This should require it rather than bundle it: see "Using Parse This from another plugin". `PARSE_THIS_VERSION` and the `parse_this_loaded` action help with that.
* The Instagram parser and the `ifset()` helper are removed.

New:

* Microformats always come first; JSON-LD, meta tags and the REST API only fill gaps. The `require_content` argument controls when a summary is enough.
* Microformats: choose the item a busy page is about; the full authorship algorithm, with feed entries inheriting the feed's author; reviews as an h-entry with `review-of`, `rating`, `best` and `worst` (as Post Kinds publishes them); complete h-event, h-review and h-recipe parsing; `follow-of`; and every value of repeated properties.
* Links with a fragment parse just that element; WordPress comment links fall back to the site's REST API.
* JSON-LD: reviews, products, recipes, and Article and Event subtypes, preferred over the WebPage node SEO plugins add.
* Feeds and JSON served with the wrong content type are recognized. JSON Feed items get plain text, a `post-type` and absolute URLs.
* Discovery finds mf2 JSON alternates, `rel=feed` links, RSS 1.0, multi-value `rel` attributes and JSON Feeds advertised as `application/json`, and follows permanent redirects.
* PHP's own HTML5 parser is used on PHP 8.4 and later: several times faster, and it handles invalid markup as browsers do (`parse_this_native_html_parser` turns it off).
* The `always_arrays` argument returns Microsub-style arrays.
* New filters: `parse_this_rest_api_jf2_type`, `parse_this_url_shorteners`, `parse_this_max_requests`, `parse_this_cache_lifetime`, `parse_this_rest_capability` and `parse_this_native_html_parser`.

Fixes:

* More than 50 bugs, many of them fatal errors on PHP 8, across RSS and Atom, the WordPress REST API, JSON-LD, meta tags, microformats, JSON Feed, X (Twitter), YouTube and feed discovery.
* Content HTML no longer loses the text before its first tag, and escaped text (`&lt;code&gt;`) no longer turns into markup.
* REST API posts are dated from their GMT dates; YouTube pages are read in full; titles that only repeat the content are dropped; names and ratings of "0" are kept; post types follow Post Type Discovery.

Performance:

* Feeds are downloaded once rather than twice; only link-shortener URLs in summaries are expanded; REST API tags and site details need far fewer requests; followed author pages are fetched once per request; REST endpoint results are cached for 15 minutes (`nocache` and `parse_this_cache_lifetime`).

Security:

* Output from fetched pages is sanitized (URLs limited to http and https, tags stripped from plain text, a safe set of HTML tags in content); one parse makes at most 10 further requests; the debug page sends its nonce in a header; plugin files exit when loaded outside WordPress; the OPML class handles invalid input safely.

Developers:

* Core's `fetch_feed()` is used for RSS and Atom; all requests go through `ParseThis\pt_remote_get()`.
* The bundled php-mf2 (0.5.0) and masterminds/html5 (2.11.0) are updated, and polyfills WordPress 6.2 provides are removed.
* Every function, class and hook is documented, and the plugin is tested on WordPress 6.2, the latest WordPress and ClassicPress 2.7 with PHP 7.4 to 8.5.

= 1.0.1 ( 2021-04-02 ) =
* Remove SimplePie as a dependency as the latest version 1.5.6 is now bundled with WordPress as of 5.6.
* Remove MB polyfill due issues with PHP8.0 compatibility in favor of simpler solution.

= 1.0.0 ( 2020-12-15 ) =
* First Official Release. Prior to this point it was in a point release.

== Upgrade Notice ==

= 2.0.0 =
A major update: classes and functions move to the ParseThis namespace (the old names Post Kinds and Yarns use still work), the output format changes in places, and the REST endpoint now requires the edit_posts capability. Requires PHP 7.4 and WordPress 6.2.

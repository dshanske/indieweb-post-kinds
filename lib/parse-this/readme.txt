=== Parse This ===
Contributors: dshanske
Tags: indieweb
Stable tag: trunk
Requires at least: 4.9
Requires PHP: 5.6
Tested up to: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Parse This turns URLs into structured jf2 data

== Description == 

Parse This is based on a variety of projects including the parsing code from Press This, which was removed from WordPress. 

* It supports parsing from MF2 if present
* For sites that are not marked up with Microformats 2(MF2) it will fall back onto parsing JSON-LD, then HTML/OpenGraph/Dublin Core Tags/etc. 
* It supports parsing of JSONFeed and RSS/Atom feeds
* It supports parsing of  WordPress REST API endpoints to generate a site feed

The goal is to produce structured jf2 data that can be used for previewing links as well as feed readers and other options. It is also bundled in the Post Kinds and Yarns Microsub plugins as a library.

It can be installed as a standalone plugin which will provide the necessary libraries and functionality as well as the REST API endpoint for getting JF2 data from an arbitrary URL or a WordPress Post. 


== Frequently Asked Questions ==

== Changelog ==

= 1.0.1 ( 2021-04-02 ) =
* Remove SimplePie as a dependency as the latest version 1.5.6 is now bundled with WordPress as of 5.6.
* Remove MB polyfill due issues with PHP8.0 compatibility in favor of simpler solution.

= 1.0.0 ( 2020-12-15 ) =
* First Official Release. Prior to this point it was in a point release.

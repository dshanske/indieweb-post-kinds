<?php

// Embeds for specific websites not supported by Wordpress
// planning on using wp embed register handler to register these for embedding
// Then use wp_oembed_get to embed posts from any supported site, and if the site isn't supported, mark it up manually

function get_embed_facebook ($url)
   {
      $embed = '<div id="fb-root"></div>';
      $embed .= '<script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/all.js#xfbml=1"; fjs.parentNode.insertBefore(js, fjs); }(document, \'script\', \'facebook-jssdk\'));</script>';
      $embed .= '<div class="fb-post" data-href="' . esc_url($url) . '" data-width="466"><div class="fb-xfbml-parse-ignore"><a href="' . esc_url($url) .  '">Post</a></div></div>';
      return $embed;
   }



function get_embed_gplus ($url)
   {
  	$embed = '<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>';
	$embed .= '<div class="g-post" data-href="' . esc_url($url) . '"></div>';
        return $embed;
   }

function get_embed_instagram ($url)
   {
        $embed = '<iframe src="';
	$embed .= esc_url($url) .  'embed" width="612" height="710" frameborder="0" scrolling="no" allowtransparency="true"></iframe>';
        return $embed;
   }

function new_embed_get ($url) {
      if (wp_is_mobile()) { return false; }
      $host = extract_domain_name($url);
      switch ($host)
	 {
		case 'facebook.com':
      	       	     $embed = get_embed_facebook ($url);
		break;
		case 'plus.google.com':
		     $embed = get_embed_gplus ($url);
		break;
		case 'instagram.com':
		     $embed = wp_oembed_get ($url);
		break;
		case 'twitter.com':
		     $embed = wp_oembed_get ($url);
		break;
		case 'youtube.com':
		     $embed = wp_oembed_get ($url);
		break;
                case 'hulu.com':
                     $embed = wp_oembed_get ($url);
                break;
                case 'slideshare.net':
                     $embed = wp_oembed_get ($url);
                break;
                case 'smugmug.com':
                     $embed = wp_oembed_get ($url);
                break;
		default:
		    $embed = false;
	}
     return $embed;
   }



?>

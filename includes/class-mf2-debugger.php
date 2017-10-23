<?php

class MF2_Debugger {
	/**
	* Initialize the plugin.
	*/
	public static function init() {
		add_filter( 'query_vars', array( 'MF2_Debugger', 'query_var' ) );
		add_action( 'parse_query', array( 'MF2_Debugger', 'parse_query' ) );
	}

	public static function query_var( $vars ) {
		$vars[] = 'pkdebug';
		return $vars;
	}

	public static function parse_query( $wp ) {
		// check if it is a debug request or not
		if ( ! $wp->get( 'pkdebug' ) ) {
			return;
		}
		$url = $wp->get( 'pkdebug', 'form' );
		if ( 'form' === $url ) {
			status_header( 200 );
			self::form_header();
			self::post_form();
			self::form_footer();
			exit;
		}
		// If Not logged in, reject input
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
			if ( is_numeric( $url ) ) {
				header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
				status_header( 200 );
				$response = new MF2_Post( (int) $url );
				echo wp_json_encode( $response->get( null ) );
				exit;
			}
			status_header( 400 );
			_e( 'The URL is Invalid', 'indieweb-post-kinds' );
			exit;
		} else {
			// generate response to URL
			$response = Link_Preview::parse( $url );
			if ( is_wp_error( $response ) ) {
				status_header( 400 );
				return $response->get_error_message();
			}
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
			status_header( 200 );
			echo wp_json_encode( $response );
			exit;

		}
	}
	public static function form_header() {
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="profile" href="http://gmpg.org/xfn/11">
		<title><?php echo get_bloginfo( 'name' ); ?>  - <?php _e( 'Indieweb Post Kinds Debugger', 'indieweb-post-kinds' ); ?></title> 
	   </head>
		<body>
		<header> 
		   <h3><a href="<?php echo site_url(); ?>"><?php echo get_bloginfo( 'name' ); ?></a>
		   <a href="<?php echo admin_url(); ?>">(<?php _e( 'Dashboard', 'indieweb-post-kinds' ); ?>)</a></h3>
		   <hr />
		   <h1> <?php _e( 'Indieweb Post Kinds Debugger', 'indieweb-post-kinds' ); ?></h1>
		</header>
		<?php
	}

	public static function form_footer() {
		?>
		</body>
		</html>
		<?php
	}

	public static function post_form() {
		?>
	  <div>
		<form action="<?php echo site_url(); ?>/?pkdebug=<?php echo $action; ?>" method="post" enctype="multipart/form-data">
		<p>
			<?php _e( 'URL:', 'indieweb-post-kinds' ); ?>
		<input type="text" name="pkdebug" size="70" />
		</p>
			<input type="submit" />
	  </form>
	</div>
	<?php
	}

}

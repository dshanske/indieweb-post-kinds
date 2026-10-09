<?php
/**
 * Kind_Menu_Widget class.
 *
 * @package Post_Kinds
 * @since   3.1.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget that lists links to the archive of each kind.
 *
 * @since 3.1.3
 */
class Kind_Menu_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 *
	 * @since 3.1.3
	 */
	public function __construct() {
		parent::__construct(
			'Kind_Menu_Widget',                // Base ID.
			__( 'Kind Menu Widget', 'indieweb-post-kinds' ),        // Name.
			array(
				'classname'             => 'kind_menu_widget',
				'description'           => __( 'A widget that allows you to display a menu of kind archives', 'indieweb-post-kinds' ),
				'show_instance_in_rest' => true,
			)
		);
	} // end constructor

	/**
	 * Front-end display of widget.
	 *
	 * @since 3.1.3
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		if ( array_key_exists( 'termslist', $instance ) ) {
			$include = $instance['termslist'];
		} else {
			$include = array();
		}
		$include = array_merge( $include, array( 'note', 'reply', 'article' ) );
		/**
		 * This filter is documented in includes/class-kind-taxonomy.php
		 */
		$include = array_unique( apply_filters( 'kind_include', $include ) );
		// Note cannot be removed or disabled without hacking the code.
		if ( ! in_array( 'note', $include, true ) ) {
			$include[] = 'note';
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper markup registered by the theme.
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Wrapper markup is the theme's; the title is escaped by core's widget_title filter.
		}
		?>

		<div id="kind-menu">
		<ul>
		<?php
		$feed = is_front_page() ? ' rel="feed"' : '';
		foreach ( $include as $i ) {
			$count = Kind_Taxonomy::get_post_kind_count( $i );
			if ( 0 === $count ) {
				continue;
			}
			$name = ( 1 === $count ) ? Kind_Taxonomy::get_kind_info( $i, 'singular_name' ) : Kind_Taxonomy::get_kind_info( $i, 'name' );
			/* translators: 1. Blog Title 2. Kind Singular Name */
			$feed_title = sprintf( __( '%1$s %2$s Feed', 'indieweb-post-kinds' ), get_bloginfo( 'name' ), Kind_Taxonomy::get_kind_info( $i, 'singular_name' ) );
			if ( 1 === (int) ( $instance['count'] ?? 0 ) ) {
				$label = sprintf( '%1$s (%2$s)', $name, number_format_i18n( $count ) );
			} else {
				$label = $name;
			}
			printf(
				'<li><a%4$s title="%5$s" type="text/html" href="%2$s">%1$s%3$s</a></li>',
				Kind_Taxonomy::get_icon( $i ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The plugin's own SVG icon markup.
				esc_url( Kind_Taxonomy::get_post_kind_link( $i ) ),
				esc_html( $label ),
				$feed, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A fixed attribute string.
				esc_attr( $feed_title )
			);
		}
		if ( 1 === (int) ( $instance['all'] ?? 0 ) ) {
			printf( '<li><a type="text/html" href="%2$s">%1$s%3$s</a></li>', Kind_Taxonomy::get_icon( 'firehose' ), esc_url( get_post_type_archive_link( 'post' ) ), esc_html__( 'All Posts', 'indieweb-post-kinds' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The plugin's own SVG icon markup.
		}
		?>
		</ul>
		</div>

		<?php
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper markup registered by the theme.
	}


	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @since 3.1.3
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$termslist = isset( $new_instance['termslist'] ) && is_array( $new_instance['termslist'] ) ? array_filter( $new_instance['termslist'], 'is_string' ) : array();
		$termslist = array_values( array_unique( array_intersect( array_map( 'sanitize_key', $termslist ), Kind_Taxonomy::get_kind_list() ) ) );
		return array(
			'title'     => isset( $new_instance['title'] ) && is_string( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'termslist' => $termslist,
			'count'     => empty( $new_instance['count'] ) ? 0 : 1,
			'all'       => empty( $new_instance['all'] ) ? 0 : 1,
		);
	}


	/**
	 * Create the form for the Widget admin
	 *
	 * @since 3.1.3
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$defaults  = array(
			'count'     => 1,
			'all'       => 1,
			'termslist' => array(),
		);
		$instance  = wp_parse_args( (array) $instance, $defaults );
		$termslist = (array) $instance['termslist'];
		?>
				<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title: ', 'indieweb-post-kinds' ); ?></label>
				<input type="text" size="30" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ?? '' ); ?>" /></p>
		<div id="kind-all">
		<?php
		foreach ( Kind_Config::get_termslist() as $term ) {
			$value = Kind_Taxonomy::get_post_kind_info( $term );
			if ( $value->show ) {
				printf(
					'<input name="%1$s[]" id="%2$s" type="checkbox" value="%3$s" %4$s />',
					esc_attr( $this->get_field_name( 'termslist' ) ),
					esc_attr( $this->get_field_id( 'termslist' ) ),
					esc_attr( $term ),
					checked( in_array( $term, $termslist, true ), true, false )
				);
				printf( '%1$s %2$s<br />', Kind_Taxonomy::get_icon( $term ), esc_html( $value->singular_name ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The plugin's own SVG icon markup.
			}
		}
		?>
		</div>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'all' ) ); ?>"><?php esc_html_e( 'Show Link to All:', 'indieweb-post-kinds' ); ?></label>
		<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'all' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'all' ) ); ?>" value="0" />
		<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'all' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'all' ) ); ?>" value="1" <?php checked( $instance['all'], 1 ); ?> />
</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Show Count:', 'indieweb-post-kinds' ); ?></label>
		<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" value="0" />
		<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" value="1" <?php checked( $instance['count'], 1 ); ?> />
</p>
		<?php
	}
}

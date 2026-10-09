<?php
/**
 * Kind_Post_Widget class.
 *
 * @package Post_Kinds
 * @since   3.1.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget that lists recent posts of selected kinds.
 *
 * @since 3.1.5
 */
class Kind_Post_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 *
	 * @since 3.1.5
	 */
	public function __construct() {
		parent::__construct(
			'Kind_Post_Widget',                // Base ID.
			__( 'Kind Post Widget', 'indieweb-post-kinds' ),        // Name.
			array(
				'classname'             => 'kind_post_widget',
				'description'           => __( 'A widget that allows you to display a list of posts by type', 'indieweb-post-kinds' ),
				'show_instance_in_rest' => true,
			)
		);
	} // end constructor

	/**
	 * Front-end display of widget.
	 *
	 * @since 3.1.5
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$kind   = $instance['kind'] ?? 'note';
		$number = absint( $instance['number'] ?? 5 );
		$posts  = get_posts(
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Kinds are a taxonomy; this is the indexed way to select them.
				'tax_query'           => array(
					array(
						'taxonomy' => 'kind',
						'field'    => 'slug',
						'terms'    => $kind,
					),
				),
				'numberposts'         => $number ? $number : 5,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);
		// Like core's Recent Posts widget, show nothing when there are no posts.
		// WP_Query caches the result in the object cache, so no transient is needed.
		if ( empty( $posts ) ) {
			return;
		}

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $instance['title'] ?? '', $instance, $this->id_base );

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper markup registered by the theme.
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Wrapper markup is the theme's; the title is escaped by core's widget_title filter.
		}
		echo '<div id="kind-posts"><ul>';
		foreach ( $posts as $post ) {
			printf( '<li>%1$s</li>', kind_get_the_link( $post ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kind_get_the_link() escapes its parts.
		}
		echo '</ul></div>';
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper markup registered by the theme.
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @since 3.1.5
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$kind   = isset( $new_instance['kind'] ) && is_string( $new_instance['kind'] ) ? sanitize_key( $new_instance['kind'] ) : '';
		$number = isset( $new_instance['number'] ) ? absint( $new_instance['number'] ) : 0;
		return array(
			'title'  => isset( $new_instance['title'] ) && is_string( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'kind'   => in_array( $kind, Kind_Taxonomy::get_kind_list(), true ) ? $kind : 'note',
			'number' => $number > 0 ? $number : 5,
		);
	}


	/**
	 * Create the form for the Widget admin
	 *
	 * @since 3.1.5
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$instance['kind'] = ( $instance['kind'] ?? 'note' );
		?>
				<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title: ', 'indieweb-post-kinds' ); ?></label>
				<input type="text" size="30" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				value="<?php echo esc_attr( $instance['title'] ?? '' ); ?>" /></p>
		<select name="<?php echo esc_attr( $this->get_field_name( 'kind' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'kind' ) ); ?>">
		<?php
		$list   = Kind_Config::get_termslist();
		$list[] = 'note';
		$list   = array_unique( $list );
		foreach ( $list as $term ) {
			printf(
				'<option value="%1$s" %3$s>%2$s</option>',
				esc_attr( $term ),
				esc_html( Kind_Taxonomy::get_kind_info( $term, 'singular_name' ) ),
				selected( $instance['kind'], $term, false )
			);
		}
		?>
		</select>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number of Posts:', 'indieweb-post-kinds' ); ?></label>
		<input type="number" min="1" step="1" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" value="<?php echo esc_attr( $instance['number'] ?? 5 ); ?>" />
		<?php
	}
}

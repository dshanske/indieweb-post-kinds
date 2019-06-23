<?php

class Kind_Menu_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'Kind_Menu_Widget',                // Base ID
			__( 'Kind Menu Widget', 'indieweb-post-kinds' ),        // Name
			array(
				'classname'   => 'kind_menu_widget',
				'description' => __( 'A widget that allows you to display a menu of kind archives', 'indieweb-post-kinds' ),
			)
		);

	} // end constructor

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$include = $instance['termslist'];
		$include = array_merge( $include, array( 'note', 'reply', 'article' ) );
		// Filter Kinds
		$include = array_unique( apply_filters( 'kind_include', $include ) );
		// Note cannot be removed or disabled without hacking the code
		if ( ! in_array( 'note', $include, true ) ) {
			$include[] = 'note';
		}

		// phpcs:ignore
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title']; // phpcs:ignore
		}
		?>

		<div id="kind-menu">
		<ul>
		<?php
		$feed = is_front_page() ? 'rel="feed"' : '';
		foreach ( $include as $i ) {
			$count = Kind_Taxonomy::get_post_kind_count( $i );
			if ( 0 === $count ) {
				continue;
			}
			$name = ( 1 === $count ) ? Kind_Taxonomy::get_kind_info( $i, 'singular_name' ) : Kind_Taxonomy::get_kind_info( $i, 'name' );
			/* translators: 1. Blog Title 2. Kind Singular Name */
			$title = sprintf( __( 'title="%1$s %2$s Feed"', 'indieweb-post-kinds' ), esc_attr( get_bloginfo( 'name' ) ), esc_attR( Kind_Taxonomy::get_kind_info( $i, 'singular_name' ) ) );
			if ( 1 === (int) $instance['count'] ) {
				$count = sprintf( '%1$s (%2$s)', $name, $count );
			} else {
				$count = $name;
			}
			printf(
				'<li><a %4$s %5$s type="text/html" href="%2$s">%1$s%3$s</a></li>',
				Kind_Taxonomy::get_icon( $i ), // phpcs:ignore
				esc_url( Kind_Taxonomy::get_post_kind_link( $i ) ),
				$count, // phpcs:ignore
				$feed, // phpcs:ignore
				$title  // phpcs:ignore
			); // phpcs:ignore
		}
		if ( 1 === (int) $instance['all'] ) {
			printf( '<li><a type="text/html" href="%2$s">%1$s%3$s</a></li>', Kind_Taxonomy::get_icon( 'firehose' ), esc_url( get_post_type_archive_link( 'post' ) ), esc_html__( 'All Posts', 'indieweb-post-kinds' ) ); // phpcs:ignore
		}
		?>
		</ul>
		</div>

		<?php
		// phpcs:ignore
		echo $args['after_widget'];
	}


	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		array_walk_recursive( $new_instance, 'sanitize_text_field' );
		return $new_instance;
	}


	/**
	 * Create the form for the Widget admin
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
				<p><label for="title"><?php esc_html_e( 'Title: ', 'indieweb-post-kinds' ); ?></label>
				<input type="text" size="30" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?> id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" value="
		<?php echo esc_html( ifset( $instance['title'] ) ); ?>" /></p>
		<div id="kind-all"> 
		<?php
		foreach ( get_option( 'kind_termslist', Kind_Taxonomy::get_kind_list() ) as $term ) {
			$value = Kind_Taxonomy::get_post_kind_info( $term );
			if ( $value->show ) {
				printf(
					'<input name="%1$s[]" id="%2$s" type="checkbox" value="%3$s" %4$s />',
					esc_attr( $this->get_field_name( 'termslist' ) ),
					esc_attr( $this->get_field_id( 'termslist' ) ),
					esc_attr( $term ),
					checked( in_array( $term, $termslist, true ), true, false )
				);
				printf( '%1$s %2$s<br />', Kind_Taxonomy::get_icon( $term ), esc_html( $value->singular_name ) ); //phpcs:ignore
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

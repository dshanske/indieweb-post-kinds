<?php

class Kind_OnThisDay_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'Kind_OnThisDay_Widget',                // Base ID
			__( 'Kind On This Day Widget', 'indieweb-post-kinds' ),        // Name
			array(
				'classname'   => 'kind_post_widget',
				'description' => __( 'A widget that allows you to display a list of posts from this day in history', 'indieweb-post-kinds' ),
			)
		);

	} // end constructor

	/**
	 * Set Defaults.
	 *
	 * @param array $instance Instance variable.
	 * @return array Instance after defaults added.
	 *
	 */
	public function defaults( $instance ) {
		$defaults = array(
			'number'    => 5,
			'nonefound' => __( 'There were no posts on this day in previous years', 'indieweb-post-kinds' ),
		);
		return wp_parse_args( $defaults, $instance );
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$instance = $this->defaults( $instance );
		$date     = new DateTime();
		// phpcs:ignore
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title']; // phpcs:ignore
		}
		$transient = 'kind_onthisday_widget' . $date->format( 'm-d' );
		$posts     = get_transient( $transient );
		if ( false === $posts ) {
			$query = array(
				'day'         => $date->format( 'd' ),
				'monthnum'    => $date->format( 'm' ),
				'numberposts' => instance['number'],
				'fields'      => 'ids',
			);
			$posts = get_posts( $query );
		}
		set_transient( $transient, $posts, HOUR_IN_SECONDS );
		$organize = array();
		foreach ( $posts as $post ) {
			$diff = human_time_diff( get_post_timestamp( $post ) );
			if ( ! array_key_exists( $diff, $organize ) ) {
				$organize[ $diff ] = array();
			}
			$organize[ $diff ][] = $this->list_item( $post );
		}

		echo '<div id="kind-onthisday">';
		if ( ! empty( $organize ) ) {
			echo '<ul>';
			foreach ( $organize as $title => $year ) {
				echo '<li>';
				/* translators: %s: Human-readable time difference. */
				printf( __( '%s ago...', 'indieweb-post-kinds' ), $title );
				echo '<ul>';
				echo implode( '', $year );
				echo '</li></ul>';
			}
			echo '</ul>';
		} else {
			echo esc_html( $instance['nonefound'] );
		}
		echo '</div>';
		echo $args['after_widget']; // phpcs:ignore
	}

	/**
	 * @access public
	 *
	 * @param WP_Post $post Post object
	 * @return string
	 */
	public function list_item( $post ) {
		$post = get_post( $post );
		return sprintf( '<li><a href="%2$s">%1$s</a></li>', $this->get_the_title( $post ), get_the_permalink( $post ) );
	}

	/**
	 * Construct a title for the post kind link.
	 *
	 * @access public
	 *
	 * @param WP_Post $post Post object.
	 * @param string  $kind Post kind.
	 * @return string
	 */
	public function get_the_title( $post ) {
		$post      = get_post( $post );
		$kind_post = new Kind_Post( $post );

		$content = $kind_post->get_name();
		if ( ! empty( $content ) ) {
			return $content;
		}

		$kind = $kind_post->get_kind();
		if ( ! in_array( $kind, array( 'note', 'article' ), true ) ) {
			$cite = $kind_post->get_cite( 'name' );
			if ( false === $cite ) {
				$content = Kind_View::get_post_type_string( $kind_post->get_cite( 'url' ) );
			} else {
				$content = $cite;
			}
		} else {
			$content = $post->post_excerpt;
			// If no excerpt use content
			if ( ! $content ) {
				$content = $post->post_content;
			}
			// If no content use date
			if ( $content ) {
				$content = mb_strimwidth( wp_strip_all_tags( $content ), 0, 40, '...' );
			}
		}
		return trim( sprintf( '<a href="%1$s">%2$s</a>', get_the_permalink( $post ), Kind_Taxonomy::get_before_kind( $kind ) . $content ) );
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
		$instance         = $this->defaults( $instance );
		$instance['kind'] = ifset( $instance['kind'], 'note' );
		if ( ! array_key_exists( 'nonefound', $instance ) ) {
			$instance['nonefound'] = __( 'There were no posts on this day in previous years', 'indieweb-post-kinds' );
		}
		?>
				<p><label for="title"><?php esc_html_e( 'Title: ', 'indieweb-post-kinds' ); ?></label>
				<input type="text" size="30" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?> id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				value="<?php echo esc_html( ifset( $instance['title'] ) ); ?>" /></p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_html_e( 'Number of Posts:', 'indieweb-post-kinds' ); ?></label>
		<input type="number" min="1" step="1" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" value="<?php echo esc_attr( ifset( $instance['number'], 5 ) ); ?>" />
		<label for="<?php echo esc_attr( $this->get_field_id( 'nonefound' ) ); ?>"><?php esc_html_e( 'Text if No Posts Found:', 'indieweb-post-kinds' ); ?></label>
		<textarea class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'nonefound' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'nonefound' ) ); ?>"><?php echo esc_html( $instance['nonefound'] ); ?></textarea>
		<?php
	}
}

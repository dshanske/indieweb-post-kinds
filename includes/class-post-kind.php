<?php
/**
 * Post_Kind class.
 *
 * @package Post_Kinds
 * @since   3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines a single post kind, as registered with register_post_kind().
 *
 * @since 3.1.0
 */
final class Post_Kind implements JsonSerializable {
	public $id; // Term ID
	public $slug; // Kind Slug
	public $name; // Name of Kind - Plural
	public $singular_name; // Name of Kind - Singular
	public $verb; // The string for the verb or action (liked this)
	public $format; // Post Format that Maps to This
	public $icon; // Icon
	public $description;
	public $description_url;
	public $title; // Should this Kind Have an Explicit Title
	public $show; // Show in Settings
	public $property; // Primary Property
	public $properties; // Array of Properties
	public $shortlink; // Shortlink Coding per http://tantek.pbworks.com/w/page/21743973/Whistle#design

	/**
	 * Arguments given at registration that are not declared properties, such as
	 * those a custom kind adds. Kept here rather than as dynamic properties,
	 * which are deprecated in PHP 8.2, and still readable as $kind->name.
	 *
	 * @var array
	 */
	private $extra = array();

	public function __construct( $slug, $args = array() ) {
		$this->slug = $slug;
		$this->set_props( $args );
	}

	public function set_props( $args ) {
		$defaults = array(
			'name'          => $this->slug,
			'singular_name' => $this->slug,
			'verb'          => $this->slug,
			'format'        => 'standard',
			'icon'          => $this->slug,
			'show'          => false,
			'property'      => '',
		);
		$args     = wp_parse_args( $args, $defaults );
		foreach ( $args as $property_name => $property_value ) {
			if ( 'extra' !== $property_name && property_exists( $this, $property_name ) ) {
				$this->$property_name = $property_value;
			} else {
				$this->extra[ $property_name ] = $property_value;
			}
		}
	}

	/**
	 * Reads an extra argument given at registration.
	 *
	 * @param string $name Argument name.
	 * @return mixed The value, or null if it was not given.
	 */
	public function __get( $name ) {
		return isset( $this->extra[ $name ] ) ? $this->extra[ $name ] : null;
	}

	/**
	 * Whether an extra argument was given at registration.
	 *
	 * @param string $name Argument name.
	 * @return bool
	 */
	public function __isset( $name ) {
		return isset( $this->extra[ $name ] );
	}

	/**
	 * Sets an extra argument, without creating a dynamic property.
	 *
	 * @param string $name  Argument name.
	 * @param mixed  $value Value.
	 */
	public function __set( $name, $value ) {
		$this->extra[ $name ] = $value;
	}

	/**
	 * Whether the kind has a declared property or an extra argument of this name.
	 *
	 * @param string $name Property or argument name.
	 * @return bool
	 */
	public function has( $name ) {
		return ( 'extra' !== $name && property_exists( $this, $name ) ) || array_key_exists( $name, $this->extra );
	}

	/**
	 * Returns the kind's declared properties and extra arguments, for JSON.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		$vars = get_object_vars( $this );
		unset( $vars['extra'] );
		return array_merge( $this->extra, $vars );
	}
}

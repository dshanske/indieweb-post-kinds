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
	/**
	 * Term ID.
	 *
	 * @since 3.1.0
	 * @var int
	 */
	public $id;

	/**
	 * Kind slug.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $slug;

	/**
	 * General name for the kind, plural.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $name;

	/**
	 * Name for one instance of the kind.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $singular_name;

	/**
	 * The verb or action, such as 'Liked'.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $verb;

	/**
	 * Post format that maps to this kind.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $format;

	/**
	 * Icon name.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $icon;

	/**
	 * Description of the kind.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $description;

	/**
	 * Link to more information about the kind.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $description_url;

	/**
	 * Whether the kind should have an explicit title.
	 *
	 * @since 3.1.0
	 * @var bool
	 */
	public $title;

	/**
	 * Whether to show the kind in Settings.
	 *
	 * @since 3.1.0
	 * @var bool
	 */
	public $show;

	/**
	 * Primary microformats 2 property, such as 'like-of'.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $property;

	/**
	 * The fields for the kind. See Kind_Fields.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	public $properties;

	/**
	 * Shortlink code, per http://tantek.pbworks.com/w/page/21743973/Whistle#design.
	 *
	 * @since 3.4.0
	 * @var string
	 */
	public $shortlink;

	/**
	 * Arguments given at registration that are not declared properties, such as
	 * those a custom kind adds. Kept here rather than as dynamic properties,
	 * which are deprecated in PHP 8.2, and still readable as $kind->name.
	 *
	 * @since 4.0.0
	 * @var array
	 */
	private $extra = array();

	/**
	 * Defines a kind.
	 *
	 * @since 3.1.0
	 *
	 * @param string $slug Kind slug.
	 * @param array  $args Optional. Kind arguments. See Kind_Taxonomy::register_post_kind().
	 */
	public function __construct( $slug, $args = array() ) {
		$this->slug = $slug;
		$this->set_props( $args );
	}

	/**
	 * Sets the kind's properties from its arguments, filling in defaults.
	 *
	 * Arguments that are not declared properties are kept as extra arguments.
	 *
	 * @since 3.1.0
	 *
	 * @param array $args Kind arguments. See Kind_Taxonomy::register_post_kind().
	 */
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
	 * @since 4.0.0
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
	 * @since 4.0.0
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
	 * @since 4.0.0
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
	 * @since 4.0.0
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
	 * @since 4.0.0
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

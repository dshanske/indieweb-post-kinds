<?php
/**
 * Post Kind Class
 *
 * @package Post Kind
 * Used to define a Post Kind object
 */

final class Post_Kind {
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
			'show'          => 'false',
			'property'      => '',
		);
		$args     = wp_parse_args( $args, $defaults );
		foreach ( $args as $property_name => $property_value ) {
			$this->$property_name = $property_value;
		}
	}

}

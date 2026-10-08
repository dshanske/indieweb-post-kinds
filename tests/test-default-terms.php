<?php

class DefaultTermsTest extends WP_UnitTestCase {
	private $writes = 0;

	public function set_up() {
		parent::set_up();
		Kind_Taxonomy::kind_defaultterms();
		add_action( 'created_term', array( $this, 'count_write' ) );
		add_action( 'edited_term', array( $this, 'count_write' ) );
	}

	public function tear_down() {
		remove_action( 'created_term', array( $this, 'count_write' ) );
		remove_action( 'edited_term', array( $this, 'count_write' ) );
		parent::tear_down();
	}

	public function count_write() {
		++$this->writes;
	}

	public function test_matching_terms_are_not_written() {
		Kind_Taxonomy::kind_defaultterms();
		$this->assertSame( 0, $this->writes );
	}

	public function test_changed_term_is_restored() {
		$term = get_term_by( 'slug', 'like', 'kind' );
		wp_update_term( $term->term_id, 'kind', array( 'name' => 'Changed' ) );
		$this->writes = 0;
		Kind_Taxonomy::kind_defaultterms();
		$this->assertSame( 1, $this->writes );
		$this->assertSame( Kind_Taxonomy::get_kind_info( 'like', 'singular_name' ), get_term_by( 'slug', 'like', 'kind' )->name );
	}

	public function test_missing_term_is_created() {
		wp_delete_term( get_term_by( 'slug', 'like', 'kind' )->term_id, 'kind' );
		$this->writes = 0;
		Kind_Taxonomy::kind_defaultterms();
		$this->assertSame( 1, $this->writes );
		$this->assertInstanceOf( 'WP_Term', get_term_by( 'slug', 'like', 'kind' ) );
	}
}

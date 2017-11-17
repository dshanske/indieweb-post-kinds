<?php
/**
 * Post Kind Metadata Class
 *
 * @package Post Kind
 * Deprecated and turned into a wrapper for mf2_post for retrieval only. To be removed in 2.8.0
 */

/**
 * Class to Manage Kind Meta.
 *
 * @package Post Kinds
 */
class Kind_Meta {
	protected $mf2_post;
	public function __construct( $post ) {
		$this->mf2_post = new MF2_Post( $post );
	}

	/**
	 * Gets URL.
	 *
	 * @return string|array Either a string indicating the URL or an array of URLs.
	 */
	public function get_url() {
		$cite = $this->mf2_post->fetch();
		return ifset( $cite['url'] );
	}

	/**
	 * Return All Meta Stored in the Object.
	 *
	 * return array $meta All Mf2 meta.
	 */
	public function get_all_meta() {
		return $this->mf2_meta->get( null );
	}

	/**
	 * Returns the post associated with the meta object.
	 *
	 * return WP_Post $post
	 */
	public function get_post() {
		return get_post( $this->mf2_post->get_post() );
	}

	/**
	 * Return Appropriate Cite Stored in the Object.
	 *
	 * return array $meta Return cite.
	 */
	public function get_cite() {
		return $this->mf2_post->fetch();
	}

	/**
	 * Return the Information on the Author.
	 *
	 * return array $author Data on Author.
	 */
	public function get_author() {
		$cite = $this->mf2_post->fetch();
		return ifset( $cite['author'] );
	}

	/**
	 * Return a specific meta key.
	 *
	 * return $string An arbitray key.
	 */
	public function get( $key ) {
		return $this->mf2_post->get( $key );
	}

} // End Class

<?php

class Parse_This_JSONFeed {
	private static function ifset( $key, $array ) {
		return isset( $array[ $key ] ) ? $array[ $key ] : null;
	}

	private static function get_author( $array ) {
		if ( ! isset( $array['author'] ) ) {
			return array();
		}
		$array = $array['author'];
		return array_filter(
			array(
				'name'  => self::ifset( 'name', $array ),
				'url'   => self::ifset( 'url', $array ),
				'photo' => self::ifset( 'avatar', $array ),
			)
		);
	}

	public static function to_jf2( $content, $url ) {
		$return          = array_filter(
			array(
				'type'    => 'jsonfeed',
				'name'    => self::ifset( 'title', $content ),
				'url'     => $url,
				'summary' => self::ifset( 'description', $content ),
				'photo'   => self::ifset( 'icon', $content ),
				'author'  => self::get_author( $content ),
			)
		);
		$return['items'] = array();
		foreach ( $content['items'] as $item ) {
			$newitem = array_filter(
				array(
					'uid'         => self::ifset( 'id', $item ),
					'url'         => self::ifset( 'url', $item ),
					'in-reply-to' => self::ifset( 'external_url', $item ),
					'name'        => self::ifset( 'title', $item ),
					'content'     => array_filter(
						array(
							'html' => self::ifset( 'content_html', $item ),
							'text' => self::ifset( 'content_text', $item ),
						)
					),
					'summary'     => self::ifset( 'summary', $item ),
					'featured'    => self::ifset( 'image', $item ),
					'published'   => normalize_iso8601( self::ifset( 'date_published', $item ) ),
					'updated'     => normalize_iso8601( self::ifset( 'date_modified', $item ) ),
					'author'      => self::get_author( $item ),
					'category'    => self::ifset( 'tags', $item ),
				)
			);
			if ( array_key_exists( 'attachments', $item ) ) {
				foreach ( $item['attachments'] as $attachment ) {
					switch ( $attachment['mime_type'] ) {
						case 'audio/mpeg':
							$newitem['audio'] = $attachment['url'];
							break;
						case 'image/jpeg':
						case 'image/png':
						case 'image/gif':
							$newitem['photo'] = $attachment['url'];
							break;
					}
				}
			}
			$return['items'][] = $newitem;
		}
		return $return;
	}
}




<?php

	/**
	 * @file
	 * Open Graph Protocol parser.
	 * Very simple open graph parser that parses open graph headers out of a given bit of php.
	 * 
	 * Example:
	 * 
	 * <code>
	 * $content = file_get_contents("https://www.youtube.com/watch?v=EIGGsZZWzZA");
	 * 
	 * print_r(\ogp\Parser::parse($content));
	 * </code>
	 * 
	 * @author Marcus Povey <marcus@marcus-povey.co.uk>
   * https://github.com/mapkyca/php-ogp
	 * @licence GPL2
	 */
	 
	 namespace ogp {
		 
		 use DOMDocument;
		 
		 class Parser {
			 
			 /**
			  * Parse content into an array.
			  * @param $content html The HTML
			  * @return array
			  */
			 public static function parse($content) {
				 
				 $doc = new DOMDocument();
				 @$doc->loadHTML($content);
				 
				 $interested_in = array('og', 'fb', 'twitter', 'article', 'music', 'video', 'book', 'profile'); // Open graph namespaces we're interested in (open graph + extensions)
				 
				 $ogp = array();
				 
				 $metas = $doc->getElementsByTagName('meta'); 
				 if (!empty($metas)) {
					 for ($n = 0; $n < $metas->length; $n++) {
						 
						 $meta = $metas->item($n);
						 
						 foreach (array('name','property') as $name) {
							 $meta_bits = explode(':', $meta->getAttribute($name)); 
							 if (in_array($meta_bits[0], $interested_in)) {
								 
								 // If we're adding to an existing element, convert it to an array
								 if (isset($ogp[$meta->getAttribute($name)]) && (!is_array($ogp[$meta->getAttribute($name)])))
									$ogp[$meta->getAttribute($name)] = array($ogp[$meta->getAttribute($name)], $meta->getAttribute('content'));
								 else if (isset($ogp[$meta->getAttribute($name)]) && (is_array($ogp[$meta->getAttribute($name)])))
									$ogp[$meta->getAttribute($name)][] = $meta->getAttribute('content');
								 else
									$ogp[$meta->getAttribute($name)] = $meta->getAttribute('content');
									
							 }
						 }
					 }
				 }
				 
				 return $ogp;
			 }
		 }
	 }

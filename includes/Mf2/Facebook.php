<?php
namespace Mf2\Shim;

use Mf2;
use DateTime;
use DOMElement;
use Exception;

function parseFacebook($html, $url=null) {
	$parser = new Facebook($html, $url, false);
	list($rels, $alternates) = $parser->parseRelsAndAlternates();
	return array_merge(array('rels' => $rels, 'alternates' => $alternates), $parser->parse());
}

function fbTimeToIso8601($t) {
	echo $t;
	$dt = DateTime::createFromFormat('l, F j, Y \a\t g:ia', $t);
	return $dt ? $dt->format(DateTime::ISO8601) : null;
}

class Facebook extends Mf2\Parser {
	public function parsePost(DOMElement $el) {
		$authorPhoto = $this->query('.//*' . Mf2\xpcs('fbStreamPermalinkHeader') . '//*' . Mf2\xpcs('profilePic'))->item(0)->getAttribute('src');
		$authorLink = $this->query('.//*' . Mf2\xpcs('permalinkHeaderInfo') . '/a')->item(0);
		$authorUrl = $this->resolveUrl($authorLink->getAttribute('href'));
		$authorName = trim($authorLink->textContent);
		
		$postLink = $this->query('.//*' . Mf2\xpcs('permalinkHeaderContentText') . '//*' . Mf2\xpcs('uiStreamSource') . '/a')->item(0);
		// TODO: resolve once php-mf2 is updated making ->resolveUrl() public
		$postUrl = $this->resolveUrl($postLink->getAttribute('href'));
		$postPublished = fbTimeToIso8601($this->query('./abbr', $postLink)->item(0)->getAttribute('title'));
		
		$contentEl = $this->query('.//*' . Mf2\xpcs('userContentWrapper') . '//*' . Mf2\xpcs('userContent'))->item(0);
		foreach ($this->query('.//a[starts-with(@onmouseover, "LinkshimAsyncLink")]') as $linkEl) {
			$linkEl->setAttribute('href', $linkEl->textContent);
			$linkEl->removeAttribute('onclick');
			$linkEl->removeAttribute('onmouseover');
			$linkEl->removeAttribute('target');
		}
		$contentPlaintext = $contentEl->textContent;
		$contentHtml = '';
		foreach ($contentEl->childNodes as $node) {
			$contentHtml .= $node->C14N();
		}
		
		$post = array(
			'type' => array('h-entry'),
			'properties' => array(
				'author' => array(
					'type' => array('h-card'),
					'properties' => array(
						'photo' => array($authorPhoto),
						'name' => array($authorName),
						'url' => array($authorUrl)
					)
				),
				'url' => array($postUrl),
				'published' => array($postPublished),
				'content' => array(array('value' => $contentPlaintext, 'html' => $contentHtml))
			)
		);

		return $post;
	}
	
	/**
   * Parse
   * 
   * @return array
   */
  public function parse() {
    $items = array();

    foreach($this->query('//*[@id="content"]') as $node) {
      $items[] = $this->parsePost($node);
    }

    return array('items' => array_values(array_filter($items)));
  }
}

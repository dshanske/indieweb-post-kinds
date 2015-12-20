<?php

namespace Mf2;

function spaceSeparatedAttributeXpathSelector($needle, $haystack="@class") {
	return 'contains(concat(" ", ' . $haystack . ', " "), " ' . $needle .' ")';
}

/**
 * XPath Class Selector
 * 
 * @return string
 */
function xpcs() {
	$classnames = func_get_args();
	if (count($classnames) == 1) {
		return '[' . spaceSeparatedAttributeXpathSelector($classnames[0]) . ']';
	} else {
		return '[' . implode(' and ', array_map(function ($item) {
			return spaceSeparatedAttributeXpathSelector($item);
		}, $classnames)) . ']';
	}
}

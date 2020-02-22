<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 19/02/2019
	 * Time: 5:39 PM
	 */

	namespace Skyenet\Database\MySQL\Search;


	class FullTextSearcher {

		private static function getSnippets($keyword, $contentText) {
			$snippet = '';
			$span = 30;
			if (preg_match("/((\W|^).{0,{$span}}\W)({$keyword})(\W.{0,{$span}}(\W|$))/i", " {$contentText} ", $match)) {
				$match = $match[0];

				if (!$match = trim($match)) {
					return null;
				}

				$snippet = $match;
			}

			$snippet = preg_replace("/(([[:<:]]{$keyword}[[:>:]]))/i", '<q>$1</q>', $snippet);
			$snippet = str_replace("\n", '', $snippet);

			return $snippet;
		}
	}
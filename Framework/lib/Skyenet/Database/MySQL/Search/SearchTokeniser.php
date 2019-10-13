<?php
	/**
	 * Created by PhpStorm.
	 * User: Skye
	 * Date: 19/02/2019
	 * Time: 5:34 PM
	 */

	namespace Skyenet\Database\MySQL\Search;


	class SearchTokeniser {
		private const INNO_DB_STOPWORDS = [
			'a',
			'about',
			'an',
			'are',
			'as',
			'at',
			'be',
			'by',
			'com',
			'de',
			'en',
			'for',
			'from',
			'how',
			'i',
			'in',
			'is',
			'it',
			'la',
			'of',
			'on',
			'or',
			'that',
			'the',
			'this',
			'to',
			'was',
			'what',
			'when',
			'where',
			'who',
			'will',
			'with',
			'und',
			'the',
			'www'
		];

		public array $searchTokens = [];
		public array $replaceSet = [];
		public $searchTerm;

		public array $searchTables = [];

		public array $requiredWordTokens = [];

		/**
		 * SearchTokeniser constructor.
		 *
		 * @param string $searchTerm
		 * @throws Exception
		 */
		public function __construct(string $searchTerm) {
			$tokens = [];
			$len = strlen($searchTerm);

			$currToken = new SearchToken();
			$inQuote = false;
			for ($i = 0; $i < $len; $i++) {
				$char = $searchTerm[$i];

				switch ($char) {
					case '\t':
					case ' ':
						if ($currToken->token) {
							$tokens[] = $currToken;
							$currToken = new SearchToken();
							$currToken->inQuote = $inQuote;
						}
						break;

					case '-':
						if (!$currToken->token && !$inQuote) {
							$currToken->isNegated = true;
						} else {
							$tokens[] = $currToken;

							$currToken = new SearchToken();
							$currToken->isNegated = true;

							if ($inQuote) {
								$currToken->isNegated = false;
								$currToken->inQuote = true;
							}
						}
						break;

					case '"':
						if (!strlen($currToken->token)) {
							$tok = new SearchToken();
							$tok->token = '"';
							$tok->inQuote = true;
							$inQuote = true;

							if ($currToken->inQuote) {
								$tok->isCloseQuote = true;
							}
							else {
								$tok->isOpenQuote = true;
							}


							$tokens[] = $tok;
							$currToken->inQuote = true;
						} else {
							$tokens[] = $currToken;

							$tok = new SearchToken();
							$tok->token = '"';
							$tok->isCloseQuote = true;

							$tokens[] = $tok;
							$currToken = new SearchToken();
							$currToken->inQuote = false;
							$inQuote = false;
						}
						break;

					case '*':
						if (strlen($currToken->token)) {
							$currToken->token .= '*';
							$currToken->isExpandable = true;

							$tokens[] = $currToken;
							$currToken = new SearchToken();
							$currToken->inQuote = $inQuote;
						}
						break;

					default:
						if (($char >= 'A' && $char <= 'Z') || ($char >= 'a' && $char <= 'z') || ($char >= '0' && $char <= '9')) {
							$currToken->token .= $char;
						} else {
							$tokens[] = $currToken;
							$currToken = new SearchToken();
							$currToken->inQuote = $inQuote;
						}
						break;
				}
			}

			if ($currToken->token) {
				$tokens[] = $currToken;
			}

			$validTokens = [];
			foreach ($tokens AS $token) {
				/* @var $token SearchToken */

				if (!$token->token) {
					continue;
				}

				// not a quoted string
				if (in_array($token->token, self::INNO_DB_STOPWORDS, true)) {
					if ($token->inQuote || $token->isExpandable) {
						$validTokens[] = $token;
					}
				} else {
					$validTokens[] = $token;
				}
			}

			$searchTerms = [];
			$inQuotes = false;

			foreach ($validTokens AS $token) {
				/* @var $token SearchToken */
				$outTerm = '';

				if ($token->isNegated && !$token->inQuote) {
					$outTerm .= '-';
				} else {
					if (!$token->isCloseQuote && !$token->isOpenQuote) {
						if (!$token->inQuote) {
							$outTerm .= '+';
						}
					} else {
						$inQuotes = !$inQuotes;
						if ($token->inQuote) {
							$outTerm .= '+';
						}
					}

				}

				// if our token is less than 3 characters and we are not inside quotes, then skip it
				if ((!$token->isOpenQuote && !$token->isCloseQuote) && !$token->inQuote && strlen($token->token) < 3) {
					continue;
				}

				// if we are not inside quotes, our token is in the stop-words list, and it is not expandable, then skip it
				if (!$token->isExpandable && !$inQuotes && in_array($token->token, self::INNO_DB_STOPWORDS, true)) {
					continue;
				}

				$outTerm .= $token->token . ' ';
				$searchTerms[] = $outTerm;

				// for building-out snippets
				// todo: move this to a more appropriate place
				if (!$token->isNegated) {
					if ($token->isExpandable) {
						$colourCodeRegex = ['/[[:<:]](\Q', '\E[^\s]{0,})[[:>:]]/i'];
					} else {
						$colourCodeRegex = ['/[[:<:]](\Q', '\E)[[:>:]]/i'];
					}

					$tk = strtr($token->token, [
						'*' => '',
						'"' => '',
					]);

					if ($tk !== '') {
						$repToken = $colourCodeRegex[0] . $tk . $colourCodeRegex[1];
						$this->replaceSet[$repToken] = '<q>$1</q>';
					}
				}
			}

			$tokenCount = count($validTokens);
			if (!$tokenCount) {
				throw new Exception('No valid search terms received');
			}

			// Close-out quotes if required
			if ($validTokens[$tokenCount - 1]->inQuote && !$validTokens[$tokenCount - 1]->isCloseQuote) {
				$closeQuoteToken = new SearchToken();
				$closeQuoteToken->isCloseQuote = true;
				$closeQuoteToken->inQuote = true;
				$validTokens[] = $closeQuoteToken;

				$searchTerms[] = '"';
			}

			// for building-out snippets
			// todo: move this to a more appropriate place
			$inQuote = false;
			$tokenPart = null;
			foreach ($validTokens AS $token) {
				/* @var $token SearchToken */
				if ($token->isNegated) {
					continue;
				}

				if ($token->isOpenQuote) {
					$inQuote = true;
					continue;
				}

				if ($token->isCloseQuote) {
					$inQuote = false;

					if ($tokenPart) {
						$this->requiredWordTokens[] = $tokenPart;
					}

					$tokenPart = null;
					continue;
				}

				if ($inQuote) {
					$tokenPart = trim("{$tokenPart} {$token->token}");
				} else {
					$tok = $token->token;
					if ($token->isExpandable) {
						$tok = substr($tok, 0, -1) . '[[:word:]]*';
					}

					$this->requiredWordTokens[] = $tok;
				}
			}

			$this->searchTokens = $validTokens;
			$this->searchTerm = implode('', $searchTerms);
		}
	}

<?php

/**
 * PHP Implementation of the Porter2 Stemming Algorithm.
 *
 * See http://snowball.tartarus.org/algorithms/english/stemmer.html .
 */
class Porter2 {
	
	/**
	 * Computes the stem of the word.
	 *
	 * @return string
	 *   The word's stem.
	 */
	public static function stem($word) {
		
		$exceptions = array(
			'skis' => 'ski',
			'skies' => 'sky',
			'dying' => 'die',
			'lying' => 'lie',
			'tying' => 'tie',
			'idly' => 'idl',
			'gently' => 'gentl',
			'ugly' => 'ugli',
			'early' => 'earli',
			'only' => 'onli',
			'singly' => 'singl',
			'sky' => 'sky',
			'news' => 'news',
			'howe' => 'howe',
			'atlas' => 'atlas',
			'cosmos' => 'cosmos',
			'bias' => 'bias',
			'andes' => 'andes',
		);
		
		// Process exceptions.
		if (isset($exceptions[$word])) {
			$word = $exceptions[$word];
		}
		elseif (strlen($word) > 2) {
			// Only execute algorithm on words that are longer than two letters.
			$word = self::prepare($word);
			$word = self::step0($word);
			$word = self::step1a($word);
			$word = self::step1b($word);
			$word = self::step1c($word);
			$word = self::step2($word);
			$word = self::step3($word);
			$word = self::step4($word);
			$word = self::step5($word);
		}
		return strtolower($word);
	}
	
	/**
	 * Set initial y, or y after a vowel, to Y.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The prepared word.
	 */
	protected static function prepare($word) {
		$inc = 0;
		if (strpos($word, "'") === 0) {
			$word = substr($word, 1);
		}
		while ($inc <= strlen($word)) {
			if (substr($word, $inc, 1) === 'y' && ($inc == 0 || self::isVowel($inc - 1, $word))) {
				$word = substr_replace($word, 'Y', $inc, 1);
			}
			$inc++;
		}
		return $word;
	}
	
	/**
	 * Search for the longest among the "s" suffixes and removes it.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The modified word.
	 */
	protected static function step0($word) {
		$found = FALSE;
		$checks = array("'s'", "'s", "'");
		foreach ($checks as $check) {
			if (!$found && self::hasEnding($word, $check)) {
				$word = self::removeEnding($word, $check);
				$found = TRUE;
			}
		}
		return $word;
	}
	
	/**
	 * Handles various suffixes, of which the longest is replaced.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The modified word.
	 */
	protected static function step1a($word) {
		$found = FALSE;
		if (self::hasEnding($word, 'sses')) {
			$word = self::removeEnding($word, 'sses') . 'ss';
			$found = TRUE;
		}
		$checks = array('ied', 'ies');
		foreach ($checks as $check) {
			if (!$found && self::hasEnding($word, $check)) {
				// @todo: check order here.
				$length = strlen($word);
				$word = self::removeEnding($word, $check);
				if ($length > 4) {
					$word .= 'i';
				}
				else {
					$word .= 'ie';
				}
				$found = TRUE;
			}
		}
		if (self::hasEnding($word, 'us') || self::hasEnding($word, 'ss')) {
			$found = TRUE;
		}
		// Delete if preceding word part has a vowel not immediately before the s.
		if (!$found && self::hasEnding($word, 's') && self::containsVowel(substr($word, 0, -2))) {
			$word = self::removeEnding($word, 's');
		}
		return $word;
	}
	
	/**
	 * Handles various suffixes, of which the longest is replaced.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The modified word.
	 */
	protected static function step1b($word) {
		$exceptions = array(
			'inning',
			'outing',
			'canning',
			'herring',
			'earring',
			'proceed',
			'exceed',
			'succeed',
		);
		if (in_array($word, $exceptions)) {
			return $word;
		}
		$checks = array('eedly', 'eed');
		foreach ($checks as $check) {
			if (self::hasEnding($word, $check)) {
				if (self::r($word, 1) !== strlen($word)) {
					$word = self::removeEnding($word, $check) . 'ee';
				}
				return $word;
			}
		}
		$checks = array('ingly', 'edly', 'ing', 'ed');
		$second_endings = array('at', 'bl', 'iz');
		foreach ($checks as $check) {
			// If the ending is present and the previous part contains a vowel.
			if (self::hasEnding($word, $check) && self::containsVowel(substr($word, 0, -strlen($check)))) {
				$word = self::removeEnding($word, $check);
				foreach ($second_endings as $ending) {
					if (self::hasEnding($word, $ending)) {
						return $word . 'e';
					}
				}
				// If the word ends with a double, remove the last letter.
				$double_removed = self::removeDoubles($word);
				if ($double_removed != $word) {
					$word = $double_removed;
				}
				elseif (self::isShort($word)) {
					// If the word is short, add e (so hop -> hope).
					$word .= 'e';
				}
				return $word;
			}
		}
		return $word;
	}
	
	/**
	 * Replaces suffix y or Y with i if after non-vowel not @ word begin.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The modified word.
	 */
	protected static function step1c($word) {
		if ((self::hasEnding($word, 'y') || self::hasEnding($word, 'Y')) && strlen($word) > 2 && !(self::isVowel(strlen($word) - 2, $word))) {
			$word = self::removeEnding($word, 'y');
			$word .= 'i';
		}
		return $word;
	}
	
	/**
	 * Implements step 2 of the Porter2 algorithm.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The modified word.
	 */
	protected static function step2($word) {
		$checks = array(
			"ization" => "ize",
			"iveness" => "ive",
			"fulness" => "ful",
			"ational" => "ate",
			"ousness" => "ous",
			"biliti" => "ble",
			"tional" => "tion",
			"lessli" => "less",
			"fulli" => "ful",
			"entli" => "ent",
			"ation" => "ate",
			"aliti" => "al",
			"iviti" => "ive",
			"ousli" => "ous",
			"alism" => "al",
			"abli" => "able",
			"anci" => "ance",
			"alli" => "al",
			"izer" => "ize",
			"enci" => "ence",
			"ator" => "ate",
			"bli" => "ble",
			"ogi" => "og",
		);
		foreach ($checks as $find => $replace) {
			if (self::hasEnding($word, $find)) {
				if (self::inR1($word, $find)) {
					$word = self::removeEnding($word, $find) . $replace;
				}
				return $word;
			}
		}
		if (self::hasEnding($word, 'li')) {
			if (strlen($word) > 4 && self::validLi(self::charAt(-3, $word))) {
				$word = self::removeEnding($word, 'li');
			}
		}
		return $word;
	}
	
	/**
	 * Implements step 3 of the Porter2 algorithm.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The modified word.
	 */
	protected static function step3($word) {
		$checks = array(
			'ational' => 'ate',
			'tional' => 'tion',
			'alize' => 'al',
			'icate' => 'ic',
			'iciti' => 'ic',
			'ical' => 'ic',
			'ness' => '',
			'ful' => '',
		);
		foreach ($checks as $find => $replace) {
			if (self::hasEnding($word, $find)) {
				if (self::inR1($word, $find)) {
					$word = self::removeEnding($word, $find) . $replace;
				}
				return $word;
			}
		}
		if (self::hasEnding($word, 'ative')) {
			if (self::inR2($word, 'ative')) {
				$word = self::removeEnding($word, 'ative');
			}
		}
		return $word;
	}
	
	/**
	 * Implements step 4 of the Porter2 algorithm.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The modified word.
	 */
	protected static function step4($word) {
		$checks = array(
			'ement',
			'ment',
			'ance',
			'ence',
			'able',
			'ible',
			'ant',
			'ent',
			'ion',
			'ism',
			'ate',
			'iti',
			'ous',
			'ive',
			'ize',
			'al',
			'er',
			'ic',
		);
		foreach ($checks as $check) {
			// Among the suffixes, if found and in R2, delete.
			if (self::hasEnding($word, $check)) {
				if (self::inR2($word, $check)) {
					if ($check !== 'ion' || in_array(self::charAt(-4, $word), array('s', 't'))) {
						$word = self::removeEnding($word, $check);
					}
				}
				return $word;
			}
		}
		return $word;
	}
	
	/**
	 * Implements step 5 of the Porter2 algorithm.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The modified word.
	 */
	protected static function step5($word) {
		if (self::hasEnding($word, 'e')) {
			// Delete if in R2, or in R1 and not preceded by a short syllable.
			if (self::inR2($word, 'e') || (self::inR1($word, 'e') && !self::isShortSyllable($word, strlen($word) - 3))) {
				$word = self::removeEnding($word, 'e');
			}
			return $word;
		}
		if (self::hasEnding($word, 'l')) {
			// Delete if in R2 and preceded by l.
			if (self::inR2($word, 'l') && self::charAt(-2, $word) == 'l') {
				$word = self::removeEnding($word, 'l');
			}
		}
		return $word;
	}
	
	/**
	 * Removes certain double consonants from the word's end.
	 *
	 * @param string $word
	 *   The word to stem.
	 *
	 * @return string $word
	 *   The modified word.
	 */
	protected static function removeDoubles($word) {
		$doubles = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');
		foreach ($doubles as $double) {
			if (substr($word, -2) == $double) {
				$word = substr($word, 0, -1);
				break;
			}
		}
		return $word;
	}
	
	/**
	 * Checks whether a character is a vowel.
	 *
	 * @param int $position
	 *   The character's position.
	 * @param string $word
	 *   The word in which to check.
	 * @param string[] $additional
	 *   (optional) Additional characters that should count as vowels.
	 *
	 * @return bool
	 *   TRUE if the character is a vowel, FALSE otherwise.
	 */
	protected static function isVowel($position, $word, array $additional = array()) {
		$vowels = array_merge(array('a', 'e', 'i', 'o', 'u', 'y'), $additional);
		return in_array(self::charAt($position, $word), $vowels);
	}
	
	/**
	 * Retrieves the character at the given position.
	 *
	 * @param int $position
	 *   The 0-based index of the character. If a negative number is given, the
	 *   position is counted from the end of the string.
	 * @param string $word
	 *   The word from which to retrieve the character.
	 *
	 * @return string
	 *   The character at the given position, or an empty string if the given
	 *   position was illegal.
	 */
	protected static function charAt($position, $word) {
		$length = strlen($word);
		if (abs($position) >= $length) {
			return '';
		}
		if ($position < 0) {
			$position += $length;
		}
		return $word[$position];
	}
	
	/**
	 * Determines whether the word ends in a "vowel-consonant" suffix.
	 *
	 * Unless the word is only two characters long, it also checks that the
	 * third-last character is neither "w", "x" nor "Y".
	 *
	 * @param int|null $position
	 *   (optional) If given, do not check the end of the word, but the character
	 *   at the given position, and the next one.
	 *
	 * @return bool
	 *   TRUE if the word has the described suffix, FALSE otherwise.
	 */
	protected static function isShortSyllable($word, $position = NULL) {
		if ($position === NULL) {
			$position = strlen($word) - 2;
		}
		// A vowel at the beginning of the word followed by a non-vowel.
		if ($position === 0) {
			return self::isVowel(0, $word) && !self::isVowel(1, $word);
		}
		// Vowel followed by non-vowel other than w, x, Y and preceded by
		// non-vowel.
		$additional = array('w', 'x', 'Y');
		return !self::isVowel($position - 1, $word) && self::isVowel($position, $word) && !self::isVowel($position + 1, $word, $additional);
	}
	
	/**
	 * Determines whether the word is short.
	 *
	 * A word is called short if it ends in a short syllable and if R1 is null.
	 *
	 * @return bool
	 *   TRUE if the word is short, FALSE otherwise.
	 */
	protected static function isShort($word) {
		return self::isShortSyllable($word) && self::r($word, 1) == strlen($word);
	}
	
	/**
	 * Determines the start of a certain "R" region.
	 *
	 * R is a region after the first non-vowel following a vowel, or end of word.
	 *
	 * @param int $type
	 *   (optional) 1 or 2. If 2, then calculate the R after the R1.
	 *
	 * @return int
	 *   The R position.
	 */
	protected static function r($word, $type = 1) {
		$inc = 1;
		if ($type === 2) {
			$inc = self::r($word, 1);
		}
		elseif (strlen($word) > 5) {
			$prefix_5 = substr($word, 0, 5);
			if ($prefix_5 === 'gener' || $prefix_5 === 'arsen') {
				return 5;
			}
			if (strlen($word) > 5 && substr($word, 0, 6) === 'commun') {
				return 6;
			}
		}
		
		while ($inc <= strlen($word)) {
			if (!self::isVowel($inc, $word) && self::isVowel($inc - 1, $word)) {
				$position = $inc;
				break;
			}
			$inc++;
		}
		if (!isset($position)) {
			$position = strlen($word);
		}
		else {
			// We add one, as this is the position AFTER the first non-vowel.
			$position++;
		}
		return $position;
	}
	
	/**
	 * Checks whether the given string is contained in R1.
	 *
	 * @param string $string
	 *   The string.
	 *
	 * @return bool
	 *   TRUE if the string is in R1, FALSE otherwise.
	 */
	protected static function inR1($word, $string) {
		$r1 = substr($word, self::r($word, 1));
		return strpos($r1, $string) !== FALSE;
	}
	
	/**
	 * Checks whether the given string is contained in R2.
	 *
	 * @param string $string
	 *   The string.
	 *
	 * @return bool
	 *   TRUE if the string is in R2, FALSE otherwise.
	 */
	protected static function inR2($word, $string) {
		$r2 = substr($word, self::r($word, 2));
		return strpos($r2, $string) !== FALSE;
	}
	
	/**
	 * Checks whether the word ends with the given string.
	 *
	 * @param string $string
	 *   The string.
	 *
	 * @return bool
	 *   TRUE if the word ends with the given string, FALSE otherwise.
	 */
	protected static function hasEnding($word, $string) {
		$length = strlen($string);
		if ($length > strlen($word)) {
			return FALSE;
		}
		return (substr_compare($word, $string, -1 * $length, $length) === 0);
	}
	
	/**
	 * Removes a given string from the end of the current word.
	 *
	 * Does not check whether the ending is actually there.
	 *
	 * @param string $string
	 *   The ending to remove.
	 */
	protected static function removeEnding($word, $string) {
		return substr($word, 0, -strlen($string));
	}
	
	/**
	 * Checks whether the given string contains a vowel.
	 *
	 * @param string $string
	 *   The string to check.
	 *
	 * @return bool
	 *   TRUE if the string contains a vowel, FALSE otherwise.
	 */
	protected static function containsVowel($string) {
		$inc = 0;
		$return = FALSE;
		while ($inc < strlen($string)) {
			if (self::isVowel($inc, $string)) {
				$return = TRUE;
				break;
			}
			$inc++;
		}
		return $return;
	}
	
	/**
	 * Checks whether the given string is a valid -li prefix.
	 *
	 * @param string $string
	 *   The string to check.
	 *
	 * @return bool
	 *   TRUE if the given string is a valid -li prefix, FALSE otherwise.
	 */
	protected static function validLi($string) {
		return in_array($string, array(
			'c',
			'd',
			'e',
			'g',
			'h',
			'k',
			'm',
			'n',
			'r',
			't',
		));
	}
	
}
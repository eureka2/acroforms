<?php declare(strict_types = 1);

/*
The MIT License (MIT)

Copyright (c) 2019 Jacques Archimède

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is furnished
to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

namespace acroforms\Utils;

class StringToolBox {

	 /**
	 * Protects parentheses that are in the contents of PDF or FDF files
	 * 
	 * @param string $content  the FDF or PDF content to protect
	 * @return string the content protected
	 */
	public static function protectParentheses($content) {
		$content = str_replace("\\(", "$@#", $content);
		$content = str_replace("\\)", "#@$", $content);
		return $content;
	}

	 /**
	 * Unprotects prerentheses previously protected by protectParentheses
	 *
	 * @param string $content  the FDF content with protected values
	 * @return string the content unprotected
	 */
	public static function unProtectParentheses($content) {
		$content = str_replace("$@#", "\\(", $content);
		$content = str_replace("#@$", "\\)", $content);
		$content = stripcslashes($content);
		return $content;
	}

	 /**
	 * Protects delimiters of hex values that are in the contents of PDF or FDF files
	 * 
	 * @param string $content  the FDF or PDF content to protect
	 * @return string the content protected
	 */
	public static function protectHexDelimiters($content) {
		$content = str_replace("\\<", "$@#", $content);
		$content = str_replace("\\>", "#@$", $content);
		return $content;
	}

	 /**
	 * Unprotects delimiters of hex values previously protected by protectHexDelimiters
	 *
	 * @param string $content  the FDF content with protected values
	 * @return string the content unprotected
	 */
	public static function unProtectHexDelimiters($content) {
		$content = str_replace("$@#", "\\<", $content);
		$content = str_replace("#@$", "\\>", $content);
		$content = stripcslashes($content);
		return $content;
	}

	 /**
	 * Removes dashes and table indexes in field names
	 *
	 * @param string $content  the field name to normalize
	 * @return string the normalized field name
	 */
	public static function normalizeFieldName($name) {
		$name = self::unaccent($name);
		$name = preg_replace("/\[(\d+)\]/", "_$1_", $name);
		$name = str_replace(["-", "."], ["_", "_"], $name);
		$name = preg_replace("/^topmostSubform_0__/", "", $name);
		return $name;
	}

	public static function unaccent($string) {
		static $chars = [
			'à' => 'a',
			'á' => 'a',
			'â' => 'a',
			'ã' => 'a',
			'ä' => 'a',
			'ą' => 'a',
			'å' => 'a',
			'ā' => 'a',
			'ă' => 'a',
			'ǎ' => 'a',
			'ǻ' => 'a',
			'À' => 'A',
			'Á' => 'A',
			'Â' => 'A',
			'Ã' => 'A',
			'Ä' => 'A',
			'Ą' => 'A',
			'Å' => 'A',
			'Ā' => 'A',
			'Ă' => 'A',
			'Ǎ' => 'A',
			'Ǻ' => 'A',
			'ç' => 'c',
			'ć' => 'c',
			'ĉ' => 'c',
			'ċ' => 'c',
			'č' => 'c',
			'Ç' => 'C',
			'Ć' => 'C',
			'Ĉ' => 'C',
			'Ċ' => 'C',
			'Č' => 'C',
			'ď' => 'd',
			'đ' => 'd',
			'Ð' => 'D',
			'Ď' => 'D',
			'Đ' => 'D',
			'è' => 'e',
			'é' => 'e',
			'ê' => 'e',
			'ë' => 'e',
			'ę' => 'e',
			'ē' => 'e',
			'ĕ' => 'e',
			'ė' => 'e',
			'ě' => 'e',
			'È' => 'E',
			'É' => 'E',
			'Ê' => 'E',
			'Ë' => 'E',
			'Ę' => 'E',
			'Ē' => 'E',
			'Ĕ' => 'E',
			'Ė' => 'E',
			'Ě' => 'E',
			'ƒ' => 'f',
			'ĝ' => 'g',
			'ğ' => 'g',
			'ġ' => 'g',
			'ģ' => 'g',
			'Ĝ' => 'G',
			'Ğ' => 'G',
			'Ġ' => 'G',
			'Ģ' => 'G',
			'ĥ' => 'h',
			'ħ' => 'h',
			'Ĥ' => 'H',
			'Ħ' => 'H',
			'ì' => 'i',
			'í' => 'i',
			'î' => 'i',
			'ï' => 'i',
			'ĩ' => 'i',
			'ī' => 'i',
			'ĭ' => 'i',
			'į' => 'i',
			'ſ' => 'i',
			'ǐ' => 'i',
			'Ì' => 'I',
			'Í' => 'I',
			'Î' => 'I',
			'Ï' => 'I',
			'Ĩ' => 'I',
			'Ī' => 'I',
			'Ĭ' => 'I',
			'Į' => 'I',
			'İ' => 'I',
			'Ǐ' => 'I',
			'ĵ' => 'j',
			'Ĵ' => 'J',
			'ķ' => 'k',
			'Ķ' => 'K',
			'ł' => 'l',
			'ĺ' => 'l',
			'ļ' => 'l',
			'ľ' => 'l',
			'ŀ' => 'l',
			'Ł' => 'L',
			'Ĺ' => 'L',
			'Ļ' => 'L',
			'Ľ' => 'L',
			'Ŀ' => 'L',
			'ñ' => 'n',
			'ń' => 'n',
			'ņ' => 'n',
			'ň' => 'n',
			'ŉ' => 'n',
			'Ñ' => 'N',
			'Ń' => 'N',
			'Ņ' => 'N',
			'Ň' => 'N',
			'ò' => 'o',
			'ó' => 'o',
			'ô' => 'o',
			'õ' => 'o',
			'ö' => 'o',
			'ð' => 'o',
			'ø' => 'o',
			'ō' => 'o',
			'ŏ' => 'o',
			'ő' => 'o',
			'ơ' => 'o',
			'ǒ' => 'o',
			'ǿ' => 'o',
			'Ò' => 'O',
			'Ó' => 'O',
			'Ô' => 'O',
			'Õ' => 'O',
			'Ö' => 'O',
			'Ø' => 'O',
			'Ō' => 'O',
			'Ŏ' => 'O',
			'Ő' => 'O',
			'Ơ' => 'O',
			'Ǒ' => 'O',
			'Ǿ' => 'O',
			'ŕ' => 'r',
			'ŗ' => 'r',
			'ř' => 'r',
			'Ŕ' => 'R',
			'Ŗ' => 'R',
			'Ř' => 'R',
			'ś' => 's',
			'š' => 's',
			'ŝ' => 's',
			'ş' => 's',
			'Ś' => 'S',
			'Š' => 'S',
			'Ŝ' => 'S',
			'Ş' => 'S',
			'ţ' => 't',
			'ť' => 't',
			'ŧ' => 't',
			'Ţ' => 'T',
			'Ť' => 'T',
			'Ŧ' => 'T',
			'ù' => 'u',
			'ú' => 'u',
			'û' => 'u',
			'ü' => 'u',
			'ũ' => 'u',
			'ū' => 'u',
			'ŭ' => 'u',
			'ů' => 'u',
			'ű' => 'u',
			'ų' => 'u',
			'ư' => 'u',
			'ǔ' => 'u',
			'ǖ' => 'u',
			'ǘ' => 'u',
			'ǚ' => 'u',
			'ǜ' => 'u',
			'Ù' => 'U',
			'Ú' => 'U',
			'Û' => 'U',
			'Ü' => 'U',
			'Ũ' => 'U',
			'Ū' => 'U',
			'Ŭ' => 'U',
			'Ů' => 'U',
			'Ű' => 'U',
			'Ų' => 'U',
			'Ư' => 'U',
			'Ǔ' => 'U',
			'Ǖ' => 'U',
			'Ǘ' => 'U',
			'Ǚ' => 'U',
			'Ǜ' => 'U',
			'ŵ' => 'w',
			'Ŵ' => 'W',
			'ý' => 'y',
			'ÿ' => 'y',
			'ŷ' => 'y',
			'Ý' => 'Y',
			'Ÿ' => 'Y',
			'Ŷ' => 'Y',
			'ż' => 'z',
			'ź' => 'z',
			'ž' => 'z',
			'Ż' => 'Z',
			'Ź' => 'Z',
			'Ž' => 'Z',
			'Ǽ' => 'A',
			'ǽ' => 'a',
		];
		$string = mb_convert_encoding($string, "UTF-8", ["UTF-8", "ISO-8859-1", "UTF-16BE", "WinAnsiEncoding", "Identity-H"]);
		return strtr($string, $chars);
	}
}

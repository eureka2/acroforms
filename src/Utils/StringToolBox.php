<?php

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

}

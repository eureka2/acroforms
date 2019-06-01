<?php

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

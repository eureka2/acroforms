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

namespace acroforms\Parser;

use acroforms\Model\FDFDocument;
use acroforms\Utils\StringToolBox;

/**
 * Class to parse the content of a FDF file.
 */
class FDFParser {

	private $fdfDocument = null; 

	public function __construct(FDFDocument $fdfDocument) {
		$this->fdfDocument = $fdfDocument;
	}

	/**
	 * Parses the content of a FDF file and saved extracted field data 
	 *
	 * @access public
	 */
	public function parse() {
		$beg = chr(254);
		$end = chr(255);
		$content = str_replace(["<<", ">>"], [$beg, $end], StringToolBox::protectParentheses($this->fdfDocument->getContent())); 
		if (preg_match_all("/" . $beg . "([^" . $end . "]+)" . $end . "/", $content, $matches)) {
			$fMax = count($matches[0]);
			for ($f = 0; $f < $fMax; $f++) {
				$field = trim(preg_replace("/[\r\n]/", " ", $matches[1][$f]));
				if (preg_match("#/T\s*\(([^\)]+)\)#", $field, $t)) {
					$key = StringToolBox::unProtectParentheses(preg_replace("/\[\d+\]$/", "", $t[1]));
					if (($fieldObject = $this->fdfDocument->getPdfdocument()->getField($key)) !== null) {
						$field = trim(str_replace($t[0], "", $field));
						if (preg_match("#/V\s*\(([^\)]*)\)#", $field, $v) || preg_match("#/V\s*/(.*)#", $field, $v)) {
							$value = StringToolBox::unProtectParentheses($v[1]);
							if ($fieldObject->getType() == 'Btn') {
								$this->fdfDocument->setButton($key, $value);
							} else {
								$this->fdfDocument->setField($key, $value);
							}
						}
					}
				}
			}
		}
		$this->fdfDocument->setParseNeeded(false);
	}

}

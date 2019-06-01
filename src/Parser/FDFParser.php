<?php

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
		$fields = [];
		$buttons = [];
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
								$buttons[$key ] = $value;
							} else {
								$fields[$key ] = $value;
							}
						}
					}
				}
			}
		}
		$this->fdfDocument->setFields($fields);
		$this->fdfDocument->setButtons($buttons);
		$this->fdfDocument->setParseNeeded(false);
	}

}

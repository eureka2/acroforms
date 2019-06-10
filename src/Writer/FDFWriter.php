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

namespace acroforms\Writer;

use acroforms\Builder\FDFBuilder;
use acroforms\Model\FDFDocument;
use acroforms\Utils\URLToolBox;

/**
 * Class to generate and/or write a FDF file.
 */
class FDFWriter {

	private $fdfDocument = null; 

	public function __construct(FDFDocument $fdfDocument) {
		$this->fdfDocument = $fdfDocument;
	}

	/**
	 * Generates a form definition file (fdf)
	 *
	 * @param string $pdfUrl
	 * @param string $outputMode 
	 *	- 'D' :		WARNING!! By default, THIS FUNCTION SENDS HTTP HEADERS! It MUST be called before 
	 *	- 			any content is spooled to the browser, or the function will fail!
	 *	- 'S' :		Return the fdf file generated as a string
	 *	<fdf_file>:	fullpathname to where the fdf file content has to be saved.
	 *
	 * @return mixed the return value which can be:
	 *	-a boolean true when outputMode is set to 'D'
	 *	-a text the fdf content when outputMode is set to 'S'
	 *	-an array holding success flag with either the fdf size or the error message
	 */
	public function output($pdfUrl, $outputMode = 'D') {
		$txOrChFields = [];
		$fields = $this->fdfDocument->getFields();
		foreach ($fields as $fieldName => $value) {
			if (($field = $this->fdfDocument->getPdfdocument()->getField($fieldName)) !== null) {
				$entry = $field->getFullName();
			} else {
				$entry = $fieldName;
			}
			$txOrChFields[$entry] = $value;
		}
		$btnFields = [];
		$buttons = $this->fdfDocument->getButtons();
		foreach ($buttons as $fieldName => $value) {
			if (($field = $this->fdfDocument->getPdfdocument()->getField($fieldName)) !== null) {
				$entry = $field->getFullName();
			} else {
				$entry = $fieldName;
			}
			$btnFields[$entry] = $value;
		}
		$required = [];
		$readonly = [];
		$fdfBuilder = new FDFBuilder();
		$fdf = $fdfBuilder->build(
			URLToolBox::resolveUrl($pdfUrl), 
			$txOrChFields,
			$btnFields,
			$required,
			$readonly
		);
		switch($outputMode) {
			case "D":
				header ("Content-Type: application/vnd.fdf");
				print $fdf;
				$ret = true;
				break;
			case "S":
				$ret = $fdf;
				break;
			default:
				$ret = $this->write($fdf, $outputMode);
		}
		return $ret;
	}

	private function write($fdf, $fdfFile) {
		$accessError = '';
		$fdfDir = dirname($fdfFile);
		if (file_exists($fdfDir)) {
			if (is_writable($fdfDir)) {
				if (!is_writable($fdfFile)) {
					$accessError = sprintf("FDFWriter: can not write fdf file (%s), disk full or missing rights?", $fdfFile);
				}
			} else {
				$accessError = sprintf("FDFWriter output: can not write into fdf's directory (%s)", $fdfDir);
			}
		} else {
			$accessError = sprintf("FDFWriter output: can not access to fdf's directory (%s)", $fdfDir);
		}
		$success = false;
		if ($accessError != "") {
			$err = sprintf("FDFWriter output : Unable to create fdf file '%s', reason: %s.", $fdfFile, $accessError);
		} else {
			if (($handle = fopen($fdfFile, 'w')) !== false) {
				$err = fwrite($handle, $fdf, strlen($fdf));
				fclose($handle);
				$success = true;
			} else {
				$err = sprintf("FDFWriter output : Unable to generate file '%s', disk full or corrupted?.", $fdfFile);
			}
		}
		return ["success" => $success, "return" => $err];
	}

}

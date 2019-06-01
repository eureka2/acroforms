<?php

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
			URLToolBox::resolve_url($pdfUrl), 
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
				if (!is_writable($fdfFile) && false) {
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
				$success = true;
			} else {
				$err = sprintf("FDFWriter output : Unable to generate file '%s', disk full or corrupted?.", $fdfFile);
			}
			fclose($handle);
		}
		return ["success" => $success, "return" => $err];
	}

}

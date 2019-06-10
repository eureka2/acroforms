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

use acroforms\Filter\FilterFactory;
use acroforms\Model\PDFDocument;

/**
 * Class to modify the lines of a PDF file.
 */
class PDFWriter {

	private $pdfDocument = null; 

	private $converter = null;

	public function __construct(PDFDocument $pdfDocument) {
		$this->pdfDocument = $pdfDocument;
		$this->converter = FilterFactory::getFilter("ASCIIHexDecode");
	}

	/**
	 * Sets the value of a field property.
	 *
	 * @param string $type supported type ares 'default' , 'current' or 'tooltip' 
	 * @param string $name name of the field 
	 * @param string $value the new value to set
	 **/
	public function setFormFieldValue($type, $name, $value) {
		if (($field = $this->pdfDocument->getField($entry)) !== null) {
			if ($type == "tooltip") {
				$offsetShift = $this->setFieldTooltip($name, $value);
			} else {
				if ($field->getCurrentValue() != '') {
					$offsetShift = $this->setFieldValue($field->getCurrentValue(), $value, $field->getType(), false);
				} else {
					$offsetShift = $this->setFieldValue($field->getNameLine(), $value, $field->getType(), true);
				}
			}
			$this->applyOffsetShiftFromObject($field->getId(), $offsetShift);
		} else {
			throw new \Exception(sprintf("PDFWriter setFormFieldValue: field %s not found", $name));
		}
	}

	private function encodeValue($str) {
		if (mb_detect_encoding($str, 'UTF-8', true) !== false) {
			$str = "\xFE\xFF" .iconv('UTF-8', 'UTF-16BE', $str);
		}
		return $this->converter->encode($str);
	}

	private function setFieldValue($line, $value, $fieldType, $append) {
		$curLine = $this->pdfDocument->getEntry($line);
		$oldLen = strlen($curLine);
		if ($append) {
			if ($fieldType == 'Btn') {
				$curLine .= ' /V /'.$value;
			} else {
				$curLine .= ' /V <'.$this->encodeValue($value).'>';
			}
		} else {
			if (preg_match('#/V\s?[<(/]([^>)]*)[>)]?#', $curLine, $a, PREG_OFFSET_CAPTURE)) {
				$len = strlen($a[1][0]);
				$pos1 = $a[1][1];
				$pos2 = $pos1 + $len;
				$curLine = substr($curLine, 0, $pos1 - 1).'<'.$this->encodeValue($value).'>'.substr($curLine, $pos2 + 1);
			}
			else {
				throw new \Exception('PDFWriter setFieldValue: /V not found');
			}
		}
		$newLen = strlen($curLine);
		$shift = $newLen - $oldLen;
		$this->pdfDocument->addToGlobalShift($shift);
		$this->pdfDocument->setEntry($line, $curLine);
		return $shift;
	}

	/**
	 * Sets the tooltip value of a field property.
	 * 
	 * @param string $name name of the field 
	 * @param string $value the new value to set
	 * @return int the size variation of the PDF
	 **/
	private function setFieldTooltip($name, $value) {
		$offsetShift = 0;
		if (($field = $this->pdfDocument->getField($name)) !== null) {
			$tooltipLine = $field->getTooltip();
			if ($tooltipLine) {
				$curLine = $this->pdfDocument->getEntry($tooltipLine);
				$oldLen = strlen($curLine);
				$fieldRegexp = '/^\/(\w+)\s?(\<|\(|\/)([^\)\>]*)(\)|\>)?/';
				if (preg_match($fieldRegexp, $curLine)) {
					$curLine = preg_replace_callback(
						$fieldRegexp,
						function($matches) use ($value) {
							array_shift($matches);
							if (($value != '') && ($matches[1] == "<")) {
								$matches[2] = $this->encodeValue($value); 
							} else {
								$matches[2] = $value;
							}
							$valueCode = $matches[0];
							$matches[0] = "/" . $valueCode . " ";
							return implode("", $matches);
						},
						$curLine
					);
				}
				$newLen = strlen($curLine);
				$shift = $newLen - $oldLen;
				$this->pdfDocument->addToGlobalShift($shift);
				$this->pdfDocument->setEntry($tooltipLine, $curLine);
				$offsetShift = $shift;
			}
		} else {
			throw new \Exception(sprintf("PDFWriter: setFieldTooltip failed as the field %s does not exist", $name));
		}
		return $offsetShift;
	}

	/**
	 * Applies a shift offset from the object whose id is given as parameter
	 * 
	 * @param int $objectId the id whose size shift has changed
	 * @param int $offsetShift the shift value to use
	 */
	private function applyOffsetShiftFromObject($objectId, $offsetShift) {
		$this->applyOffsetShift(
			$this->pdfDocument->getPosition($objectId) + 1,
			$offsetShift
		);
	}

	/**
	 * Applies a shift offset starting at the index to the shifts array
	 * 
	 * @param int $from  the index to start apply the shift
	 * @param int $shift the shift value to use
	 */
	private function applyOffsetShift($from, $shift) {
		$offsets = $this->pdfDocument->getShifts();
		foreach (array_keys($offsets) as $key) {
			if ($key >= $from) {
				$offset = $offsets[$key] + $shift;
				$this->pdfDocument->setOffset($key, $offset);
			}
		}
	}

}

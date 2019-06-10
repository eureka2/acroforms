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

namespace acroforms\Manager;

use acroforms\Model\CrossReference;

/**
 * Class to manage the cross-reference of a PDF document.
 */
class CrossReferenceManager {

	private $crossReference;
	private $pdfDocument;

	private $safeMode = false;				// if set, ignore previous offsets do no calculations for the new xref table, seek pos directly in file
	private $checkMode = false;				// Use this to track offset calculations errors in corrupteds pdfs files for sample
	private $haltMode = true; 				// if true, stops when offset error is encountered

	public function __construct(CrossReference $crossReference) {
		$this->crossReference = $crossReference;
		$this->pdfDocument = $crossReference->getDocument();
	}

	public function isSafeMode() {
	    return $this->safeMode;
	}

	public function isCheckMode() {
	    return $this->checkMode;
	}

	public function isHaltMode() {
	    return $this->haltMode;
	}

	public function setSafeMode($safeMode) {
	    $this->safeMode = $safeMode;
	}

	public function setCheckMode($checkMode) {
	    $this->checkMode = $checkMode;
	}

	public function setHaltMode($haltMode) {
	    $this->haltMode = $haltMode;
	}

	/**
	 * Gets the cross-reference table
	 *
	 * @return \acroforms\Model\CrossReference the CrossReference object that represents the xref table
	 */
	private function getCrossReferenceTable() {
		return $this->crossReference;
	}

	/**
	 * Gets the offset of the cross-reference table in the PDF file
	 *
	 * @return int the offset of the cross-reference table 
	 */
	private function getCrossReferenceStart() {
		return $this->crossReference->getStartPointer(); 
	}

	/**
	 * Gets the line of the PDF entries where the offset of the cross-reference table is stored
	 *
	 * @return int the wished line number
	 */
	private function getCrossReferenceStartLine() {
		return $this->crossReference->getStartLine(); 
	}

	/**
	 * Calculates the offset of the cross-reference table
	 *
	 * @return int the wished xrefstart offset value
	 */
	private function getCrossReferenceStartValue() {
		return $this->getCrossReferenceStart() + $this->pdfDocument->getGlobalShift();
	}

	/**
	 * Read the offset of the cross-reference table directly from file content
	 *
	 * @return int the wished cross-reference offset value
	 */
	private function readCrossReferenceStartValue() {
		$buffer = $this->pdfDocument->getBuffer();
		$chunks = preg_split('/\bxref\b/', $buffer, -1, PREG_SPLIT_OFFSET_CAPTURE);
		return intval($chunks[1][1]) - 4;
	}

	/**
	 * Calculates the new offset/xref for the given objectId by applying the offset shift due to value changes
	 *
	 * @param int $objectId an object id, a integer value starting from 1
	 * @return int the wished offset
	 */
	private function getOffsetObjectValue($objectId) {
		$positions = $this->getPositionsOrdered();
		$offsets = $this->getOffsetsStartingFromZero();
		$shifts = $this->pdfDocument->getShifts();
		$p = $positions[$objectId];
		return $offsets[$p] + $shifts[$p];
	}

	/**
	 * Reads the offset of the cross-reference table directly from file content
	 *
	 * @param int $objectId an object id, a integer value starting from 1
	 * @return int the wished offset
	 */
	private function readOffsetObjectValue($objectId) {
		$buffer = $this->pdfDocument->getBuffer();
		$previousObjectFooter = '';
		$objectHeader = $previousObjectFooter.'\n'.$objectId.' 0 obj';
		$chars = preg_split('/'.$objectHeader.'/', $buffer, -1, PREG_SPLIT_OFFSET_CAPTURE);
		return intval($chars[1][1]) - strlen($objectHeader) + strlen($previousObjectFooter) + 2;
	}

	/**
	 * Updates the offset of the cross-reference table
	 */
	public function updateCrossReferenceStart() {
		$calculateXrefstartValue = (!$this->safeMode || $this->checkMode);
		$extractXrefstartValueFromFile = ($this->safeMode || $this->checkMode);
		if ($calculateXrefstartValue) {
			$xrefStartValueCalculated = $this->getCrossReferenceStartValue();
			if (!$this->safeMode) {
				$xrefStartValue = $xrefStartValueCalculated;
			}
		}
		if ($extractXrefstartValueFromFile) { 
			$xrefStartValueSafe = $this->readCrossReferenceStartValue();
			if ($this->safeMode) {
				$xrefStartValue = $xrefStartValueSafe;
			}
		} 
		if ($this->checkMode) {
			if ($xrefStartValueCalculated != $xrefStartValueSafe) {
				$xrefStartValue = $xrefStartValueSafe;
				if ($this->haltMode) {
					throw new \Exception("CrossReferenceManager: halt on error mode enabled, aborting. Use \$pdf->setMode('halt',false); to disable this mode and go further fixing corrupted pdf.");
				}
			}
		}
		$this->pdfDocument->setEntry($this->getCrossReferenceStartLine(), $xrefStartValue . "");
	}

	/**
	 * Get the offsets table (0 indexed)
	 * 
	 * @return array $offsets 
	 */
	private function getOffsetsStartingFromZero() {
		$offsets = $this->pdfDocument->getOffsets(); 
		return array_values($offsets); 
	}

	/**
	 * Sorts the position array by key
	 * 
	 * @return array $positions the ordered positions
	 */
	private function getPositionsOrdered() {
		$positions = $this->pdfDocument->getPositions();
		ksort($positions);
		return $positions;
	}

	/**
	 * Rebuilds the offsets entries of the cross-reference table
	 * 
	 */
	public function rebuildCrossReferenceTable() {
		$xLen = $this->crossReference->getCount();
		$offsets = $this->pdfDocument->getOffsets(); 
		$oLen = count($offsets);
		if ($xLen == $oLen) {
			$firstXrefEntryLine = $this->crossReference->getLine() + 3;
			$calculateOffsetValue = (!$this->safeMode || $this->checkMode);
			$extractOffsetValueFromFile = ($this->safeMode || $this->checkMode);
			for($i = 0; $i < $xLen; $i++) {
				$objectId = $i + 1;
				$offsetValue = 0;
				if ($calculateOffsetValue) {
					$offsetValueCalculated = $this->getOffsetObjectValue($objectId);
					if (!$this->safeMode) {
						$offsetValue = $offsetValueCalculated;
					}
				}
				if ($extractOffsetValueFromFile) {
					$offsetValueRead = $this->readOffsetObjectValue($objectId);	
					if ($this->safeMode) {
						$offsetValue = $offsetValueRead;
					}
				} 
				if ($this->checkMode) {
					if ($offsetValueCalculated !=  $offsetValueRead)  {
						$offsetValue = $offsetValueRead;
						if ($this->haltMode) {
							throw new \Exception(sprintf('CrossReferenceManager: wrong offset for object %s, found is "%s", must be %s', $objectId, $offsetValueRead, $offsetValueCalculated));
						}
					}
				}
				$this->pdfDocument->setEntry($firstXrefEntryLine + $i, sprintf('%010d 00000 n ', $offsetValue));
			}
		} else { 
			throw new \Exception(sprintf("CrossReferenceManager: number of objects (%s) differs with number of references (%s), pdf cross-reference table is corrupted", $oLen, $xLen)); 
		}
	}

}

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

namespace acroforms\Model;

use acroforms\Filter\FilterFactory;
use acroforms\Utils\PDFtkBridge;

/**
 * Class representing the lines of a PDF file.
 */
class PDFDocument extends BaseDocument {

	private $needAppearancesTrue = false; 
	private $entries = [];
	private $fields = [];
	private $metadata = [];
	private $crossReference = null;
	private $positions = []; 				// stores what object id is at a given position n ($positions[n]=<obj_id>)
	private $offsets = [];					// offsets for objects, index is the object's id, starting at 1
	private $shifts = [];					// shifts of objects in the order positions they appear in the pdf, starting at 0.
	private $globalShift = 0;				// overall size of the file that changes as the size of the object values changes

	private $converter = null;

	public function __construct() {
		$this->converter = FilterFactory::getFilter("ASCIIHexDecode");
	}

	/**
	 * Loads the content of a PDF file
	 *
	 * @param string $filename the filename of the file
	 **/
	public function load($filename, $pdftk = "") {
		parent::load($filename);
		if ($pdftk != "" && $this->isLinearized()) {
			$this->unLinearize($filename, $pdftk);
		}
		$this->check();
	}

	/**
	 * Loads the content of a string
	 *
	 * @param string $content the content
	 **/
	public function setContent($content) {
		parent::setContent($content);
		$this->check();
	}

	protected function check() {
		if ($this->hasObjectStreams()) {
			throw new \Exception('PDFDocument: Object streams are not supported');
		}
		if ($this->isLinearized()) {
			throw new \Exception('PDFDocument: Fast Web View mode is not supported');
		}
		if ($this->hasIncrementalUpdates()) {
			throw new \Exception('PDFDocument: Incremental updates are not supported');
		}
		$this->needAppearancesTrue = (strpos($this->content, '/NeedAppearances true') !== false);
		$this->entries = explode("\n", $this->content);
	}

	protected function unLinearize($filename, $cmd) {
		$err = '';
		if (PDFtkBridge::is_windows()) {
			$cmd = sprintf('cd %s && %s', escapeshellarg(dirname($cmd)), basename($cmd));
		}
		$temp = tempnam(sys_get_temp_dir(), 'acroform_');
		if ($temp === false) {
			throw new \Exception("PDFDocument: pdftk failed because it's impossible to create a temporary file");
		} else {
			$pdfOut = $temp.'.pdf';
			rename($temp, $pdfOut);
			$cmdline = sprintf('%s "%s" output "%s"', $cmd, $filename, $pdfOut);
			$ret = PDFtkBridge::run($cmdline, $pdfOut);
			if ($ret["success"]) {
				parent::load($ret["output"]);
			}
			@unlink($ret["output"]);
		}
	}

	public function isLinearized() {
		$start = substr($this->content, 0, 2048);
		return strpos($start, '/Linearized') !== false;
	}

	public function hasObjectStreams() {
		$start = substr($this->content, 0, 2048);
		return strpos($start, '/ObjStm') !== false;
	}

	public function hasIncrementalUpdates() {
		$end = substr($this->content, -512);
		return strpos($end, '/Prev') !== false;
	}

	public function isNeedAppearancesTrue() {
		return $this->needAppearancesTrue;
	}

	public function getEntries() {
		return $this->entries;
	}

	public function getEntriesCount() {
		return count($this->entries);
	}

	public function getEntry($line) {
		return $this->entries[$line];
	}

	public function setEntry($line, $entry) {
		$this->entries[$line] = $entry;
	}

	public function getField($fieldname) {
		return  isset($this->fields[$fieldname]) ?
				$this->fields[$fieldname]:
				null;
	}

	public function setField($fieldname, $field) {
		$this->fields[$fieldname] = $field;
	}

	public function getFields() {
		return $this->fields;
	}

	public function getMetadata() {
		return $this->metadata;
	}

	public function addMeta($key, $value) {
		$this->metadata[$key] = $value;
	}

	public function getCrossReference() {
		return $this->crossReference;
	}

	public function setCrossReference($crossReference) {
		$this->crossReference = $crossReference;
	}

	public function getPositions() {
		return $this->positions;
	}

	public function setPosition($objectId, $value) {
		$this->positions[$objectId] = $value;
	}

	public function getPosition($objectId) {
		return $this->positions[$objectId];
	}

	public function getOffsets() {
		return $this->offsets;
	}

	public function setOffset($objectId, $value) {
		$this->offsets[$objectId] = $value;
	}

	public function getShifts() {
		return $this->shifts;
	}

	public function setShift($objectId, $value) {
		$this->shifts[$objectId] = $value;
	}

	public function getGlobalShift() {
	    return $this->globalShift;
	}

	public function setGlobalShift($globalShift) {
		$this->globalShift = $globalShift;
	}

	public function addToGlobalShift($shift) {
		$this->globalShift += $shift;
	}

	/**
	 * Get current pdf content 
	 *
	 * @return string the pdf content
	 **/
	public function getBuffer() {
		return implode("\n", $this->entries);
	}

}

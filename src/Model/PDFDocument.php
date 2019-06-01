<?php

namespace acroforms\Model;

use acroforms\Filter\FilterFactory;

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
	public function load($filename) {
		parent::load($filename);
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

	private function check() {
		$start = substr($this->content, 0, 2048);
		if (strpos($start, '/ObjStm') !== false) {
			throw new \Exception('PDFDocument: Object streams are not supported');
		}
		if (strpos($start, '/Linearized') !== false) {
			throw new \Exception('PDFDocument: Fast Web View mode is not supported');
		}
		$end = substr($this->content, -512);
		if (strpos($end, '/Prev') !== false) {
			throw new \Exception('PDFDocument: Incremental updates are not supported');
		}
		$this->needAppearancesTrue = (strpos($this->content, '/NeedAppearances true') !== false);
		$this->entries = explode("\n", $this->content);
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

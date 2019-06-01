<?php

namespace acroforms\Model;

use acroforms\Manager\CrossReferenceManager;

/**
 * Class representing the XRef table of the COS structure of a PDF file.
 */
class CrossReference {

	private $document = null;
	private $manager = null;
	private $line = 0;
	private $startPointer = 0;
	private $startValue = 0;
	private $startLine = 0;
	private $count = 0;
	private $entries = [];

	public function __construct(PDFDocument $document) {
		$this->document = $document;
		$this->manager = new CrossReferenceManager($this);
	}

	public function getDocument() {
	    return $this->document;
	}

	public function getManager() {
	    return $this->manager;
	}

	public function getLine() {
		return $this->line;
	}

	public function getStartPointer() {
		return $this->startPointer;
	}

	public function getStart() {
		return $this->getStartPointer();
	}

	public function getStartValue() {
		return $this->startValue;
	}

	public function getStartLine() {
		return $this->startLine;
	}

	public function getCount() {
		return $this->count;
	}

	public function setLine($line) {
		$this->line = $line;
	}

	public function setStartPointer($startPointer) {
		$this->startPointer = $startPointer;
	}

	public function setStartValue($startValue) {
		$this->startValue = $startValue;
	}

	public function setStartLine($startLine) {
		$this->startLine = $startLine;
	}

	public function setCount($count) {
		$this->count = $count;
	}

	public function getEntries() {
		return $this->entries;
	}

	public function setEntries($entries) {
		$this->entries = $entries;
	}

	public function setEntry($entry, $value) {
		$this->entries[$entry] = $value;
	}

}

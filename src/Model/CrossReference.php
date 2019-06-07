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

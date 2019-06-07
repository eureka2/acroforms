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

/**
 * Class representing the lines of a FDF file.
 */
class FDFDocument extends BaseDocument {

	private $pdfdocument = null;
	private $fields = [];
	private $buttons = [];
	private $parseNeeded = true;

	public function __construct(PDFDocument $pdfdocument) {
		$this->pdfdocument = $pdfdocument;
	}

	/**
	 * Loads form data
	 *
	 * @param array $data the content
	 **/
	public function setFormData($data) {
		$this->fields = $data['text'];
		$this->buttons = $data['button'];
		$this->parseNeeded = false;
	}

	public function getPdfdocument() {
		return $this->pdfdocument;
	}

	public function getFields() {
		return $this->fields;
	}

	public function setFields(&$fields) {
		$this->fields = $fields;
	}

	public function getButtons() {
		return $this->buttons;
	}

	public function setButtons(&$buttons) {
		$this->buttons = $buttons;
	}

	public function isParseNeeded() {
		return $this->parseNeeded;
	}

	public function setParseNeeded($parseNeeded) {
		$this->parseNeeded = $parseNeeded;
	}

}

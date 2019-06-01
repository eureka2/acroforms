<?php

namespace acroforms\Model;

use acroforms\Utils\StringToolBox;

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
	 * @param string $data the content
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

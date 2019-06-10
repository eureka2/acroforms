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

namespace acroforms\Model;

/**
 * Class representing a field in the COS structure of a PDF file.
 */
class AcroField {

	private $id = 0;
	private $line = -1; // pointer to the first line of the field definition in the entries table of the PDF file
	private $name = '';
	private $nameLine = ''; // pointer to the name in the entries table of the PDF file
	private $fullName = '';
	private $type = ''; // Tx, Btn, Ch or Sig
	private $flag = 0;
	private $maxLen = 0;
	private $kids = []; // array of pointers of the kids in the entries table of the PDF file
	private $tooltip = 0; // pointer to the tooltip in the entries table of the PDF file
	private $defaultValue = 0; // pointer to the default value in the entries table of the PDF file
	private $currentValue = 0; // pointer to the current value in the entries table of the PDF file
	private $options = [];
	private $topIndex = 0;
	private $selecteds = [];

	public function __construct($id) {
		$this->setId($id);
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getLine() {
		return $this->line;
	}

	public function setLine($line) {
		$this->line = $line;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getNameLine() {
		return $this->nameLine;
	}

	public function setNameLine($nameLine) {
		$this->nameLine = $nameLine;
	}

	public function getFullName() {
		return $this->fullName;
	}

	public function setFullName($fullName) {
		$this->fullName = $fullName;
	}

	public function getType() {
		return $this->type;
	}

	public function setType($type) {
		$this->type = $type;
	}

	public function getFlag() {
		return $this->flag;
	}

	public function setFlag($flag) {
		$this->flag = $flag;
	}

	public function isReadOnly() {
		return $this->flag & 1; // bit 1 is set
	}

	public function isRequired() {
		return $this->flag & (1 << (2 - 1)); // bit 2 is set
	}

	public function isExportable() {
		return ! ($this->flag & (1 << (3 - 1))); // bit 3 is not set
	}

	public function isTextField() {
		return $this->type == 'Tx';
	}

	public function isChoice() {
		return $this->type == 'Ch';
	}

	public function isButton() {
		return $this->type == 'Btn';
	}

	public function isSignatureField() {
		return $this->type == 'Sig';
	}

	public function isPushButton() {
		return $this->type == 'Btn' 
			&& ($this->flag & (1 << (17 - 1))); // bit 17 is set
	}

	public function isCheckBox() {
		return $this->type == 'Btn' 
			&& ! ($this->flag & (1 << (16 - 1))) // bit 16 is not set
			&& ! ($this->flag & (1 << (17 - 1))); // bit 17 is not set
	}

	public function isRadio() {
		return $this->type == 'Btn' 
			&& ($this->flag & (1 << (16 - 1))); // bit 16 is set
	}

	public function isRadioInUnison() {
		return $this->type == 'Btn' 
			&& ($this->flag & (1 << (26 - 1))); // bit 26 is set
	}

	public function isMultiline() {
		return $this->type == 'Tx'
			&& ($this->flag & (1 << (13 - 1))); // bit 13 is set
	}

	public function isPassword() {
		return $this->type == 'Tx'
			&& ($this->flag & (1 << (14 - 1))); // bit 14 is set
	}

	public function isFileSelect() {
		return $this->type == 'Tx'
			&& ($this->flag & (1 << (21 - 1))); // bit 21 is set
	}

	public function canSpellCheck() {
		return ($this->type == 'Tx' || $this->type == 'Ch')
			&& ! ($this->flag & (1 << (23 - 1))); // bit 23 is not set
	}

	public function canScroll() {
		return $this->type == 'Tx'
			&& ! ($this->flag & (1 << (24 - 1))); // bit 24 is not set
	}

	public function isComb() {
		return $this->type == 'Tx'
			&& ($this->flag & (1 << (25 - 1))); // bit 25 is set
	}

	public function isRichText() {
		return $this->type == 'Tx'
			&& ($this->flag & (1 << (26 - 1))); // bit 26 is set
	}

	public function isComboBox() {
		return $this->type == 'Ch'
			&& ($this->flag & (1 << (18 - 1))); // bit 18 is set
	}

	public function isEditableComboBox() {
		return $this->type == 'Ch'
			&& ($this->flag & (1 << (18 - 1))) // bit 18 is set
			&& ($this->flag & (1 << (19 - 1))); // bit 19 is set
	}

	public function isListBox() {
		return $this->type == 'Ch'
			&& ! ($this->flag & (1 << (18 - 1))); // bit 18 is not set
	}

	public function isSorted() {
		return $this->type == 'Ch'
			&& ($this->flag & (1 << (20 - 1))); // bit 20 is set
	}

	public function isMultiSelect() {
		return $this->type == 'Ch'
			&& ($this->flag & (1 << (20 - 1))); // bit 20 is set
	}

	public function commitOnSelChange() {
		return $this->type == 'Ch'
			&& ($this->flag & (1 << (27 - 1))); // bit 27 is set
	}

	public function getMaxLen() {
		return $this->maxLen;
	}

	public function setMaxLen($maxLen) {
		$this->maxLen = $maxLen;
	}

	public function getTooltip() {
		return $this->tooltip;
	}

	public function setTooltip($tooltip) {
		$this->tooltip = $tooltip;
	}

	public function getDefaultValue() {
		return $this->defaultValue;
	}

	public function setDefaultValue($defaultValue) {
		$this->defaultValue = $defaultValue;
	}

	public function getCurrentValue() {
		return $this->currentValue;
	}

	public function setCurrentValue($currentValue) {
		$this->currentValue = $currentValue;
	}

	public function getOptions() {
		return $this->options;
	}

	public function setOptions(&$options) {
		$this->options = $options;
	}

	public function getKids() {
	    return $this->kids;
	}

	public function setKids(&$kids) {
	    $this->kids = $kids;
	}

	public function getTopIndex() {
		return $this->topIndex;
	}

	public function setTopIndex($topIndex) {
		$this->topIndex = $topIndex;
	}

	public function getSelecteds() {
		return $this->selecteds;
	}

	public function setSelecteds($selecteds) {
		$this->selecteds = $selecteds;
	}

}

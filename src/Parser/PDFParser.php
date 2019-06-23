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

namespace acroforms\Parser;

use acroforms\Filter\FilterFactory;
use acroforms\Model\AcroField;
use acroforms\Model\CrossReference;
use acroforms\Model\PDFDocument;
use acroforms\Utils\StringToolBox;

/**
 *
 * Class to parse the lines of a PDF file.
 *
 */
class PDFParser {
	const METAS = [ "Title", "Author", "Subject", "Keywords", "Creator", "Producer", "CreationDate", "ModDate" ];

	private $pdfDocument = null; 

	private $converter = null;

	private $linesCount;
	private $pointers = [];
	private $objectsLine = []; // line of objects by object id
	private $linesObject = []; // Object in line
	private $objects = []; // objects by object id

	private $objectPosition = 0; //Position of an object, in the order it is declared in the pdf file

	public function __construct(PDFDocument $pdfDocument) {
		$this->pdfDocument = $pdfDocument;
		$this->converter = FilterFactory::getFilter("ASCIIHexDecode");
	}

	/**
	 * Decodes a PDF value according to the encoding
	 * 
	 * @param string $encoding  the encoding to use for decoding the value, only 'hex' is supported
	 * @param string $value a value to decode
	 * @return string the value decoded
	 */
	private function decodeValue($encoding, $value) {
		if ($encoding == "hex") {
			$value = $this->converter->decode($value);
		}
		return $value;
	}

	/**
	 * Parses the lines entries of a PDF 
	 */
	public function parse() {
		$match = [];
		$this->linesCount = $this->pdfDocument->getEntriesCount();
		for ($i = 0, $p = 0; $i < $this->linesCount; $i++ ) {
			$entry = $this->pdfDocument->getEntry($i);
			$this->pointers[$i] = $p;
			$p += strlen($entry) + 1;
			if (preg_match("/^(\d+) (\d+) obj/", $entry, $match)) {
				$this->objectsLine[$match[1]] = $i;
				$this->linesObject[$i] = $match[1];
			}
		}
		$this->parseObjects();
		$this->parseCrossReference();
		$this->parseTrailer();
		$this->parseStartxref();
	}

	private function parseObjects() {
		$root = null;
		$from = 0;
		while ( $from < $this->linesCount ) {
			$field = $this->parseObject($from);
			if ($field !== null) {
				$objectId = $field->getId();
				$this->objects[$objectId] = $field;
				$name = $field->getName();
				if ($name != '' && preg_match("/^topmostSubform/", $name)) {
					if ($root !== null) {
						throw new \Exception("Ambiguous document objects root");
					}
					$root = $field;
				}
			}
		}
		if ($root === null) {
			throw new \Exception("Document objects root not found");
		}
		$this->updateDocumentFields($root);
	}

	private function updateDocumentFields(&$object, $name = "") {
		$fieldname = $object->getName();
		if ($name !== '') {
			$fieldname = $name . "." . $fieldname;
		}
		if ($object->getType() != '' // it's a field
			&& $object->getName() != '' // with a name
			&& !$object->isPushButton()) { // and not a push button
			if ($object->isButton()) {
				$this->parseFieldStates($object);
			}
			$object->setFullName($fieldname);
			$this->pdfDocument->setField($fieldname, $object);
		}
		$kids = $object->getKids();
		foreach ($kids as $kid) {
			$objectId = $this->linesObject[$kid];
			$this->updateDocumentFields($this->objects[$objectId], $fieldname);
		}
	}

	private function parseObject(&$from) {
		$match = [];
		while ($from < $this->linesCount) { 
			$entry = $this->pdfDocument->getEntry($from);
			if (preg_match("/^(\d+) (\d+) obj/", $entry, $match)) {
				break;
			}
			$from++;
		}
		$field = null;
		if ($from < $this->linesCount) {
			$defaultMaxLen = 0; //No limit
			$defaultTooltipLine = 0; // TooltipLine is optional as it may not be defined
			$removed = false;
			$objectId = intval($match[1]);
			$this->pdfDocument->setOffset($objectId, $this->pointers[$from]);
			$this->pdfDocument->setPosition($objectId, $this->objectPosition);
			$this->pdfDocument->setShift($this->objectPosition, 0);
			$this->objectPosition++;
			$field = new AcroField(intval($objectId));
			$field->setLine($from);
			$field->setMaxLen($defaultMaxLen);
			$field->setTooltipLine($defaultTooltipLine);
			$entry = $this->pdfDocument->getEntry(++$from);
			while ( $from < $this->linesCount && ! preg_match("/endobj/", $entry)) {
				$removed = $removed || ! $this->parseFieldProperty($entry, $from, $field);
				$entry = $this->pdfDocument->getEntry(++$from);
			}
			if ($removed) {
				$field = null;
			}
		}
		return $field;
	}

	private function parseFieldProperty($entry, $from, &$field) {
		$removed = false;
		$match = [];
		foreach (self::METAS as $meta) {
			if (preg_match("/\/" . $meta . "\s*\(([^\)]+)\)/", $entry, $match)) {
				$this->pdfDocument->addMeta($meta, $match[1]);
			} elseif (preg_match("/\/" . $meta . "\s*\<([^\>]+)\>/", $entry, $match)) {
				$this->pdfDocument->addMeta($meta, $this->decodeValue("hex", $match[1]));
			}
		}
		if (preg_match("/\/Trapped\s*\/(.+)$/",$entry, $match)) {
			$this->pdfDocument->addMeta("Trapped", strtolower($match[1]));
		} elseif (preg_match("/^\/T\s?\((.+)\)\s*$/", $entry, $match)) {
			$this->parseName($from, $match, $field);
		} elseif (preg_match("/^\/(V|DV|TU)\s*\((.+)\)\s*$/", StringToolBox::protectParentheses($entry), $match)) {
			$this->parseValue($from, $match, $field);
		} elseif (preg_match("/^\/(V|DV|TU)\s*\<(.+)\>\s*$/", StringToolBox::protectHexDelimiters($entry), $match)) {
			$this->parseHexValue($from, $match, $field);
		} elseif (preg_match("/^\/(V|DV|TU)\s*\/(.*)$/", $entry, $match)) {
			$this->parseValue($from, $match, $field);
		} elseif (preg_match("/^\/MaxLen\s+(\d+)/", $entry, $match)) {
			$field->setMaxLen(intval($match[1]));
		} elseif (preg_match("/^\/removed\s+true/", $entry)) {
			$removed = true;
		} elseif (preg_match("/^\/FT\s+\/(.+)$/", $entry, $match)) {
			$field->setType($match[1]); // Tx, Btn, Ch or Sig
		} elseif (preg_match("/^\/Ff\s+(\d+)/", $entry, $match)) {
			$field->setFlag(intval($match[1]));
		} elseif (preg_match("/^\/Opt\s+\[(.+)\]\s*$/", $entry, $match)) {
			$this->parseOptions($match, $field);
		} elseif (preg_match("/^\/Kids\s+\[(.+)\]\s*$/", $entry, $match)) {
			$this->parseKids($match, $field);
		} elseif (preg_match("/^\/TI\s+\/(.+)$/", $entry, $match)) {
			$field->setTopIndex($match[1]);
		} elseif (preg_match("/^\/I\s+\[(.+)\]\s*$/", $entry, $match)) {
			$field->setSelecteds(array_map(function ($sel) {
				return intval($sel);
			}, preg_split("/\s+/", trim($match[1]))));
		}
		if (substr($entry, 0, 7) == '/Fields' && !$this->pdfDocument->isNeedAppearancesTrue()) {
			$this->pdfDocument->setEntry($from, '/NeedAppearances true ' . $entry);
		}
		return !$removed;
	}

	private function parseName($from, $match, &$field) {
		$name = stripcslashes($match[1]);
		$field->setName($name);
		$field->setNameLine($from);
	}

	private function parseValue($from, $match, &$field) {
		$value = StringToolBox::unProtectParentheses($match[2]);
		if ($match[1] == "TU") {
			$field->setTooltipLine($from);
			$field->setTooltip($value);
		} elseif ($match[1] == "DV") {
			$field->setDefaultValueLine($from);
			$field->setDefaultValue($value);
		} else {
			$field->setCurrentValueLine($from);
			$field->setCurrentValue($value);
		}
	}

	private function parseHexValue($from, $match, &$field) {
		$value = StringToolBox::unProtectHexDelimiters($match[2]);
		$value = $this->decodeValue('hex', $value);
		if ($match[1] == "TU") {
			$field->setTooltipLine($from);
			$field->setTooltip($value);
		} elseif ($match[1] == "DV") {
			$field->setDefaultValueLine($from);
			$field->setDefaultValue($value);
		} else {
			$field->setCurrentValueLine($from);
			$field->setCurrentValue($value);
		}
	}

	private function parseFieldStates(&$field) {
		$kids = $field->getKids();
		array_unshift($kids, $field->getLine());
		$beg = chr(254);
		$end = chr(255);
		$states = [];
		foreach($kids as $kid) {
			$from = $kid + 1;
			$obj = "";
			$entry = $this->pdfDocument->getEntry($from);
			while ( $from < $this->linesCount && ! preg_match("/endobj/", $entry)) {
				$obj .= $entry;
				$entry = $this->pdfDocument->getEntry(++$from);
			}
			$obj = str_replace(["<<", ">>"], [$beg, $end], $obj); 
			if (preg_match("#(" . $beg . "|" . $end. ")\s*/N\s+" . $beg . "\s*/([^\s]+)" . "#", $obj, $match)) {
				$states[$match[2]] = true;
			}
			if (preg_match("|/AS\s+/([^\s\/]+)|", $obj, $match)) {
				$states[$match[1]] = true;
			}
		}
		$states = array_keys($states);
		if (!empty($states)) {
			$options = $field->getOptions();
			foreach ($states as $state) {
				$options[$state] = $state;
			}
			$field->setOptions($options);
		}
	}

	private function parseKids($match, &$field) {
		$array = $match[1];
		if (preg_match_all("/(\d+)\s+\d+\s+R/", $array, $match)) {
			$array = $match[1];
			$kids = [];
			foreach ($array as $objectId) {
				$kids[] = $this->objectsLine[$objectId];
			}
			$field->setKids($kids);
		}
	}

	private function parseOptions($match, &$field) {
		$array = $match[1];
		if (preg_match_all("/\[([^\]]+)\]/", $array, $match)) {
			$array = $match[1];
			$options = [];
			foreach ($array as $option) {
				if (preg_match("/^\s*\(([^\)]+)\)\s*\(([^\)]+)\)\s*$/", $option, $match)) {
					$options[$match[1]] = $match[2];
				} elseif (preg_match("/^\s*\(([^\)]+)\)\s*$/", $option, $match)) {
					$options[$match[1]] = $match[1];
				} 
			}
			if (!empty($options)) {
				$field->setOptions($options);
			}
		}
	}

	private function parseCrossReference() {
		$from = $this->linesCount - 1;
		while ($from >= 0) { 
			$entry = $this->pdfDocument->getEntry($from);
			if (preg_match("/\bxref\b/", $entry)) {
				break;
			}
			 $from--;
		}
		if ($from < 0) {
			throw new \Exception("PDFParser: xref tag not found");
		}
		if ($from == $this->linesCount - 1) {
			throw new \Exception(sprintf("PDFParser: PDF document is corruptd, last entry found : %s", $entry));
		}
		$refsCount = 0;
		$xrefTable = 1;
		$crossReference = new CrossReference($this->pdfDocument);
		$crossReference->setLine($from);
		$startPointer = $this->pointers[$from] + strpos($entry, "xref");
		$crossReference->setStartPointer($startPointer);
		$entry = $this->pdfDocument->getEntry(++$from);
		while ( $from < $this->linesCount && ! preg_match("/^trailer/", $entry)) {
			$match = [];
			$xrefTable++;
			switch($xrefTable) {
				case 2:
					if (preg_match("/^(\d+) (\d+)/",$entry, $match)) {
						$refsCount = intval($match[2]);
						$crossReference->setCount($refsCount - 1);
					}
					break;
				case 3:
					break;
				default:
					if ($refsCount > 0) {
						$xref = $xrefTable - 3;
						if ($refsCount == 1) {
							throw new \Exception("PDFParser: xrefTable length corrupted?: Trailer not found at expected!");
						} else {
							$crossReference->setEntry($xref, $entry);
						}
						$refsCount--;
					}
			}
			$entry = $this->pdfDocument->getEntry(++$from);
		}
		$this->pdfDocument->setCrossReference($crossReference);
	}

	private function parseTrailer() {
		$from = $this->linesCount - 1;
		while ($from >= 0) { 
			$entry = $this->pdfDocument->getEntry($from);
			if (preg_match("/^trailer/", $entry)) {
				break;
			}
			 $from--;
		}
		if ($from < 0) {
			throw new \Exception("PDFParser: trailer tag not found");
		}
		if ($from == $this->linesCount - 1) {
			throw new \Exception(sprintf("PDFParser: PDF document is corruptd, last entry found : %s", $entry));
		}
		$inIDDefinition = false;
		$isSingleLineIDDefinition = false;
		$isMultiLineIDDefinition = false;
		$oid = '';
		$entry = $this->pdfDocument->getEntry(++$from);
		while ( $from < $this->linesCount && ! preg_match("/^startxref/", $entry)) {
			$match = [];
			if (!$inIDDefinition || preg_match("/^\/(Size|Root|Info|ID|DocChecksum)/", $entry, $match)) {
				if (preg_match("/\/Size (\d+)/", $entry, $match)) {
					$this->pdfDocument->addMeta("size", $match[1]);
				}
				if (preg_match("/^\/ID\s*\[\s*<([\da-fA-F]+)/", $entry, $match)) {
					$oid = $match[1];
					$inIDDefinition = true;
					if (preg_match("/\>\s?\</", $entry, $match)) {
						$isSingleLineIDDefinition = true;
					}
				}
				if ($inIDDefinition) {
					if ($isSingleLineIDDefinition || $isMultiLineIDDefinition) {
						if (preg_match("/([\da-fA-F]+)>.*$/", $entry, $match)) {
							$this->pdfDocument->addMeta("ID", array($oid, $match[1]));
							$inIDDefinition = false;
						} else {
							throw new \Exception("PDFParser: trailer corrupted?; ID chunk two can not be decoded ");
						}
					} else { 
						$isMultiLineIDDefinition = true;
					}
				}
				if (preg_match("/^\/DocChecksum \/([\da-fA-F]+)/", $entry, $match)) {
					$this->pdfDocument->addMeta("checksum", $match[1]);
				}
			}
			$entry = $this->pdfDocument->getEntry(++$from);
		}
	}

	private function parseStartxref() {
		$match = [];
		$from = $this->linesCount - 1;
		while ($from >= 0) { 
			$entry = $this->pdfDocument->getEntry($from);
			if (preg_match("/^startxref/", $entry)) {
				break;
			}
			 $from--;
		}
		if ($from < 0) {
			throw new \Exception("PDFParser: startxref tag not found");
		}
		if ($from == $this->linesCount - 1) {
			throw new \Exception(sprintf("PDFParser: PDF document is corruptd, last entry found : %s", $entry));
		}
		$entry = $this->pdfDocument->getEntry(++$from);
		if (preg_match("/^(\d+)/", $entry, $match)) {
			$this->pdfDocument->getCrossReference()->setStartValue(intval($match[1]));
			$this->pdfDocument->getCrossReference()->setStartLine($from);
		} else {
			throw new \Exception(sprintf("PDFParser: startxref value expected, found : %s", $entry));
		}
	}

}

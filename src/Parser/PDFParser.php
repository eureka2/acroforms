<?php

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
	private $entry = "";
	private $counter = 0;
	private $pointer = 0;
	private $objects = []; // name and parent of objects by object id

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
		$crossReference = null;
		$this->counter = 0;
		$this->pointer = 0;
		$this->linesCount = $this->pdfDocument->getEntriesCount();
		$objectId = 0; 
		$field = null;
		$parentId = 0;
		$fieldtype = '';
		$removed = false;
		$name = '';
		$value = '';
		$defaultMaxLen = 0;
		$defaultTooltipLine = 0;
		$xrefTable = 0;
		$refsCount = 0;
		$trailerPart = 0;
		$inIDDefinition = false;
		$isSingleLineIDDefinition = false;
		$isMultiLineIDDefinition = false;
		$oid = '';

		while ( $this->counter < $this->linesCount ) {
			$this->entry = $this->pdfDocument->getEntry($this->counter);
			if ($xrefTable == 0) {
				if (preg_match("/^(\d+) (\d+) obj/", $this->entry, $match)) {
					$objectId = intval($match[1]);
					$this->pdfDocument->setOffset($objectId, $this->pointer);
					$this->pdfDocument->setPosition($objectId, $this->objectPosition);
					$this->pdfDocument->setShift($this->objectPosition, 0);
					$this->objectPosition++;
					$field = new AcroField(intval($objectId));
					$field->setMaxLen($defaultMaxLen);
					$field->setTooltip($defaultTooltipLine);
				} else { 
					if ($objectId > 0) {
						if (preg_match("/endobj/", $this->entry, $match)) {
							if ($fieldtype != '' // it's a field
								&& $name != '' // with a name
								&& !$removed // not removed 
								&& !$field->isPushButton()) { // and not a push button
								$name = preg_replace("/\[\d+\]$/", "", $name);
								$this->pdfDocument->setField($name, $field);
							}
							$field = null;
							$objectId = 0;
							$parentId = 0;
							$fieldtype = '';
							$removed = false;
							$name = '';
							$value = '';
							$maxLen = 0;
						} else {
							foreach (self::METAS as $meta) {
								if (preg_match("/\/" . $meta . "\s*\(([^\)]+)\)/", $this->entry, $values)) {
									$this->pdfDocument->addMeta($meta, $values[1]);
								} elseif (preg_match("/\/" . $meta . "\s*\<([^\>]+)\>/", $this->entry, $values)) {
									$this->pdfDocument->addMeta($meta, $this->decodeValue("hex", $values[1]));
								}
							}
							if (preg_match("/\/Trapped\s*\/(.+)$/",$this->entry, $values)) {
								$this->pdfDocument->addMeta("Trapped", strtolower($values[1]));
							}
							if ($name == "" && preg_match("/^\/T\s?\((.+)\)\s*$/", StringToolBox::protectParentheses($this->entry), $match)) {
								$name = StringToolBox::unProtectParentheses($match[1]);
								$field->setName($name);
								if ($field->getFullName() != '') {
									$field->setFullName($field->getFullName() . "." . $name);
								} else {
									$field->setFullName($name);
								}
								$field->setNameLine($this->counter);
								$this->objects[$objectId] = [ 'name' => $name, 'parent' => $parentId ];
								$fullName = [];
								while (isset($this->objects[$parentId])) {
									array_unshift($fullName, $this->objects[$parentId]['name']);
									$parentId = $this->objects[$parentId]['parent'];
								}
								if (! empty($fullName)) {
									$field->setFullName(implode(".", $fullName) . "." . $name);
								} else {
									$field->setFullName($name);
								}
							}
							if (preg_match("/^\/(V|DV|TU)\s+([\<\(\/])/", $this->entry, $def)) { 
								if ($def[1] == "TU") {
									$field->setTooltip($this->counter);
								} elseif ($def[1] == "DV") {
									$field->setDefaultValue($this->counter);
								} else {
									$field->setCurrentValue($this->counter);
								}
							} elseif (preg_match("/^\/MaxLen\s+(\d+)/", $this->entry, $values)) {
								$maxLen = $values[1];
								$field->setMaxLen(intval($maxLen));
							} elseif (preg_match("/^\/removed\s+true/", $this->entry, $parent)) {
								$removed = true;
							} elseif (preg_match("/^\/Parent\s+(\d+)/", $this->entry, $parent)) {
								$parentId = $parent[1];
							} elseif (preg_match("/^\/FT\s+\/(.+)$/", $this->entry, $ft)) {
								$fieldtype = $ft[1]; // Tx, Btn, Ch or Sig
								$field->setType($fieldtype);
							} elseif (preg_match("/^\/Ff\s+(\d+)/", $this->entry, $ff)) {
								$field->setFlag(intval($ff[1]));
							} elseif (preg_match("/^\/Opt\s+\[(.+)\]\s*$/", $this->entry, $opt)) {
								$array = $opt[1];
								if (preg_match_all("/\[([^\]]+)\]/", $array, $opt)) {
									$array = $opt[1];
									$options = [];
									foreach ($array as $option) {
										if (preg_match("/^\s*\(([^\)]+)\)\s*\(([^\)]+)\)\s*$/", $option, $opt)) {
											$options[$opt[1]] = $opt[2];
										} elseif (preg_match("/^\s*\(([^\)]+)\)\s*$/", $option, $opt)) {
											$options[$opt[1]] = $opt[1];
										} 
									}
									if (!empty($options)) {
										$field->setOptions($options);
									}
								}
							} elseif (preg_match("/^\/TI\s+\/(.+)$/", $this->entry, $ti)) {
								$field->setTopIndex($ti[1]);
							} elseif (preg_match("/^\/I\s+\[(.+)\]\s*$/", $this->entry, $i)) {
								$field->setSelecteds(array_map(function ($sel) {
									return intval($sel);
								}, preg_split("/\s+/", trim($i[1]))));
							}
							if (substr($this->entry, 0, 7) == '/Fields' && !$this->pdfDocument->isNeedAppearancesTrue()) {
								$this->entry = '/NeedAppearances true ' . $this->entry;
								$this->pdfDocument->setEntry($this->counter, $this->entry);
							}
						}
					}
					if (preg_match("/\bxref\b/",$this->entry)) {
						$xrefTable = 1;
						$crossReference = new CrossReference($this->pdfDocument);
						$crossReference->setLine($this->counter);
						$startPointer = $this->pointer + strpos($this->entry, "xref");
						$crossReference->setStartPointer($startPointer);
					}
				}
			} else {
				$xrefTable = $xrefTable + 1;
				switch($xrefTable) {
					case 2:
						if (preg_match("/^(\d+) (\d+)/",$this->entry, $match)) {
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
								if (!preg_match("/^trailer/", $this->entry)) { 
									throw new \Exception("PDFParser: xrefTable length corrupted?: Trailer not found at expected!");
								} else {
									$trailerPart = 1;
								}
							} else {
								$crossReference->setEntry($xref, $this->entry);
							}
							$refsCount--;
						} else {
							if ($trailerPart == 1) {
								if (trim($this->entry) != '') {
									if (!preg_match("/<</",$this->entry,$match)) {
										throw new \Exception("PDFParser: trailer corrupted?; missing start delimiter << ");
									}
									$trailerPart++;
								}
							} else if ($trailerPart > 0 && (!$inIDDefinition || preg_match("/^\/(Size|Root|Info|ID|DocChecksum)/", $this->entry, $match))) {
								if (preg_match("/\/Size (\d+)/", $this->entry, $match)) {
									$this->pdfDocument->addMeta("size", $match[1]);
								}
								if (preg_match("/^\/ID\s*\[\s*<([\da-fA-F]+)/", $this->entry, $match)) {
									$oid = $match[1];
									$inIDDefinition = true;
									if (preg_match("/\>\s?\</", $this->entry, $match)) {
										$isSingleLineIDDefinition = true;
									}
								}
								if ($inIDDefinition) {
									if ($isSingleLineIDDefinition || $isMultiLineIDDefinition) {
										if (preg_match("/([\da-fA-F]+)>.*$/", $this->entry, $match)) {
											$this->pdfDocument->addMeta("ID", array($oid, $match[1]));
											$inIDDefinition = false;
										} else {
											throw new \Exception("PDFParser: trailer corrupted?; ID chunk two can not be decoded ");
										}
									} else { 
										$isMultiLineIDDefinition = true;
									}
								}
								if (preg_match("/^\/DocChecksum \/([\da-fA-F]+)/", $this->entry, $match)) {
									$this->pdfDocument->addMeta("checksum", $match[1]);
								}
								if (preg_match("/>>/", $this->entry, $match)) {
									$trailerPart = -1;
								}
							} else {
								switch($trailerPart) {
									case -1:
										if (!preg_match("/^startxref/", $this->entry, $match)) {
											throw new \Exception("PDFParser: startxref tag expected, read $this->entry");
										}
										break;
									case -2:
										if (preg_match("/^(\d+)/", $this->entry, $match)) {
											$crossReference->setStartValue(intval($match[1]));
											$crossReference->setStartLine($this->counter);
										} else {
											throw new \Exception("PDFParser: startxref value expected, read $this->entry");
										}
										break;
								}
								$trailerPart--;
							}
						}
				}
			}
			$this->pointer += strlen($this->entry) + 1;
			$this->counter++;
		}
		$this->pdfDocument->setCrossReference($crossReference);
	}

}

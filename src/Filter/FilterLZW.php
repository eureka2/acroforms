<?php

namespace acroforms\Filter;

/*
 *  Class for handling LZW encoded data
 */
class FilterLZW implements FilterInterface {
	
	private $sTable = [];
	private $data = null;
	private $dataLength = 0;
	private $tIdx;
	private $bitsToGet = 9;
	private $bytePointer;
	private $bitPointer;
	private $nextData = 0;
	private $nextBits = 0;
	private $andTable = array(511, 1023, 2047, 4095);

	/**
	 * Method to decode LZW compressed data.
	 *
	 * @param string $data    The compressed data.
	 * @return string         The decoded data.
	 */
	public function decode($data) {
		if($data[0] == 0x00 && $data[1] == 0x01) {
			throw new \Exception('FilterLZW: LZW flavour not supported.');
		}
		$this->initsTable();
		$this->data = $data;
		$this->dataLength = strlen($data);
		$this->bytePointer = 0;
		$this->bitPointer = 0;
		$this->nextData = 0;
		$this->nextBits = 0;
		$oldCode = 0;
		$string = '';
		$decoded = '';
		while (($code = $this->getNextCode()) != 257) {
			if ($code == 256) {
				$this->initsTable();
				$code = $this->getNextCode();
				if ($code == 257) {
					break;
				}
				$decoded .= $this->sTable[$code];
				$oldCode = $code;
			} else {
				if ($code < $this->tIdx) {
					$string = $this->sTable[$code];
					$decoded .= $string;
					$this->addStringToTable($this->sTable[$oldCode], $string[0]);
					$oldCode = $code;
				} else {
					$string = $this->sTable[$oldCode];
					$string = $string.$string[0];
					$decoded .= $string;
					$this->addStringToTable($string);
					$oldCode = $code;
				}
			}
		}
		return $decoded;
	}


	/**
	 * Initialize the string table.
	 */
	private function initsTable() {
		$this->sTable = [];
		for ($i = 0; $i < 256; $i++)
			$this->sTable[$i] = chr($i);
		$this->tIdx = 258;
		$this->bitsToGet = 9;
	}

	/**
	 * Add a new string to the string table.
	 */
	private function addStringToTable ($oldString, $newString='') {
		$string = $oldString.$newString;
		// Add this new String to the table
		$this->sTable[$this->tIdx++] = $string;
		if ($this->tIdx == 511) {
			$this->bitsToGet = 10;
		} else if ($this->tIdx == 1023) {
			$this->bitsToGet = 11;
		} else if ($this->tIdx == 2047) {
			$this->bitsToGet = 12;
		}
	}

	/**
	 * Returns the next 9, 10, 11 or 12 bits
	 * @return int
	 */
	private function getNextCode() {
		if ($this->bytePointer == $this->dataLength) {
			return 257;
		}
		$this->nextData = ($this->nextData << 8) | (ord($this->data[$this->bytePointer++]) & 0xff);
		$this->nextBits += 8;
		if ($this->nextBits < $this->bitsToGet) {
			$this->nextData = ($this->nextData << 8) | (ord($this->data[$this->bytePointer++]) & 0xff);
			$this->nextBits += 8;
		}
		$code = ($this->nextData >> ($this->nextBits - $this->bitsToGet)) & $this->andTable[$this->bitsToGet-9];
		$this->nextBits -= $this->bitsToGet;
		return $code;
	}
	
	public function encode($in) {
		throw new \Exception("LZW encoding not implemented.");
	}
}

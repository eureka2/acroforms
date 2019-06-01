<?php

namespace acroforms\Filter;

/*
 *  Class for handling data encoded using a byte-oriented run-length encoding algorithm.
 */
class FilterRunLength implements FilterInterface {

	public function decode($data) {
		$decoded = '';
		$len = strlen($data);
		$i = 0;
		while ($i < $len) {
			$byte = ord($data{$i});
			if ($byte == 128) {
				break;
			} elseif ($byte < 128) {
				$decoded .= substr($data, ($i + 1), ($byte + 1));
				$i += ($byte + 2);
			} else {
				$decoded .= str_repeat($data{($i + 1)}, (257 - $byte));
				$i += 2;
			}
		}
		return $decoded;
	}

	public function encode($in) {
		throw new \Exception("RunLength encoding not implemented.");
	}
}

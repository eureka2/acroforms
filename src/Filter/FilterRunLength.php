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

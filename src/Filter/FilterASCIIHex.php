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

namespace acroforms\Filter;

/**
 *  Class for handling ASCII hexadecimal encoded data
 *
 */
class FilterASCIIHex implements FilterInterface {

	/**
	 * Decodes a binary string from its hexadecimal representation
	 *
	 * @param string $data an hexadecimal string
	 * @return string|false the decoded string or false if data not contains only hex characters
	 */
	public function decode($data) {
		$decoded = '';
		$len = strlen($data);
		// only hex numbers is allowed
		if ($len % 2 != 0 || preg_match("/[^\da-fA-F]/",$data)) {
			return false;
		}
		for ($i = 0; $i < $len; $i += 2) {
			$decoded .= '%'.substr ($data, $i, 2);
		}
		return rawurldecode ($decoded);//chr(hexdec())
	}

	/**
	 * Encodes a binary string to its hexadecimal representation
	 *
	 * @param string $data a binary string
	 * @return string the hexified string
	 */
	function encode($data) {
		$hex = "";
		$len = strlen($data);
		$i = 0;
		do {
			$hex .= sprintf("%02x", ord($data{$i}));
			$i++;
		} while ($i < $len);
		return $hex;
	}

}

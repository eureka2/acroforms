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
 * Class for handling GZIP compressed data
 */
class FlateFilter implements FilterInterface {

	private $data = null;
	private $dataLength = 0;

	/**
	 * Method to decode GZIP compressed data.
	 *
	 * @param string $data    The compressed data.
	 * @return string         The uncompressed data
	 */
	public function decode($data) {
		$this->data = $data;
		$this->dataLength = strlen($data);
		$data = gzuncompress($data);
		if(!$data) {
			throw new \Exception("FlateFilterDecode: invalid stream data.");
		}
		return $data;
	}

	/**
	 * Method to encode data into GZIP compressed.
	 *
	 * @param string $data    The data.
	 * @return string|false   The compressed data
	 */
	public function encode($data) {
		return gzcompress($data, 9);
	}
}

?>
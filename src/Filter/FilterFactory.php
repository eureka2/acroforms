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

/**
 * Factory for filters
 */
class FilterFactory {

	public static function getAvailableFilters() {
		return [
			"ASCIIHexDecode",
			"FlateDecode",
			"LZWDecode",
			"Standard",
			"ASCII85Decode",
			"RunLengthDecode"
		];
	}

	/**
	 * Get a filter class by its name
	 *
	 * @param string $name a string matching one of the supported default filters
	 * @return \acroforms\Filter\FilterInterface the wished filter class to access the stream
	 **/
	public static function getFilter($name) : FilterInterface{
		switch($name) {
			case "LZWDecode":
				$filter = new FilterLZW();
				break;
			case "ASCIIHexDecode": 
				$filter = new FilterASCIIHex();
				break;
			case "ASCII85Decode": 
				$filter = new FilterASCII85();
				break;
			case "FlateDecode":
				$filter = new FilterFlate();
				break;
			case "RunLengthDecode":
				$filter = new FilterRunLength();
				break;
			case "Standard": //Raw
				$filter = new FilterStandard();
				break;
			default:
				throw new \Exception(sprintf("FilterFactory: getFilter cannot open stream of object because filter '%s' is not supported.", $name));
		}
		return $filter;
	}

}

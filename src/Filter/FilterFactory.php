<?php

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
	 * @param $name a string matching one of the supported default filters (marked with +)
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

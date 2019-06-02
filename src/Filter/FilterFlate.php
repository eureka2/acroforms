<?php

namespace acroforms\Filter;

/**
 * Class for handling GZIP compressed data
 */
class FilterFlate implements FilterInterface {

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
			throw new \Exception("FilterFlateDecode: invalid stream data.");
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
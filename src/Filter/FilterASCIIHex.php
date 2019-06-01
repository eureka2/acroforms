<?php

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

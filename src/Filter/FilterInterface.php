<?php

namespace acroforms\Filter;

/**
 * Interface for filters
 */
interface FilterInterface {
	/**
	 * Decodes a string.
	 *
	 * @param string $data The input string
	 * @return string
	 */
	public function decode($data);

	/**
	 * Encodes a string.
	 *
	 * @param string $data The input string
	 * @return string
	 */
	public function encode($data);
}
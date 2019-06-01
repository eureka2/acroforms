<?php

namespace acroforms\Filter;

/*
 *  Dummy filter for unfiltered streams!
 */
class FilterStandard implements FilterInterface {

	public function decode($data) {
		return $data;
	}

	public function encode($data) {
		return $data;
	}
}

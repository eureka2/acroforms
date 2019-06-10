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

/*
 *  Class for handling ASCII base-85 encoded data
 */
class FilterASCII85 implements FilterInterface {

	public function decode($in) {
		$decoded = '';
		$state = 0;
		$chn = [];
		$l = strlen($in);
		for ($k = 0; $k < $l; ++$k) {
			$ch = ord($in[$k]) & 0xff;
			if ($ch == ord('~')) {
				break;
			}
			if (preg_match('/^\s$/',chr($ch))) {
				continue;
			}
			if ($ch == ord('z') && $state == 0) {
				$decoded .= chr(0).chr(0).chr(0).chr(0);
				continue;
			}
			if ($ch < ord('!') || $ch > ord('u')) {
				throw new \Exception('Illegal character in ASCII85Decode.');
			}
			$chn[$state++] = $ch - ord('!');
			if ($state == 5) {
				$state = 0;
				$r = 0;
				for ($j = 0; $j < 5; ++$j)
					$r = $r * 85 + $chn[$j];
				$decoded .= chr($r >> 24);
				$decoded .= chr($r >> 16);
				$decoded .= chr($r >> 8);
				$decoded .= chr($r);
			}
		}
		$r = 0;
		if ($state == 1)
			throw new \Exception('Illegal length in ASCII85Decode.');
		if ($state == 2) {
			$r = $chn[0] * 85 * 85 * 85 * 85 + ($chn[1]+1) * 85 * 85 * 85;
			$decoded .= chr($r >> 24);
		} else if ($state == 3) {
			$r = $chn[0] * 85 * 85 * 85 * 85 + $chn[1] * 85 * 85 * 85  + ($chn[2]+1) * 85 * 85;
			$decoded .= chr($r >> 24);
			$decoded .= chr($r >> 16);
		} else if ($state == 4) {
			$r = $chn[0] * 85 * 85 * 85 * 85 + $chn[1] * 85 * 85 * 85  + $chn[2] * 85 * 85  + ($chn[3]+1) * 85 ;
			$decoded .= chr($r >> 24);
			$decoded .= chr($r >> 16);
			$decoded .= chr($r >> 8);
		}
		return $decoded;
	}

	public function encode($in) {
		throw new \Exception("ASCII85 encoding not implemented.");
	}
}

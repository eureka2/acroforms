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

namespace acroforms\Builder;

/**
 *
 * Generates the fdf code
 */
class FDFBuilder {

	/**
	 *   Builds the fdf source
	 *
	 * @param string      $pdfUrl        a string containing a URL path to a PDF file on the server.
	 * @param array       $txOrChFields  an array in the form key => val; where the field name is the key, and the field's value is in val.
	 * @param array       $btnFields     an array in the form key => val; where the field name is the key, and the field's value is in val.
	 * @param array       $required      an array of required fields .
	 * @param array       $readonly      an array of readonly fields.
	 * @return string     the fdf source
	*/  
	public function build($pdfUrl, $txOrChFields, $btnFields, $required, $readonly ) {
		$fdf  = "%FDF-1.2\x0d%\xe2\xe3\xcf\xd3\x0d\x0a"; // header
		$fdf .= "1 0 obj\x0d<< "; // open the Root dictionary
		$fdf .= "\x0d/FDF << "; // open the FDF dictionary
		$fdf .= "/Fields [ "; // open the form Fields array
		$txOrChFields = $this->makeFullNameTree( $txOrChFields );
		$this->buildTxOrChFields($fdf, $txOrChFields, $required, $readonly);
		$btnFields = $this->makeFullNameTree( $btnFields );
		$this->buildBtnFields($fdf, $btnFields, $required, $readonly);
		$fdf .= "] \x0d"; // close the Fields array
		if( $pdfUrl ) {
			$fdf .= "/F (".$this->escapeString($pdfUrl).") \x0d";
		}
		$fdf .= ">> \x0d"; // close the FDF dictionary
		$fdf .= ">> \x0dendobj\x0d"; // close the Root dictionary
		$fdf .= "trailer\x0d<<\x0d/Root 1 0 R \x0d\x0d>>\x0d";
		$fdf .= "%%EOF\x0d\x0a";
		return $fdf;
	}

	private function escapeFieldName($name) {
		return $this->escapeString($name);
	}

	private function escapeTxOrChValue($value) {
		return $this->escapeString($value);
	}

	private function escapeBtnValue($value) {
		$escaped = '';
		if (mb_detect_encoding($value, 'UTF-8', true) !== false) {
			$value = utf8_decode($value);
		}
		$len = strlen( $value );
		for( $i = 0; $i < $len; ++$i ) {
			if( ord($value{$i}) < 33 || 126 < ord($value{$i}) || ord($value{$i}) == 0x23 ) {
				$escaped .= sprintf( "#%02x", ord($value{$i}) );
			} else {
				$escaped .= $value{$i};
			}
		}
		return $escaped;
	}

	private function escapeString($string) {
		$backslash = chr(0x5c);
		$escaped = '';
		if (mb_detect_encoding($string, 'UTF-8', true) !== false) {
			$string = utf8_decode($string);
		}
		$len = strlen($string);
		for( $i = 0; $i < $len; ++$i ) {
			if( ord($string{$i}) == 0x28 ||  // open parenthesis
				ord($string{$i}) == 0x29 ||  // close parenthesis
				ord($string{$i}) == 0x5c ) { // backslash
				$escaped .= $backslash.$string{$i}; // escape the character w/ backslash
			}
			else if( ord($string{$i}) < 32 || 126 < ord($string{$i}) ) {
				$escaped .= sprintf( "\\%03o", ord($string{$i}) );
			} else {
				$escaped .= $string{$i};
			}
		}
		return $escaped;
	}

	/*
	 * Converts a dot-delimited full name into a tree of arrays;
	 */
	private function makeFullNameTree( $fullName ) {
		$tree = [];
		foreach ( $fullName as $key => $value ) {
			$nameParts = explode( '.', $key, 2 );
			if( count($nameParts) == 2 ) {
				if ( !array_key_exists( $nameParts[0], $tree ) ) {
					$tree[ $nameParts[0] ] = [];
				}
				if ( ! is_array( $tree[ $nameParts[0] ] ) ) {
					$tree[ $nameParts[0] ] = [ '' => $tree[ $nameParts[0] ] ];
				}
				$tree[ $nameParts[0] ][ $nameParts[1] ] = $value;
			} else {
				if ( array_key_exists( $nameParts[0], $tree ) &&
					is_array( $tree[ $nameParts[0] ] ) ) {
					$tree[ $key ][''] = $value;
				} else { 
					$tree[ $key ] = $value;
				}
			}
		}
		foreach( $tree as $key => $value ) {
			if ( is_array($value) ) {
				$tree[ $key ] = $this->makeFullNameTree( $value ); // recurse
			}
		}
		return $tree;
	}

	private function setFieldFlag(&$fdf, $fieldName, $required, $readonly ) {
		if( in_array( $fieldName, $required ) ) {
			$fdf .= "/SetF 2 "; // set
		} else {
			$fdf .= "/ClrF 2 "; // clear
		}
		if( in_array( $fieldName, $readonly ) ) {
			$fdf .= "/SetFf 1 "; // set
		} else {
			$fdf .= "/ClrFf 1 "; // clear
		}
	}

	private function buildFields (&$fdf, &$data, &$required, &$readonly, $dottedName, $isTxOrChField) {
		if (strlen( $dottedName ) > 0) {
			$dottedName .= '.';
		}
		foreach( $data as $key => $value ) {
			$fdf .= "<< "; // open dictionary
			if ( is_array($value) ) {
				$fdf .= "/T (" . $this->escapeFieldName($key) . ") ";
				$fdf .= "/Kids [ "; // open Kids array
				// recurse
				$this->buildFields(
					$fdf,
					$value,
					$required,
					$readonly,
					$dottedName . $key,
					$isTxOrChField
				);
				$fdf .= "] "; // close Kids array
			} else {
				// field name
				$fdf .= "/T (".$this->escapeFieldName( $key ).") ";
				// field value
				if( $isTxOrChField ) {
					$fdf .= "/V (".$this->escapeTxOrChValue( $value ).") ";
				} else { // Btn field
					$fdf .= "/V /".$this->escapeBtnValue( $value ). " ";
				}
				// field flags
				$this->setFieldFlag(
					$fdf,
					$dottedName . $key,
					$required,
					$readonly
				);
			}
			$fdf .= ">> \x0d"; // close dictionary
		}
	}

	private function buildTxOrChFields(&$fdf, &$txOrChFields, &$required, &$readonly ) {
		return $this->buildFields(
			$fdf,
			$txOrChFields,
			$required,
			$readonly,
			'',
			true // true => Tx or Ch fields
		);
	}


	private function buildBtnFields(&$fdf, &$btnFields, &$required, &$readonly ) {
		return $this->buildFields(
			$fdf,
			$btnFields,
			$required,
			$readonly,
			'',
			false // false => Btn fields
		);
	}

}

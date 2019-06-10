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

namespace acroforms\Utils;

/****************************************************************
 * Generates pdf files using the pdf toolkit (pdftk)
 * @see https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/
 ******************************************************************/
 class PDFtkBridge {

	public static function is_windows(){
		$PHP_OS = php_uname('s');
		return (strtoupper(substr($PHP_OS, 0, 3)) === 'WIN');
	}

	public static function run($cmdline, $output) {
		$descriptorspec = [
			0 => ["pipe", "r"],  // stdin 
			1 => ["pipe", "w"],  // stdout 
			2 => ["pipe", "w"]   // stderr 
		];
		$err = '';
		$process = proc_open($cmdline, $descriptorspec, $pipes);
		if (is_resource($process)) {
			$err = stream_get_contents($pipes[2]);
			fclose($pipes[2]);
			$return_value = proc_close($process);
		} else {
			$err = sprintf("PDFtkBridge: No more resource to execute the command '%s'", $cmdline);
		}
		if ($err) {
			$ret = ["success" => false, "output" => $err];
		} else {
			$ret = ["success" => true, "output" => $output];
		}
		return $ret;
	}

	/**
	 * Calls pdftk/pdftk.exe to inject data from an FDF file into a PDF file
	 *
	 * @param string $pdfFile absolute pathname to a pdf form file
	 * @param string $fdfFile absolute pathname to a pdf data file
	 * @param array $settings options for pdftk: 
	 *	- output_modes: 'compress', 'uncompress', 'flatten' ..(see pdftk --help)
	 *	- security: 'password, 'encrypt', 'allow'
	 *	- command: the full path of the pdftk executable
	 *
	 * @return array an associative array with two keys: 
	 *	- bool success a flag , if true, it means that the process ended successfully
	 *	- string output the path to the generated pdf or an error message
	 **/
	public static function pdftk($pdfFile, $fdfFile, $settings) {
		$outputModes = $settings['output_modes'];
		$security = $settings['security'];
		$cmd = $settings['command'];
		$err = '';
		if (self::is_windows()) {
			$cmd = sprintf('cd %s && %s', escapeshellarg(dirname($cmd)), basename($cmd));
		}
		$temp = tempnam(sys_get_temp_dir(), 'acroform_');
		if ($temp === false) {
			return ["success" => false, "output" => "PDFtkBridge: pdftk failed because it's impossible to create a temporary file"];
		} else {
			$pdfOut = $temp.'.pdf';
			rename($temp, $pdfOut);
			$cmdline = sprintf('%s "%s" fill_form "%s" output "%s" drop_xfa %s %s', $cmd, $pdfFile, $fdfFile, $pdfOut, $outputModes, $security);
			return self::run($cmdline, $pdfOut);
		}
	}

}

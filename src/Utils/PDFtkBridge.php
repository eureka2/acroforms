<?php

namespace acroforms\Utils;

/****************************************************************
 * Generates pdf files using the pdf toolkit (pdftk)
 * @see https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/
 ******************************************************************/
 class PDFtkBridge {

	private static function is_windows(){
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
		$pdfOut = $temp.'.pdf';
		rename($temp, $pdfOut);
		$cmdline = sprintf('%s "%s" fill_form "%s" output "%s" %s %s', $cmd, $pdfFile, $fdfFile, $pdfOut, $outputModes, $security);
		return self::run($cmdline, $pdfOut);
	}

}

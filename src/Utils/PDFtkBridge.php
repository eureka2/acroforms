<?php

namespace acroforms\Utils;

/****************************************************************
 * Generates pdf files using the pdf toolkit
 ******************************************************************/
 class PDFtkBridge {

	private static function is_windows(){
		$PHP_OS = php_uname('s');
		return (strtoupper(substr($PHP_OS, 0, 3)) === 'WIN');
	}

	/**
	 * Generate randomly an unique id
	 **/
	public static function rnunid() {
		return md5( uniqid() );  // 32 characters long
	}

	/**
	 * This function will call pdftk/pdftk.exe like this:
	 *	pdftk form.pdf fill_form data.fdf output out.pdf flatten
	 *
	 * @param string $pdfFile absolute pathname to a pdf form file
	 * @param string $fdfFile absolute pathname to a pdf data file
	 * @param array $settings options for pdftk 
	 *
	 *	Output modes 'compress', 'uncompress', 'flatten' ..(see pdftk --help)
	 * @return array an associative array with two keys: 
	 *	- bool success a flag , if positive meaning the process is a success
	 *	- string return the path to the pdf generated or the error message 
	 **/
	public static function pdftk($pdfFile, $fdfFile, $settings) {
		$descriptorspec = [
			0 => ["pipe", "r"],  // stdin 
			1 => ["pipe", "w"],  // stdout 
			2 => ["pipe", "w"]   // stderr 
		];
		$outputModes = $settings['output_modes'];
		$security = $settings['security'];
		$cache = $settings['cache'];
		$cmd = $settings['command'];
		$err = '';
		if (self::is_windows()) {
			$cmd = sprintf('cd %s && %s', escapeshellarg(dirname($cmd)), basename($cmd));
		}
		$pdfOut = $cache . "/pdf_".self::rnunid()."_flatten.pdf";
		$cmdline = sprintf('%s "%s" fill_form "%s" output "%s" %s %s', $cmd, $pdfFile, $fdfFile, $pdfOut, $outputModes, $security);
		$process = proc_open($cmdline, $descriptorspec, $pipes);
		if (is_resource($process)) {
			$err = stream_get_contents($pipes[2]);
			fclose($pipes[2]);
			$return_value = proc_close($process);
		} else {
			$err = "No more resource to execute the command";
		}
		if ($err) {
			$ret = ["success" => false, "return" => $err];
		} else 
			$ret = ["success" => true, "return" => $pdfOut];
		return $ret;
	}

}

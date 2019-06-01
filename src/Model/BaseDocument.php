<?php

namespace acroforms\Model;

/**
 * Base class for PDFDocument and FDFDocument
 */
abstract class BaseDocument {

	protected $content = null;

	/**
	 * Loads the content of a file
	 *
	 * @access private
	 * @param string $filename the filename of the file
	 **/
	public function load($filename) {
		$handle = fopen($filename, 'rb');
		$content = fread($handle, filesize($filename));
		fclose($handle);
		if (!$content) {
			throw new \Exception(sprintf('BaseDocument: Cannot open file %s !', $filename));
		}
		$this->content = $content;
	}

	/**
	 * Loads the content of a string
	 *
	 * @param string $content the content
	 **/
	public function setContent($content) {
		$this->content = $content;
	}

	public function getContent() {
		return $this->content;
	}

}

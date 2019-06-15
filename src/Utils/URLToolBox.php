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

class URLToolBox {

	public static function getUrlfromDir($localDir) {
		$serverDir = self::getWebDir($localDir);
		$server_scheme = self::getScheme();
		$server_host = self::getHost();
		return "{$server_scheme}://{$server_host}/$serverDir";
	}

	public static function fixPath($path) {
		return str_replace('\\', '/' ,$path);
	}

	public static function resolveUrl($relativeUrl) {
		$url = array_merge([
			'scheme' => 'http',
			'host' => '',
			'path' => ''
		], parse_url($relativeUrl));
		$url["path"] = self::resolvePath($url["path"] ?? '');
		return self::buildUrl($url);
	}

	/**
	 *
	 * Get realpath without checking existence of file
	 *
	 */
	public static function resolvePath($path) {
		$out = array();
		foreach(explode('/', $path) as $i => $fold){
			if ($fold == '' || $fold == '.') {
				continue;
			}
			if ($fold == '..' && $i > 0 && end($out)!= '..') {
				array_pop($out);
			} else {
				$out[] = $fold;
			}
		}
		return ($path{0} == '/' ? '/' : '').join('/', $out);
	}

	private static function getScheme() {
		if (PHP_SAPI === 'cli') {
			return 'file';
		}
		$port = self::getServerVariableValue("SERVER_PORT");
		$schemes = [
			'http'=>   80,
			'https'=> 443,
			'ftp' =>   21,
			'ftps'=>  990
		];
		$ports = array_flip($schemes);
		return (array_key_exists($port, $ports)) ? $ports[$port] : 'http';
	}

	private static function getHost() {
		return PHP_SAPI === 'cli' ? '/' : self::getServerVariableValue("HTTP_HOST");
	}

	private static function getWebDir($localDir) {
		if (PHP_SAPI === 'cli') {
			return $localDir;
		}
		$root = realpath(self::getServerVariableValue("DOCUMENT_ROOT"));
		return str_replace($root, '', $localDir);
	}

	private static function getServerVariableValue($variable) {
		$value = filter_input(INPUT_SERVER, $variable);
		if ($value === null || $value === false) {
			$value = getenv($variable);
			if (($value === null || $value === false || $value == '') && $variable == "DOCUMENT_ROOT") {
				$value = getenv("SCRIPT_NAME");
				$root = self::fixPath(realpath($value));
				$value = substr($root, 0 ,strpos($root, $value));
			}
		}
		return $value;
	}

	private static function buildUrl($parsed) {
		$url  = $parsed['scheme'] . '://';
		if (isset($parsed['user']) && $parsed['user'] != '' && isset($parsed['pass'])) {
			$url .= $parsed['user'] . ':' . $parsed['pass'] . '@';
		}
		$url .= $parsed['host'];
		if (isset($parsed['path']) && $parsed['path'] != '') {
			$url .= $parsed['path'];
		}
		if (isset($parsed['query']) && $parsed['query'] != '') {
			$url .= '?' . $parsed['query'];
		}
		if (isset($parsed['fragment']) && $parsed['fragment'] != '') {
			$url .= '#' . $parsed['fragment'];
		}
		return $url;
	}

}

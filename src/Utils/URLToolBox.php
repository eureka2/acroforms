<?php

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

	public static function getScheme() {
		$numargs = func_num_args();
		$port = ($numargs >0) ? func_get_arg(0) : filter_input(INPUT_SERVER, "SERVER_PORT");
		$schemes = array(
			'http'=>   80, // default for http
			'https'=> 443, // default for https
			'ftp' =>   21, // default for ftp
			'ftps'=>  990  // default for ftps 
		);
		$ports = array_flip($schemes);
		return (array_key_exists($port, $ports)) ? $ports[$port] : 0;
	}

	public static function getHost() {
		return filter_input(INPUT_SERVER, "HTTP_HOST");
	}

	public static function fixPath($path) {
		return str_replace('\\','/',$path);
	}

	public static function getWebDir($local_dir) {
		$local_root = filter_input(INPUT_SERVER, "DOCUMENT_ROOT");
		$server_dir = str_replace($local_root,'',$local_dir);
		return $server_dir;
	}

	public static function getUrlfromDir($local_dir) {
		$server_dir = self::getWebDir($local_dir);
		$server_scheme = self::getScheme();
		$server_host = self::getHost();
		return "{$server_scheme}://{$server_host}/$server_dir";
	}

	public static function build_url($aUrl) {
		if (!is_array($aUrl)) {
			return "";
		}
		$sQuery = '';
		if (isset($aUrl['query_params']) && is_array($aUrl['query_params'])) {
			$aPairs = array();
			foreach ($aUrl['query_params'] as $sKey=>$sValue) {
				$aPairs[] = $sKey.'='.urlencode($sValue);
			}
			$sQuery = implode('&', $aPairs);   
		} else {
			if (isset($aUrl['query'])) {
				$sQuery = $aUrl['query'];
			}
		}
		$sUrl =
			$aUrl['scheme'] . '://' . (
				isset($aUrl['user']) && $aUrl['user'] != '' && isset($aUrl['pass'])
					? $aUrl['user'] . ':' . $aUrl['pass'] . '@'
					: ''
			) .
			$aUrl['host'] . (
				isset($aUrl['path']) && $aUrl['path'] != ''
					? $aUrl['path']
					: ''
			) . (
			   $sQuery != ''
					? '?' . $sQuery
				   : ''
			) . (
			   isset($aUrl['fragment']) && $aUrl['fragment'] != ''
					? '#' . $aUrl['fragment']
					: ''
			);
		return $sUrl;
	}

	public static function resolve_url($relative_url) {
		$url = parse_url($relative_url);
		$url["path"] = self::resolvePath($url["path"]);
		$absolute_url = self::build_url($url);
		return $absolute_url;
	}

	/**
	 *
	 * Get realpath without checking existence of file like php function does..
	 *
	 */
	public static function resolvePath($path) {
		$out = array();
		foreach(explode('/', $path) as $i=>$fold){
			if ($fold=='' || $fold=='.') {
				continue;
			}
			if ($fold=='..' && $i>0 && end($out)!='..') {
				array_pop($out);
			} else {
				$out[]= $fold;
			}
		}
		return ($path{0}=='/'?'/':'').join('/', $out);
	}

}

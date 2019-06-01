<?php

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

<?php

spl_autoload_register(function ($class) {
	if (strpos($class, 'acroforms\\') === 0) {
		$filename = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 9)) . '.php';
		$fullpath = __DIR__ . DIRECTORY_SEPARATOR . $filename;
		if (file_exists($fullpath)) {
			/** @noinspection PhpIncludeInspection */
			require_once $fullpath;
		}
	}
});
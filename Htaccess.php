<?php

	namespace mvc_framework\core\router;

	class Htaccess {
		private static $htaccess = [];

		public static function REDIRECT($path = '') {
			$flag = 'B';
			if(strlen($path) > 0) {
				$flag .= '='.$path;
			}
			return $flag;
		}
		public static function B($chars=[]) {
			$flag = 'B';
			if(count($chars) > 0) {
				$flag .= '='.implode('', $chars);
			}
			return $flag;
		}
		public static function COOKIE($NAME, $VALUE, $DOMAIN, $lifetime, $path, $secure = null, $httponly = null) {
			$flag = 'cookie='.$NAME.':'.$VALUE.':'.$DOMAIN.':'.$lifetime.':'.$path.($secure === null ? '' : ':'.$secure).($httponly === null ? '' : ':'.$httponly);
			return $flag;
		}
		const END = 'END';
		const FORBIDDEN = 'F';
		const GONE = 'G';
		public static function HANDLER($handler) {
			$flag = 'H='.$handler;
			return $flag;
		}
		const LAST = 'L';
		const NEXT = 'N';
		const PASSTHROUGH = 'PT';
		public static function SKIP($steps) {
			return 'S='.$steps;
		}
		public static function TYPE($type) {
			return 'T='.$type;
		}

		public static function init() {
			self::$htaccess[] = 'Options +FollowSymlinks';
			self::$htaccess[] = 'RewriteEngine On';
			self::$htaccess[] = '';
		}
		public static function alias($origin, $alias) {
			self::$htaccess[] = 'Alias '.$origin.' '.$alias;
		}
		public static function rewrite_rule($pattern, $substitution, $flags = [self::LAST]) {
			self::$htaccess[] = 'RewriteRule '.$pattern.' '.$substitution.' ['.implode(', ', $flags).']';
		}
		public static function error_document($error, $error_file) {
			self::$htaccess[] = 'ErrorDocument '.$error.' '.$error_file;
		}
		public static function genere($module = '') {
			file_put_contents(__DIR__.'/../..'.$module.'/.htaccess', implode("\n", self::$htaccess));
		}
	}

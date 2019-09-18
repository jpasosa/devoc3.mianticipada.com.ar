<?php

namespace Journal3\Utils;

class Request {

	public static function isGet() {
		return strtolower(Arr::get($_SERVER, 'REQUEST_METHOD')) === 'get';
	}

	public static function isPost() {
		return strtolower(Arr::get($_SERVER, 'REQUEST_METHOD')) === 'post';
	}

	public static function isAjax() {
		return strtolower(Arr::get($_SERVER, 'HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
	}

	public static function isAdminRequest() {
		return Arr::get($_GET, 'jf') === '1';
	}

	public static function isHttps() {
		return (bool)Arr::get($_SERVER, 'HTTPS');
	}

	public static function getCurrentUrl() {
		return static::getHost() . $_SERVER['REQUEST_URI'];
	}

	public static function getHost() {
		return (static::isHttps() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
	}

	public static function matches($ignored_routes) {
		$route = Arr::get($_GET, 'route');

		if (!$route) {
			return false;
		}

		foreach ($ignored_routes as $ignored_route) {
			if (Str::startsWith($route, $ignored_route)) {
				return true;
			}
		}

		return false;
	}

	public static function header_sent($header) {
		$headers = headers_list();
		$header = trim($header, ': ');

		foreach ($headers as $hdr) {
			if (stripos($hdr, $header) !== false) {
				return true;
			}
		}

		return false;
	}

}

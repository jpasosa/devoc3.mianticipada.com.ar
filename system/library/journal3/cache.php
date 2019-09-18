<?php

namespace Journal3;

use Journal3\Utils\Profiler;
use Journal3\Utils\Request;
use SuperCache\SuperCache;

class Cache {

	private static $config = array(
		'store_id'           => '',
		'language_id'        => '',
		'currency_id'        => '',
		'device'             => '',
		'wishlist'           => 0,
		'compare'            => 0,
		'customer_firstname' => '',
		'customer_lastname'  => '',
	);

	private static $key;

	public function set($key, $data) {
		if (JOURNAL3_CACHE) {
			SuperCache::cache('journal3.' . $key . '.' . static::getKey())->set($data);
		}
	}

	public function get($key) {
		if (JOURNAL3_CACHE) {
			Profiler::start('journal3/cache');

			$key = 'journal3.' . $key . '.' . static::getKey();

			$data = SuperCache::cache($key)->get();

			if ($data === null) {
				$data = false;
			}

			Profiler::end('journal3/cache');

			return $data;
		}

		return false;
	}

	public function delete($key = null) {
		if ($key === null) {
			SuperCache::clearAll();
		} else {
			SuperCache::cache('journal3.' . $key)->destroy();
		}
	}

	public function update($data) {
		// update count badge
		$new_data = str_replace(array(
			'{{ $wishlist }}',
			'{{ $compare }}',
			'{{ $customer_firstname }}',
			'{{ $customer_lastname }}',
		), array(
			static::$config['wishlist'],
			static::$config['compare'],
			static::$config['customer_firstname'],
			static::$config['customer_lastname'],
		), $data);

		if ($data !== $new_data) {
			if (!static::$config['wishlist']) {
				$new_data = str_replace('count-badge wishlist-badge', 'count-badge wishlist-badge count-zero', $new_data);
			}

			if (!static::$config['compare']) {
				$new_data = str_replace('count-badge compare-badge', 'count-badge compare-badge count-zero', $new_data);
			}
		}

		return $new_data;
	}

	public static function setConfig($key, $value) {
		static::$config[$key] = $value;
	}

	public static function getKey() {
		if (static::$key === null) {
			static::$key = sprintf(
				"%s_%s_s%d_l%d_c%s_c%d_g%d_%s",
				substr(md5(Request::getHost()), 0, 10),
				static::$config['device'],
				static::$config['store_id'],
				static::$config['language_id'],
				static::$config['currency_id'],
				static::$config['customer'],
				static::$config['customer_group_id'],
				JOURNAL3_VERSION
			);
		}

		return static::$key;
	}

}

<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;

class Range extends Option {

	protected static function parseValue($value, $data = null) {
		return array(
			'from'    => Arr::get($value, 'from'),
			'to'      => Arr::get($value, 'to'),
			'between' => Arr::get($value, 'between') !== 'false',
		);
	}

	public static function inRange($range) {
		if (!is_array($range)) {
			return true;
		}

		$from = $range['from'];
		$to = $range['to'];
		$between = $range['between'];

		if (!$from && !$to) {
			return true;
		}

		$from = strtotime($from);
		$to = strtotime($to);

		if (!$from && !$to) {
			return true;
		}

		$now = time();

		if ($from && !$to) {
			if ($from > $now) {
				return false;
			}
		} else if (!$from && $to) {
			if ($to < $now) {
				return false;
			}
		} else {
			if ($between) {
				if (($from > $now) || ($to < $now)) {
					return false;
				}
			} else {
				if (($from < $now) || ($to > $now)) {
					return false;
				}
			}
		}

		return true;
	}

}

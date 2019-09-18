<?php

namespace Journal3\Options;

use Journal3\Utils\Arr;

class ImageLang extends Option {

	protected static function parseValue($value, $data = null) {
		$result = Arr::get($value, 'lang_' . $data['config']['language_id']);

		if ($result === null) {
			$result = Arr::get($value, 'lang_1');
		}

		if ($result && is_file(DIR_IMAGE . $result)) {
			return $result;
		}

		return null;
	}

}

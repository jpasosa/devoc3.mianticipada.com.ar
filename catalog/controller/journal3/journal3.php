<?php

use Journal3\Opencart\Controller;
use Journal3\Utils\Img;

class ControllerJournal3Journal3 extends Controller {

	public function image_tools() {
		try {
			if (!function_exists('exec')) {
				throw new \Exception('exec function is not enabled!');
			}

			if (in_array(strtolower(ini_get('safe_mode')), array('on', '1'), true)) {
				throw new \Exception('safe_mode is on');
			}

			$disabled_functions = explode(',', ini_get('disable_functions'));

			if (in_array('exec', $disabled_functions)) {
				throw new \Exception('exec function is in disable_functions');
			}

			$this->renderJson(self::SUCCESS, Img::canOptimise());
		} catch (\Exception $e) {
			$this->renderJson(self::SUCCESS, array(
				'error' => $e->getMessage(),
			));
		}
	}

}

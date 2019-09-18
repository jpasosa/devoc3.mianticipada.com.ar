<?php

use Journal3\Opencart\Model;
use Journal3\Utils\Img;
use Journal3\Utils\Str;

class ModelJournal3Image extends Model {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->load->model('tool/image');
	}

	public function transparent($width, $height) {
		if (!$width || !$height) {
			return false;
		}

		$filename = 'cache/transparent-' . $width . 'x' . $height . '.png';

		if (!is_file(DIR_IMAGE . $filename)) {
			$img = imagecreatetruecolor($width, $height);
			$color = imagecolorallocatealpha($img, 0, 0, 0, 127);
			imagesavealpha($img, true);
			imagefill($img, 0, 0, $color);
			imagepng($img, DIR_IMAGE . $filename);
			imagedestroy($img);

			if ($this->journal3->settings->get('performanceCompressImagesStatus')) {
				Img::optimise(DIR_IMAGE . $filename);
			}
		}

		if (defined('JOURNAL3_STATIC_URL')) {
			return JOURNAL3_STATIC_URL . 'image/' . $filename;
		}

		if ($this->request->server['HTTPS']) {
			return $this->config->get('config_ssl') . 'image/' . $filename;
		} else {
			return $this->config->get('config_url') . 'image/' . $filename;
		}
	}

	private function isNumeric($value) {
		return is_numeric($value) && $value > 0;
	}

	public function dimensions($filename) {
		if ($filename && is_file(DIR_IMAGE . $filename)) {
			list($width, $height) = @getimagesize(DIR_IMAGE . $filename);

			if (!$width || !$height) {
				trigger_error('Image <b>' . DIR_IMAGE . $filename . '</b> is invalid!');
			}
		} else {
			$width = null;
			$height = null;
		}

		return array($width, $height);
	}

	public function resize($filename, $width = null, $height = null, $resize_type = '') {
		if (!$filename || !is_file(DIR_IMAGE . $filename)) {
			// external image
			if (Str::startsWith($filename, 'http://') || Str::startsWith($filename, 'https://')) {
				return $this->model_tool_image->resize($filename, $width, $height, $resize_type);
			}

			// svg image
			if (Str::endsWith($filename, '.svg')) {
				return $this->model_tool_image->resize($filename, $width, $height, $resize_type);
			}

			$filename = 'placeholder.png';
		}

		list($width_orig, $height_orig) = $this->dimensions($filename);

		if (!$this->isNumeric($width) && !$this->isNumeric($height)) {
			return $this->model_tool_image->resize($filename, $width_orig, $height_orig);
		}

		$ratio_orig = (float)$width_orig / $height_orig;

		if ($this->isNumeric($width) && $this->isNumeric($height)) {
			if ($resize_type === 'fill' || $resize_type === 'crop') {
				$ratio = (float)$width / $height;

				if ($ratio > $ratio_orig) {
					$resize_type = 'w';
				} else if ($ratio < $ratio_orig) {
					$resize_type = 'h';
				} else {
					$resize_type = '';
				}
			} else {
				$ratio = (float)$width / $height;

				if ($ratio > $ratio_orig) {
					$resize_type = 'h';
				} else if ($ratio < $ratio_orig) {
					$resize_type = 'w';
				} else {
					$resize_type = '';
				}
			}
		} else if ($this->isNumeric($width)) {
			$height = $width / $ratio_orig;
		} else {
			$width = $height * $ratio_orig;
		}

		return $this->model_tool_image->resize($filename, $width, $height, $resize_type);
	}

}

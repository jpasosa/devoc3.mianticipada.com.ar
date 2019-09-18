<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;
use Journal3\Utils\Request;

class ControllerJournal3Form extends ModuleController {

	public function index($args) {
		$data = parent::index($args);

		if (!$data) {
			return null;
		}

		foreach ($this->settings['items'] as $index => $item) {
			if (in_array($item['type'], array('date', 'time', 'datetime'))) {
				if ($this->journal3->isOC2()) {
					$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment.js');
					$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
					$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
				} else {
					$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
//					$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
					$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
					$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
				}
				break;
			}
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$data['text_select'] = $this->language->get('text_select');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['button_submit'] = $this->language->get('button_submit');
		$data['datepicker'] = $this->language->get('datepicker');

		$data['action'] = $this->model_journal3_links->url('journal3/form/send', 'module_id=' . $this->module_id, true);

		$data['agree_data'] = $this->model_journal3_links->getInformation($parser->getSetting('agree'));

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		return array();
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		return array();
	}

	protected function beforeRender() {
		if (!isset($this->request->get['route'])) {
			$this->request->get['route'] = 'common/home';
		}

		if ($this->journal3->isOC2()) {
			if ($this->config->get($this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
				$this->settings['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$this->settings['captcha'] = '';
			}
		} else {
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
				$this->settings['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$this->settings['captcha'] = '';
			}
		}

		foreach ($this->settings['items'] as &$item) {
			if (!$item['placeholder']) {
				if ($item['type'] === 'select') {
					$item['placeholder'] = $this->settings['text_select'];
				} else {
					$item['placeholder'] = $item['label'];
				}
			}
		}
	}

	public function send() {
		try {
			$module_id = (int)$this->input('GET', 'module_id');
			$agree = $this->input('POST', 'agree', '');

			if (!$this->index(array('module_id' => $module_id, 'module_type' => 'form',))) {
				throw new \Exception('Invalid module id!');
			}

			$this->load->language('account/register');

			$errors = array();
			$data = array();

			$data['title'] = $this->settings['title'];

			$data['url'] = htmlspecialchars_decode($this->input('POST', 'url', ''));

			$data['ip'] = $this->request->server['REMOTE_ADDR'];

			if (isset($this->settings['agree'])) {
				$agree_data = $this->model_journal3_links->getInformation($this->settings['agree']);

				if ($agree_data && !$agree) {
					$errors['agree'] = $agree_data['error'];
				}
			}

			foreach ($this->settings['items'] as $index => $item) {
				$value = Arr::get($this->request->post, 'item.' . $index);

				if ($item['required'] && empty($value)) {
					$errors['item[' . $index . ']'] = sprintf($this->language->get('error_custom_field'), $item['label']);
				}

				if ($item['type'] === 'name') {
					$data['name'] = $value;
				} else if ($item['type'] === 'email') {
					$data['email'] = $value;

					if ($value && !isset($errors['item[' . $index . ']']) && ((utf8_strlen($value) > 96) || !filter_var($value, FILTER_VALIDATE_EMAIL))) {
						$errors['item[' . $index . ']'] = $this->language->get('error_email');
					}
				}

				$data['items'][$index] = array(
					'type'  => $item['type'],
					'label' => $item['label'],
					'value' => $value,
				);
			}

            if (!isset($this->request->post['g-recaptcha-response'])) {
                $this->request->post['g-recaptcha-response'] = '';
            }

            if (!isset($this->request->post['captcha'])) {
                $this->request->post['captcha'] = '';
            }

			if ($this->journal3->isOC2()) {
				if ($this->config->get($this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
					$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

					if ($captcha) {
						$errors['captcha'] = $captcha;
					}
				}
			} else {
				if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
					$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

					if ($captcha) {
						$errors['captcha'] = $captcha;
					}
				}
			}

			if ($errors) {
				$this->renderJson(self::ERROR, array('errors' => $errors));
			} else {
				unset($this->session->data['gcapcha']);

				$this->load->model('journal3/message');
				$this->load->model('journal3/image');

				$email_data = array(
					'title'      => $this->config->get('config_name'),
					'logo'       => $this->model_journal3_image->resize($this->config->get('config_logo')),
					'store_name' => $this->config->get('config_name'),
					'store_url'  => $this->config->get(Request::isHttps() ? 'config_ssl' : 'config_url'),
					'data'       => $data,
				);

				$this->model_journal3_message->addMessage($data);

				$params = array(
					'to'      => $this->config->get('config_email'),
					'subject' => $this->config->get('config_name'),
					'message' => $this->load->view('journal3/module/form_email', $email_data),
				);

				if (Arr::get($data, 'email')) {
					$params['reply_to'] = $data['email'];
				}

				$this->load->controller('journal3/mail/send', $params);

				$this->renderJson(self::SUCCESS, array('message' => $this->settings['sentText']));
			}
		} catch (Exception $e) {
			$this->renderJson(self::ERROR, $e->getMessage());
		}
	}

}

<?php

use Journal3\Opencart\Controller;
use Journal3\Utils\Arr;

class ControllerJournal3Startup extends Controller {

	public function index() {
		if ($this->config->get('config_theme') === 'journal3' || $this->config->get('config_theme') === 'theme_journal3' || $this->config->get('config_template') === 'journal3') {
			// redirect wrong hostname to avoid cors
//			if (Request::isGet()) {
//				$current_url = Request::getCurrentUrl();
//				$correct_url = $this->url->link('');
//
//				$current_host = parse_url($current_url, PHP_URL_SCHEME) . '://' . parse_url($current_url, PHP_URL_HOST);
//				$correct_host = parse_url($correct_url, PHP_URL_SCHEME) . '://' . parse_url($correct_url, PHP_URL_HOST);
//
//				if ($current_host !== $correct_host) {
//					$url = str_replace($current_host, $correct_host, $current_url);
//
//					if (!headers_sent()) {
//						header('Location: ' . $url);
//					} else {
//						echo '<script>location = "' . $url . '";</script>';
//					}
//
//					exit;
//				}
//			}

			define('JOURNAL3_CATALOG', true);
			define('JOURNAL3_ACTIVE', true);

			$this->registry->set('journal3', new Journal3($this->registry));

			// models
			$this->load->model('journal3/settings');
			$this->load->model('journal3/module');
			$this->load->model('journal3/image');

			// skins check
			if (!$this->model_journal3_settings->haveSkins()) {
				$this->print_error('You can import demo content by following the documentation on: <a href="https://docs.journal-theme.com/docs/demos/demo/" target="_blank">Demo Import</a>.');
			}

			// assets folder writable
			if (!is_writable($this->journal3->minifier->getAssetsPath())) {
				$this->print_error($this->journal3->minifier->getAssetsPath() . ' is not writable! <br /> <br /> <br /> <b style="color: red">Consult with your hosting provider for more information.</b>');
			}

			// document classes
			if ($this->journal3->isAdmin()) {
				$this->journal3->document->addClass('is-admin');
			}

			if ($this->journal3->isCustomer()) {
				$this->journal3->document->addClass('is-customer');
			} else {
				$this->journal3->document->addClass('is-guest');
			}

			if ($this->config->get('config_maintenance') && !$this->journal3->isAdmin()) {
				$this->journal3->document->addClass('maintenance-page');
			}

			// settings
			$this->load->controller('journal3/settings');

			// modernizr
			$this->journal3->document->addScript('catalog/view/theme/journal3/lib/modernizr/modernizr-custom.js');

			// jquery
			if ($this->journal3->isOC1()) {
				$this->journal3->document->addStyle('catalog/view/javascript/jquery/ui/themes/ui-lightness/jquery-ui-1.8.16.custom.css');
				$this->journal3->document->addScript('catalog/view/theme/journal3/lib/jquery/jquery-2.1.1.min.js');
				$this->journal3->document->addScript('catalog/view/theme/journal3/lib/jquery/jquery-migrate-1.2.1.min.js');
				$this->journal3->document->addScript('catalog/view/javascript/jquery/ui/jquery-ui-1.8.16.custom.min.js');
			} else {
				if ($this->journal3->isOC31()) {
					$this->journal3->document->addScript('catalog/view/theme/journal3/lib/jquery/jquery-3.3.1.js');
				} else {
					$this->journal3->document->addScript('catalog/view/theme/journal3/lib/jquery/jquery-2.1.1.min.js');
				}
			}

			// anime
			$this->journal3->document->addScript('catalog/view/theme/journal3/lib/anime/anime.min.js');

			// bootstrap
			if ($this->journal3->isOC1()) {
				$this->journal3->document->addStyle('catalog/view/theme/journal3/lib/bootstrap/css/bootstrap.min.css');
				$this->journal3->document->addStyle('catalog/view/theme/journal3/lib/font-awesome/css/font-awesome.min.css');
				$this->journal3->document->addScript('catalog/view/theme/journal3/lib/bootstrap/js/bootstrap.min.js');
			} else {
				$this->journal3->document->addStyle('catalog/view/javascript/bootstrap/css/bootstrap.min.css');
				$this->journal3->document->addStyle('catalog/view/javascript/font-awesome/css/font-awesome.min.css');

				if ($this->journal3->isOC31()) {
					$this->journal3->document->addScript('catalog/view/javascript/bootstrap/js/popper.min.js');
				}

				$this->journal3->document->addScript('catalog/view/javascript/bootstrap/js/bootstrap.min.js');
			}

			// bootstrap rtl
			if ($this->language->get('direction') === 'rtl') {
				$this->journal3->document->addStyle('catalog/view/theme/journal3/lib/bootstrap-rtl/bootstrap-rtl.min.css');
			}

			// lazy sizes
			if ($this->journal3->settings->get('performanceLazyLoadImagesStatus')) {
				$this->journal3->document->addScript('catalog/view/theme/journal3/lib/lazysizes/lazysizes.min.js', 'footer');
				$this->journal3->document->addScript('catalog/view/theme/journal3/lib/lazysizes/intersection-observer.js', 'footer');
			}

			// icons
			if (is_file(DIR_TEMPLATE . 'journal3/icons_custom/style.css')) {
				$icons = 'icons_custom';
			} else {
				$icons = 'icons';
			}

			if (is_file(DIR_TEMPLATE . 'journal3/' . $icons . '/style.minimal.css')) {
				$this->journal3->document->addStyle('catalog/view/theme/journal3/' . $icons . '/style.minimal.css');
			} else {
				$this->journal3->document->addStyle('catalog/view/theme/journal3/' . $icons . '/style.css');
			}

			// common.js
			$this->journal3->document->addScript('catalog/view/javascript/common.js');

			// countdown
			$this->journal3->document->addScript('catalog/view/theme/journal3/lib/countdown/jquery.countdown.min.js', 'footer');

			// inobounce
			if ($this->journal3->document->isMobile()) {
				$this->journal3->document->addScript('catalog/view/theme/journal3/lib/inobounce/inobounce.min.js', 'footer');
			}

			// typeahead
			if ($this->journal3->settings->get('searchStyleSearchAutoSuggestStatus')) {
				$this->journal3->document->addScript('catalog/view/theme/journal3/lib/typeahead/typeahead.jquery.min.js', 'footer');
			}

			// hover intent
			$this->journal3->document->addScript('catalog/view/theme/journal3/lib/hoverintent/jquery.hoverIntent.min.js');

			// sticky
			//$this->journal3->document->addScript('catalog/view/theme/journal3/lib/sticky/sticky.min.js', 'footer');

			// infinite scroll
			if (in_array(Arr::get($this->request->get, 'route', ''), array(
				'product/catalog',
				'product/category',
				'product/manufacturer/info',
				'product/search',
				'product/special',
			))) {
				$this->journal3->document->addScript('catalog/view/theme/journal3/lib/ias/jquery-ias.min.js', 'footer');
			}

			// cookie
			$this->journal3->document->addScript('catalog/view/theme/journal3/lib/cookie/cookie.js', 'footer');

			// admin
			if ($this->journal3->isAdmin()) {
				$this->journal3->document->addScript('catalog/view/theme/journal3/js/admin.js', 'footer');
			}

			// product extras
			$this->load->controller('journal3/product/extras', array('module_type' => 'product_label'));
			$this->load->controller('journal3/product/extras', array('module_type' => 'product_exclude_button'));
			$this->load->controller('journal3/product/extras', array('module_type' => 'product_extra_button'));
			$this->load->controller('journal3/product/extras', array('module_type' => 'product_blocks'));
			$this->load->controller('journal3/product/extras', array('module_type' => 'product_tabs'));
			$this->load->controller('journal3/product/second_image');
			$this->load->controller('journal3/product/countdown');

			// mega menu info blocks
			if ($this->journal3->settings->get('headerType') === 'mega' && $this->journal3->settings->get('infoBlocksModule')) {
				$this->journal3->settings->set('headerInfoBlocks', $this->load->controller('journal3/info_blocks', array(
					'module_id'   => $this->journal3->settings->get('infoBlocksModule'),
					'module_type' => 'info_blocks',
				)));
			}
		}
	}

	public function error() {
		if (!defined('JOURNAL3_INSTALLED')) {
			return;
		}

		if (
			($this->config->get('config_theme') === 'theme_default' || $this->config->get('config_theme') === 'default') &&
			($this->config->get('config_template') === 'journal3' || $this->config->get('theme_default_directory') === 'journal3')) {
			$this->print_error('Journal3 must be activated from System > Settings > Your Store > General > Theme and not from Extension > Extension > Themes (like in Journal2).');
		}

		$this->response->redirect($this->url->link('common/home'));
	}

	public function print_error($error) {
		echo "
			<style>
				.content {
					font-family: sans-serif;
					margin: 30px;
				}
			</style>
			<div class=\"content\">
				<h2>No Skins Found</h2>
				<p>" . $error . "</p>
			</div>
		";

		exit;
	}
}

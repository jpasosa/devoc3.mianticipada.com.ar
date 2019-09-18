<?php

use Journal3\Opencart\Controller;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Layout extends Controller {

	private static $MODULES = array(
		'popup',
		'notification',
		'header_notice',
		'bottom_menu',
		'side_menu',
		'fullscreen_slider',
		'background_slider',
	);

	private static $POSITIONS = array(
		'column_left',
		'column_right',
		'content_top',
		'content_bottom',
		'top',
		'bottom',
		'header_top',
		'footer_top',
		'footer_bottom',
	);

	private static $layout;

	public function index($position) {
		if ($this->config->get('config_maintenance') && !$this->journal3->isAdmin()) {
			return null;
		}

		if (static::$layout === null) {
			$this->load->model('design/layout');
			$this->load->model('journal3/layout');
			$this->load->model('journal3/module');

			if (isset($this->request->get['route'])) {
				$route = (string)$this->request->get['route'];
			} else {
				$route = 'common/home';
			}

			$this->journal3->document->addClass('route-' . str_replace('/', '-', $route));
			$this->journal3->document->setPageRoute($route);

			$layout_id = 0;

			if ($route == 'product/category' && isset($this->request->get['path'])) {
				$path = explode('_', (string)$this->request->get['path']);
				$category_id = end($path);

				$this->load->model('catalog/category');

				$layout_id = $this->model_catalog_category->getCategoryLayoutId($category_id);

				$this->journal3->document->setPageId($category_id);

				$this->journal3->document->addClass('category-' . $category_id);
			}

			if ($route == 'product/manufacturer/info' && isset($this->request->get['manufacturer_id'])) {
				$manufacturer_id = $this->request->get['manufacturer_id'];

				$this->journal3->document->setPageId($manufacturer_id);

				$this->journal3->document->addClass('manufacturer-' . $manufacturer_id);
			}

			if ($route == 'product/product' && isset($this->request->get['product_id'])) {
				$product_id = $this->request->get['product_id'];

				$this->load->model('catalog/product');

				$layout_id = $this->model_catalog_product->getProductLayoutId($product_id);

				$this->journal3->document->setPageId($product_id);

				$this->journal3->document->addClass('product-' . $product_id);
			}

			if ($route == 'information/information' && isset($this->request->get['information_id'])) {
				$information_id = $this->request->get['information_id'];

				$this->load->model('catalog/information');

				$layout_id = $this->model_catalog_information->getInformationLayoutId($information_id);

				$this->journal3->document->setPageId($information_id);

				$this->journal3->document->addClass('information-' . $information_id);
			}

			if ($route == 'journal3/blog' && isset($this->request->get['journal_blog_category_id'])) {
				$journal_blog_category_id = $this->request->get['journal_blog_category_id'];

				$layout_id = $this->model_journal3_blog->getBlogCategoryLayoutId($journal_blog_category_id);

				$this->journal3->document->setPageId($journal_blog_category_id);

				$this->journal3->document->addClass('blog-category-' . $journal_blog_category_id);
			}

			if ($route == 'journal3/blog/post' && isset($this->request->get['journal_blog_post_id'])) {
				$journal_blog_post_id = $this->request->get['journal_blog_post_id'];

				$layout_id = $this->model_journal3_blog->getBlogPostLayoutId($journal_blog_post_id);

				$this->journal3->document->setPageId($journal_blog_post_id);

				$this->journal3->document->addClass('blog-post-' . $journal_blog_post_id);
			}

			if (!$layout_id) {
				$layout_id = $this->model_design_layout->getLayout($route);
			}

			if (!$layout_id) {
				$layout_id = $this->config->get('config_layout_id');
			}

			$this->journal3->document->addClass('layout-' . $layout_id);

			$this->journal3->document->setLayoutId($layout_id);

			if ($this->journal3->document->isPopup()) {
				self::$layout = false;

				return null;
			}

			$this->_cache_key = 'layout.' . $layout_id;

			if ($this->_cache === false) {
				$layout_data = $this->model_journal3_layout->get($layout_id);

				$layout_positions = Arr::get($layout_data, 'enabledPositions', array());

				$cache = array(
					'settings' => array(),
					'php'      => array(),
					'js'       => array(),
					'fonts'    => array(),
					'css'      => '',
				);

				$parser = new Parser('layout/general', Arr::get($layout_data, 'general'), null, array($layout_id));

				$cache['php'] += $parser->getPhp();
				$cache['css'] .= $parser->getCss();

				foreach (static::$POSITIONS as $POSITION) {
					$data = array(
						'rows'         => array(),
						'grid_classes' => array('grid-rows'),
					);

					$cache['settings'][$POSITION] = $data;

					if (!in_array($POSITION, $layout_positions)) {
						continue;
					}

					$prefix = str_replace('_', '-', $POSITION);

					$row_id = 0;

					foreach (Arr::get($layout_data, 'positions.' . $POSITION . '.rows', array()) as $row) {
						$row_id++;

						$parser = new Parser('layout/row', Arr::get($row, 'options'), null, Arr::trim(array($prefix, $row_id)));

						if ($parser->getSetting('status') === false) {
							continue;
						}

						$cache['css'] .= $parser->getCss();
						$fonts = $parser->getFonts();
						$cache['fonts'] = Arr::merge($cache['fonts'], $fonts);

						$data['rows'][$row_id] = array_merge_recursive(
							$parser->getPhp(),
							array(
								'classes' => array('grid-row', 'grid-row-' . $prefix . '-' . $row_id),
								'columns' => array(),
							)
						);

						$column_id = 0;

						foreach (Arr::get($row, 'columns', array()) as $column) {
							$column_id++;

							$parser = new Parser('layout/column', Arr::get($column, 'options'), null, Arr::trim(array($prefix, $row_id, $column_id)));

							if ($parser->getSetting('status') === false) {
								continue;
							}

							$cache['css'] .= $parser->getCss();
							$fonts = $parser->getFonts();
							$cache['fonts'] = Arr::merge($cache['fonts'], $fonts);

							$data['rows'][$row_id]['columns'][$column_id] = array_merge_recursive(
								$parser->getPhp(),
								array(
									'classes' => array('grid-col', 'grid-col-' . $prefix . '-' . $row_id . '-' . $column_id),
									'items'   => array(),
								)
							);

							$module_id = 0;

							foreach (Arr::get($column, 'items', array()) as $module) {
								// disable columns on mobile but allow filter module
								if ($this->journal3->document->isTablet()) {
									if ($POSITION === 'column_left' && !$this->journal3->settings->get('globalPageColumnLeftTabletStatus')) {
										if (Arr::get($module, 'item.type') !== 'filter') {
											continue;
										}
									}

									if ($POSITION === 'column_right' && !$this->journal3->settings->get('globalPageColumnRightTabletStatus')) {
										if (Arr::get($module, 'item.type') !== 'filter') {
											continue;
										}
									}
								}

								if ($this->journal3->document->isPhone() && ($POSITION === 'column_left' || $position === 'column_right')) {
									if (Arr::get($module, 'item.type') !== 'filter') {
										continue;
									}
								}

								$module_id++;

								$parser = new Parser('layout/module', Arr::get($module, 'options'), null, Arr::trim(array($prefix, $row_id, $column_id, $module_id)));

								$cache['css'] .= $parser->getCss();
								$fonts = $parser->getFonts();
								$cache['fonts'] = Arr::merge($cache['fonts'], $fonts);

								$data['rows'][$row_id]['columns'][$column_id]['items'][$module_id] = array_merge_recursive(
									$parser->getPhp(),
									array(
										'classes' => array('grid-item', 'grid-item-' . $prefix . '-' . $row_id . '-' . $column_id . '-' . $module_id),
										'item'    => Arr::get($module, 'item'),
									)
								);
							}
						}

					}

					$cache['settings'][$POSITION] = $data;
				}

				foreach (static::$MODULES as $MODULE) {
					if (Arr::get($layout_data, 'positions.absolute.' . $MODULE)) {
						$module_id = Arr::get($layout_data, 'positions.absolute.' . $MODULE);

						if ($module_id) {
							$cache['settings']['absolute'][] = array(
								'module_id'   => $module_id,
								'module_type' => $MODULE,
							);
						}
					} else {
						$module_id = Arr::get($layout_data, 'positions.global.' . $MODULE);

						if ($module_id) {
							$cache['settings']['global'][] = array(
								'module_id'   => $module_id,
								'module_type' => $MODULE,
							);
						}
					}
				}

				$this->_cache = $cache;
			}

			switch (Arr::get($this->_cache['php'], 'pageStyleBoxedLayout')) {
				case 'boxed':
					$this->journal3->document->addClass('boxed-layout');
					break;

				case 'fullwidth':
					$this->journal3->document->removeClass('boxed-layout');
					break;
			}

			$this->journal3->document->addCss($this->_cache['css']);
			$this->journal3->document->addFonts($this->_cache['fonts']);

			foreach (static::$POSITIONS as $POSITION) {
				$data = $this->_cache['settings'][$POSITION];

				$grid = $this->renderGrid($data, !in_array($POSITION, array()));

				$data['modules'] = array();

				if ($grid) {
					$data['modules'][] = $grid;
				}

//				$modules = $this->model_design_layout->getLayoutModules($layout_id, $POSITION);
//
//				$this->load->model('setting/module');
//
//				foreach ($modules as $module) {
//					$part = explode('.', $module['code']);
//
//					if (isset($part[0]) && $this->config->get('module_' . $part[0] . '_status')) {
//						$module_data = $this->load->controller('extension/module/' . $part[0]);
//
//						if ($module_data) {
//							$data['modules'][] = $module_data;
//						}
//					}
//
//					if (isset($part[1])) {
//						$setting_info = $this->model_setting_module->getModule($part[1]);
//
//						if ($setting_info && $setting_info['status']) {
//							$output = $this->load->controller('extension/module/' . $part[0], $setting_info);
//
//							if ($output) {
//								$data['modules'][] = $output;
//							}
//						}
//					}
//				}

				if ($data['modules']) {
					self::$layout[$POSITION] = $this->renderView('common/' . $POSITION, $data);
				} else {
					self::$layout[$POSITION] = null;
				}
			}

			foreach (Arr::get($this->_cache['settings'], 'global', array()) as $module) {
				$result = $this->load->controller('journal3/' . $module['module_type'], $module);

				if ($result) {
					self::$layout[$module['module_type']] = $result;
				}
			}

			foreach (Arr::get($this->_cache['settings'], 'absolute', array()) as $module) {
				$result = $this->load->controller('journal3/' . $module['module_type'], $module);

				if ($result) {
					self::$layout[$module['module_type']] = $result;
				}
			}

			if (self::$layout['column_left'] && self::$layout['column_right']) {
				$this->journal3->document->addClass('two-column');
				$this->journal3->document->addJs(array('columnsCount' => 2));
				$this->journal3->settings->set('columnsCount', 2);
			} else if (self::$layout['column_left'] || self::$layout['column_right']) {
				$this->journal3->document->addClass('one-column');
				$this->journal3->document->addJs(array('columnsCount' => 1));
			} else {
				$this->journal3->document->addJs(array('columnsCount' => 0));
			}

			if (self::$layout['column_left'] && self::$layout['column_right']) {
				$this->journal3->document->addClass('column-left column-right');
			} else if (self::$layout['column_left']) {
				$this->journal3->document->addClass('column-left');
			} else if (self::$layout['column_right']) {
				$this->journal3->document->addClass('column-right');
			}

		}

		return Arr::get(self::$layout, $position);
	}

}

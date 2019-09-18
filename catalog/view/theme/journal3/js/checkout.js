$(function () {
	window['_QuickCheckout'] = new Vue({
		data: window['_QuickCheckoutData'],
		el: '.quick-checkout',
		template: '#quick-checkout',
		mounted: function () {
			var self = this;

			self.payment();

			$(self.$el).find('.date').datetimepicker({
				pickTime: false
			});

			$(self.$el).find('.datetime').datetimepicker({
				pickDate: true,
				pickTime: true
			});

			$(self.$el).find('.time').datetimepicker({
				pickDate: false
			});

			$(self.$el).find('.date, .datetime, .time').on('dp.change', function (event) {
				$(event.target).find('input')[0].dispatchEvent(new Event('change'));
			});

			$(self.$el).find('[placeholder]').each(function () {
				$(this).attr('placeholder', he.decode($(this).attr('placeholder')));
			});
		},
		updated: function () {
			var self = this;

			$(self.$el).find('.date').datetimepicker({
				pickTime: false
			});

			$(self.$el).find('.datetime').datetimepicker({
				pickDate: true,
				pickTime: true
			});

			$(self.$el).find('.time').datetimepicker({
				pickDate: false
			});

			$(self.$el).find('.date, .datetime, .time').on('dp.change', function (event) {
				$(event.target).find('input')[0].dispatchEvent(new Event('change'));
			});

			$(self.$el).find('[placeholder]').each(function () {
				$(this).attr('placeholder', he.decode($(this).attr('placeholder')));
			});
		},
		beforeUpdate: function () {
			$(this.$el).find('.date, .datetime, .time').each(function () {
				$(this).datepicker('hide').datepicker('destroy');
			});
		},
		beforeDestroy: function () {
			$(this.$el).find('.date, .datetime, .time').each(function () {
				$(this).datepicker('hide').datepicker('destroy');
			});
		},
		methods: {
			saveDateTime: function (path, key, event) {
				this.order_data[path][key] = event.target.value;
				this.save();
			},
			ajax: function (obj) {
				return $.ajax($.extend({
					cache: false,
					type: 'post',
					dataType: 'json',
					beforeSend: function () {
						$('#quick-checkout-button-confirm, #button-login').button('loading');
					},
					error: function (xhr, ajaxOptions, thrownError) {
						$('#quick-checkout-button-confirm, #button-login').button('reset');

						if (xhr.statusText !== 'abort') {
							console.warn(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
						}
					}
				}, obj));
			},
			payment: function () {
				if (window['_QuickCheckoutAjaxPayment']) {
					window['_QuickCheckoutAjaxPayment'].abort();
				}

				window['_QuickCheckoutPaymentData'] = {};

				$('.quick-checkout-payment').find('input[type="text"], input[type="checkbox"], input[type="radio"], select').each(function () {
					window['_QuickCheckoutPaymentData'][$(this).attr('name')] = $(this).val();
				});

				$('.quick-checkout-payment').html('<div class="journal-loading-overlay"><div class="journal-loading"><i class="fa fa-spinner fa-spin"></i></div></div>');

				window['_QuickCheckoutAjaxPayment'] = this.ajax({
					type: 'get',
					dataType: 'html',
					url: 'index.php?route=journal3/checkout/payment',
					success: function (data) {
						$('.quick-checkout-payment').html(data);

						$.each(window['_QuickCheckoutPaymentData'], function (k, v) {
							$('.quick-checkout-payment').find('[name="' + k + '"]').val(v);
						});

						window['_QuickCheckoutAjaxPayment'] = null;

						$('#quick-checkout-button-confirm, #button-login').button('reset');
					}
				})
			},
			updateCartItemQuantity: function (index, value) {
				this.$data.products[index].quantity = parseInt(this.$data.products[index].quantity) + parseInt(value);
				this.updateCartItem(this.$data.products[index]);
			},
			updateCartItemQuantityValue: function (index, value) {
				this.$data.products[index].quantity = parseInt(value);
				this.updateCartItem(this.$data.products[index]);
			},
			updateCartItem: function (product) {
				var self = this;

				this.ajax({
					url: 'index.php?route=journal3/checkout/cart_update',
					data: {
						key: product.cart_id,
						quantity: product.quantity,
						account: this.account,
						order_data: this.order_data,
						password: this.password,
						password2: this.password2,
						same_address: this.same_address,
						newsletter: this.newsletter,
						privacy: this.privacy,
						agree: this.agree,
						payment_address_type: this.payment_address_type,
						shipping_address_type: this.shipping_address_type,
						coupon: this.coupon,
						voucher: this.voucher,
						reward: this.reward
					},
					success: function (json) {
						self.update(json);
					}
				});
			},
			deleteCartItem: function (product) {
				var self = this;

				this.ajax({
					url: 'index.php?route=journal3/checkout/cart_delete',
					data: {
						key: product.cart_id,
						account: this.account,
						order_data: this.order_data,
						password: this.password,
						password2: this.password2,
						same_address: this.same_address,
						newsletter: this.newsletter,
						privacy: this.privacy,
						agree: this.agree,
						payment_address_type: this.payment_address_type,
						shipping_address_type: this.shipping_address_type,
						coupon: this.coupon,
						voucher: this.voucher,
						reward: this.reward
					},
					success: function (json) {
						self.update(json);
					}
				});
			},
			applyCoupon: function () {
			},
			deleteVoucher: function (voucher) {
				var self = this;

				this.ajax({
					url: 'index.php?route=journal3/checkout/cart_delete',
					data: {
						key: voucher.key,
						account: this.account,
						order_data: this.order_data,
						password: this.password,
						password2: this.password2,
						same_address: this.same_address,
						newsletter: this.newsletter,
						privacy: this.privacy,
						agree: this.agree,
						payment_address_type: this.payment_address_type,
						shipping_address_type: this.shipping_address_type,
						coupon: this.coupon,
						voucher: this.voucher,
						reward: this.reward
					},
					success: function (json) {
						self.update(json);
					}
				});
			},
			change: function () {
				this.$data.changed = true;
			},
			changeAddressType: function (type, value) {
				if (value === 'new') {
					this.$data.order_data[type + '_address_id'] = '';
				} else {
					this.$data.order_data[type + '_address_id'] = this.default_address_id;
				}
			},
			checkSave: function (confirm) {
				if (this.$data.changed === true) {
					this.$data.changed = false;

					this.save(confirm);
				}
			},
			save: function (confirm) {
				this.error = {};

				this.ajax({
					url: 'index.php?route=journal3/checkout/save' + (confirm ? '&confirm=true' : ''),
					data: {
						account: this.account,
						order_data: this.order_data,
						password: this.password,
						password2: this.password2,
						same_address: this.same_address,
						newsletter: this.newsletter,
						privacy: this.privacy,
						agree: this.agree,
						payment_address_type: this.payment_address_type,
						shipping_address_type: this.shipping_address_type,
						coupon: this.coupon,
						voucher: this.voucher,
						reward: this.reward
					},
					success: function (json) {
						this.update(json, confirm);
					}.bind(this)
				});
			},
			update: function (json, confirm) {
				if (json.response.redirect) {
					window.location = json.response.redirect;
				} else {
					this.custom_fields = json.response.custom_fields;
					this.shipping_methods = json.response.shipping_methods;
					this.payment_methods = json.response.payment_methods;
					this.shipping_zones = json.response.shipping_zones;
					this.payment_zones = json.response.payment_zones;
					this.order_data.shipping_code = json.response.order_data.shipping_code;
					this.order_data.payment_code = json.response.order_data.payment_code;
					this.order_data.shipping_country_id = json.response.order_data.shipping_country_id;
					this.order_data.payment_country_id = json.response.order_data.payment_country_id;
					this.order_data.shipping_zone_id = json.response.order_data.shipping_zone_id;
					this.order_data.payment_zone_id = json.response.order_data.payment_zone_id;
					this.totals = json.response.totals;
					this.products = json.response.products;
					this.stock_warning = json.response.stock_warning;
					this.vouchers = json.response.vouchers;
					this.$data.total = json.response.total;
					this.session = json.response.session;
					this.error = json.response.error;

					$('#cart-total').html(json.response.total);
					$('.cart-content > ul').html($(json.response.cart).find('.cart-content > ul').html());
					$('#cart-items.count-badge').html(json.response.total_items);

					if (json.response.error) {
						$('#quick-checkout-button-confirm').button('reset');

						try {
							console.error(JSON.stringify(json.response.error, null, 2));
						} catch (e) {
						}

						if (json.response.error.payment_code) {
							alert(json.response.error.payment_code);
						}

						if (json.response.error.shipping_code) {
							alert(json.response.error.shipping_code);
						}

						setTimeout(function () {
							try {
								$('html, body').animate({ scrollTop: $('.form-group .text-danger').closest('.form-group').offset().top - 50 }, 'slow');
							} catch (e) {
							}
						}, 300);
					} else {
						if (confirm) {
							var btns = ['input[type="button"]', 'input[type="submit"]', 'button[type="submit"]', '#button-confirm', '.buttons a'];
							var $btn = $('.quick-checkout-payment').find(btns.join(', ')).first();

							if ($btn.attr('href')) {
								window.location = $btn.attr('href');
							} else {
								$btn.trigger('click');
							}
						} else {
							this.payment();
						}
					}
				}
			},
			login: function () {
				var data = {
					email: this.login_email,
					password: this.login_password
				};

				this.ajax({
					url: 'index.php?route=account/login',
					data: data,
					success: function (json) {
						if (json.status === 'success') {
							parent.window.location.reload();
						} else {
							$('#quick-checkout-button-confirm, #button-login').button('reset');

							if (json.response.warning) {
								alert(json.response.warning);
							}
						}
					}
				});
			},
			srcSet: function (image, image2x) {
				return image + ' 1x, ' + image2x + ' 2x'
			},
			upload: function (path, key, event) {
				var node = this;

				$('#form-upload').remove();

				$('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display: none;"><input type="file" name="file" /></form>');

				$('#form-upload input[name=\'file\']').trigger('click');

				if (typeof timer != 'undefined') {
					clearInterval(timer);
				}

				timer = setInterval(function() {
					if ($('#form-upload input[name=\'file\']').val() != '') {
						clearInterval(timer);

						$.ajax({
							url: 'index.php?route=tool/upload',
							type: 'post',
							dataType: 'json',
							data: new FormData($('#form-upload')[0]),
							cache: false,
							contentType: false,
							processData: false,
							beforeSend: function() {
								$('#quick-checkout-button-confirm, #button-login, .custom-field button').button('loading');
							},
							complete: function() {
								$('#quick-checkout-button-confirm, #button-login, .custom-field button').button('reset');
							},
							success: function(json) {
								$('.text-danger').remove();

								if (json['error']) {
									alert(json['error']);
								}

								if (json['success']) {
									node.order_data[path][key] = json['code'];
									node.save();

									alert(json['success']);
								}
							},
							error: function(xhr, ajaxOptions, thrownError) {
								alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
							}
						});
					}
				}, 500);
			}
		}
	});
});

$(document).ajaxSuccess(function (event, xhr, settings, data) {
	if (settings.dataType === 'json') {
		if (data.error) {
			$('#quick-checkout-button-confirm').button('reset');
			_QuickCheckout.payment();
		}
	}
});

function triggerLoadingOn() {
	$('#quick-checkout-button-confirm, #button-login').button('loading');
}

function triggerLoadingOff() {
	$('#quick-checkout-button-confirm, #button-login').button('reset');
}

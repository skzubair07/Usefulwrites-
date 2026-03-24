(function ($) {
	'use strict';

	let activeProduct = 0;

	$(document).on('click', '.cw-open-lead', function () {
		const wrap = $(this).closest('.cw-buy-wrap');
		activeProduct = parseInt(wrap.data('product-id'), 10);
		$('#cw-lead-form [name="product_id"]').val(activeProduct);
		$('#cw-lead-modal').fadeIn(150);
	});

	$(document).on('click', '.cw-close', function () {
		$('#cw-lead-modal').fadeOut(150);
	});

	$(document).on('submit', '#cw-lead-form', function (e) {
		e.preventDefault();
		const formData = $(this).serializeArray();
		formData.push({ name: 'action', value: 'cw_save_lead' });
		formData.push({ name: 'nonce', value: cwShop.nonce });

		$.post(cwShop.ajaxUrl, formData)
			.done(function (leadResp) {
				if (!leadResp.success) {
					$('.cw-lead-response').text(leadResp.data?.message || cwShop.message);
					return;
				}
				createOrder(formData);
			})
			.fail(function () {
				$('.cw-lead-response').text(cwShop.message);
			});
	});

	function createOrder(formData) {
		const orderData = {
			action: 'cw_create_order',
			nonce: cwShop.nonce,
			product_id: activeProduct,
			name: valueFrom(formData, 'name'),
			email: valueFrom(formData, 'email'),
			phone: valueFrom(formData, 'phone')
		};
		$.post(cwShop.ajaxUrl, orderData)
			.done(function (resp) {
				if (!resp.success) {
					$('.cw-lead-response').text(resp.data?.message || cwShop.message);
					return;
				}
				launchRazorpay(resp.data, orderData);
			})
			.fail(function () {
				$('.cw-lead-response').text(cwShop.message);
			});
	}

	function launchRazorpay(order, customer) {
		const options = {
			key: order.key,
			amount: order.amount,
			currency: order.currency,
			name: order.product_title,
			description: 'Digital purchase',
			order_id: order.order_id,
			prefill: {
				name: customer.name,
				email: customer.email,
				contact: customer.phone
			},
			handler: function (response) {
				verifyPayment(response);
			},
			modal: {
				ondismiss: function () {
					$('.cw-lead-response').text('Payment cancelled.');
				}
			}
		};
		const rz = new Razorpay(options);
		rz.open();
	}

	function verifyPayment(response) {
		response.action = 'cw_verify_payment';
		response.nonce = cwShop.nonce;
		$.post(cwShop.ajaxUrl, response)
			.done(function (resp) {
				if (!resp.success) {
					$('.cw-lead-response').text(resp.data?.message || cwShop.message);
					return;
				}
				$('#cw-lead-modal').hide();
				$('.cw-payment-response').html('<a href="' + resp.data.download_link + '">Download Now</a>');
			})
			.fail(function () {
				$('.cw-lead-response').text(cwShop.message);
			});
	}

	function valueFrom(items, key) {
		for (let i = 0; i < items.length; i++) {
			if (items[i].name === key) {
				return items[i].value;
			}
		}
		return '';
	}

	$(document).on('submit', '.cw-withdraw-form', function (e) {
		e.preventDefault();
		const form = $(this);
		const data = form.serializeArray();
		data.push({ name: 'action', value: 'cw_withdraw_request' });
		$.post(cwShop.ajaxUrl, data).done(function (resp) {
			form.siblings('.cw-withdraw-response').text(resp.data?.message || cwShop.message);
		});
	});
})(jQuery);

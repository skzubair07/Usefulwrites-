(function ($) {
	'use strict';

	function setMessage($wrap, msg, type) {
		$wrap.find('.cwqsp-message').removeClass('ok err').addClass(type).text(msg);
	}

	function setLoading($wrap, loading) {
		$wrap.toggleClass('is-loading', loading);
	}

	$(document).on('click', '.cwqsp-buy-btn', function () {
		var $wrap = $(this).closest('.cwqsp-wrap');
		var productId = parseInt($wrap.data('product-id'), 10);
		var name = $.trim($wrap.find('.cwqsp-name').val());
		var email = $.trim($wrap.find('.cwqsp-email').val());
		var phone = $.trim($wrap.find('.cwqsp-phone').val());

		if (!productId || !name || !email || !phone) {
			setMessage($wrap, 'Please fill all fields before payment.', 'err');
			return;
		}

		setLoading($wrap, true);
		setMessage($wrap, cwqspData.checkoutText, 'ok');

		$.post(cwqspData.ajaxUrl, {
			action: 'cwqsp_create_order',
			nonce: cwqspData.nonce,
			product_id: productId,
			name: name,
			email: email,
			phone: phone
		}).done(function (res) {
			if (!res.success) {
				setLoading($wrap, false);
				setMessage($wrap, res.data && res.data.message ? res.data.message : cwqspData.failedText, 'err');
				return;
			}

			var data = res.data;
			var rzp = new Razorpay({
				key: data.key,
				amount: data.amount,
				currency: data.currency,
				name: data.name,
				description: data.desc,
				order_id: data.order_id,
				prefill: { name: name, email: email, contact: phone },
				handler: function (response) {
					$.post(cwqspData.ajaxUrl, {
						action: 'cwqsp_verify_payment',
						nonce: cwqspData.nonce,
						razorpay_order_id: response.razorpay_order_id,
						razorpay_payment_id: response.razorpay_payment_id,
						razorpay_signature: response.razorpay_signature
					}).done(function (verifyRes) {
						setLoading($wrap, false);
						if (verifyRes.success && verifyRes.data.redirect_url) {
							setMessage($wrap, verifyRes.data.message, 'ok');
							window.location.href = verifyRes.data.redirect_url;
						} else {
							setMessage($wrap, verifyRes.data && verifyRes.data.message ? verifyRes.data.message : cwqspData.failedText, 'err');
						}
					}).fail(function () {
						setLoading($wrap, false);
						setMessage($wrap, cwqspData.failedText, 'err');
					});
				},
				modal: {
					ondismiss: function () {
						setLoading($wrap, false);
						setMessage($wrap, 'Payment popup closed. You can retry now.', 'err');
					}
				}
			});

			rzp.open();
		}).fail(function () {
			setLoading($wrap, false);
			setMessage($wrap, cwqspData.failedText, 'err');
		});
	});
})(jQuery);

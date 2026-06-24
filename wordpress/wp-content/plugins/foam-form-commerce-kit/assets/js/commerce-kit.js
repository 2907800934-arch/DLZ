(function ($) {
	'use strict';

	$(function () {
		var $stickyButton = $('.foam-sticky-cart__button');
		var $popup = $('.foam-exit-popup');
		var popupKey = 'foam_exit_popup_seen';

		function scrollToCart() {
			var $button = $('.single_add_to_cart_button').first();
			if ($button.length) {
				$('html, body').animate({ scrollTop: $button.offset().top - 120 }, 250);
				$button.trigger('focus');
			}
		}

		if ($stickyButton.length) {
			$stickyButton.on('click', function () {
				scrollToCart();
				$('.single_add_to_cart_button').first().trigger('click');
			});
		}

		$('.foam-scroll-cart').on('click', function (event) {
			event.preventDefault();
			scrollToCart();
		});

		function openPopup() {
			if (!$popup.length || window.localStorage.getItem(popupKey)) {
				return;
			}
			$popup.addClass('is-visible').attr('aria-hidden', 'false');
		}

		function closePopup() {
			if (!$popup.length) {
				return;
			}
			$popup.removeClass('is-visible').attr('aria-hidden', 'true');
			window.localStorage.setItem(popupKey, '1');
		}

		$(document).on('mouseleave', function (event) {
			if (event.clientY > 12) {
				return;
			}
			openPopup();
		});

		$('.foam-exit-popup__close, .foam-exit-popup__backdrop').on('click', function () {
			closePopup();
		});

		$('.foam-exit-popup__form').on('submit', function (event) {
			event.preventDefault();
			var $form = $(this);
			$form.replaceWith('<p class="foam-popup-success">' + foamFormCommerce.popupSuccessText + '</p>');
			window.localStorage.setItem(popupKey, '1');
		});
	});
}(jQuery));

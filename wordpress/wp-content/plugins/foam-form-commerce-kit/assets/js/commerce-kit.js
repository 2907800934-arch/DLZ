(function ($) {
	'use strict';

	$(function () {
		var $stickyButton = $('.foam-sticky-cart__button');
		var $popup = $('.foam-exit-popup');
		var $searchToggle = $('.foam-search-toggle');
		var $searchPanel = $('.foam-search-panel');
		var $searchClose = $('.foam-search-panel__close, .foam-search-panel__backdrop');
		var $footerSubscribeForm = $('.foam-footer-subscribe-form');
		var popupKey = 'foam_exit_popup_seen';
		var scrollMotionEnabled = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

		function openSearchPanel() {
			if (!$searchPanel.length) {
				return;
			}

			$searchPanel.prop('hidden', false);
			$searchToggle.attr('aria-expanded', 'true');
			window.setTimeout(function () {
				$('#foam-search-field').trigger('focus');
			}, 20);
		}

		function closeSearchPanel() {
			if (!$searchPanel.length) {
				return;
			}

			$searchPanel.prop('hidden', true);
			$searchToggle.attr('aria-expanded', 'false');
		}

		if ($searchToggle.length) {
			$searchToggle.on('click', function () {
				if ($searchPanel.prop('hidden')) {
					openSearchPanel();
				} else {
					closeSearchPanel();
				}
			});
		}

		if ($searchClose.length) {
			$searchClose.on('click', function () {
				closeSearchPanel();
			});
		}

		$(document).on('keydown', function (event) {
			if (event.key === 'Escape') {
				closeSearchPanel();
			}
		});

		$(window).on('scroll', function () {
			if (window.scrollY > 18) {
				document.body.classList.add('foam-nav-scrolled');
			} else {
				document.body.classList.remove('foam-nav-scrolled');
			}
		});

		function clamp(value, min, max) {
			return Math.min(Math.max(value, min), max);
		}

		function setRevealTargets() {
			var sectionSelectors = [
				'.foam-home-section',
				'.foam-home-features',
				'.foam-shop-toolbar',
				'.foam-editorial-feature',
				'.foam-editorial-gallery__card',
				'.foam-editorial-block',
				'.foam-technology-card',
				'.foam-review-card',
				'.foam-icon-card',
				'.foam-home-editorial-copy',
				'.foam-shop-promo__copy',
				'.foam-footer-subscribe',
				'.foam-footer-links > div'
			];

			var cardSelectors = [
				'.foam-collection-card',
				'.foam-lifestyle-tile',
				'.foam-home-editorial-media',
				'.foam-editorial-feature__media',
				'.foam-editorial-gallery__card',
				'.post-type-archive-product .woocommerce ul.products li.product',
				'.tax-product_cat .woocommerce ul.products li.product',
				'.tax-product_tag .woocommerce ul.products li.product'
			];

			$(sectionSelectors.join(',')).each(function (index) {
				$(this)
					.addClass('foam-scroll-reveal')
					.css('--foam-reveal-delay', (index % 6) * 70 + 'ms');
			});

			$(cardSelectors.join(',')).each(function (index) {
				$(this)
					.addClass('foam-scroll-card')
					.css('--foam-card-index', index);
			});
		}

		function observeReveals() {
			if (!scrollMotionEnabled || !('IntersectionObserver' in window)) {
				$('.foam-scroll-reveal').addClass('is-revealed');
				return;
			}

			var revealObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-revealed');
						revealObserver.unobserve(entry.target);
					}
				});
			}, {
				rootMargin: '0px 0px -8% 0px',
				threshold: 0.12
			});

			$('.foam-scroll-reveal').each(function () {
				revealObserver.observe(this);
			});
		}

		function updateScrollCards() {
			if (!scrollMotionEnabled) {
				return;
			}

			var viewportHeight = window.innerHeight || document.documentElement.clientHeight;

			$('.foam-scroll-card').each(function () {
				var rect = this.getBoundingClientRect();
				var midpoint = rect.top + rect.height / 2;
				var relative = (midpoint - viewportHeight / 2) / viewportHeight;
				var progress = clamp(1 - Math.abs(relative) * 1.9, 0, 1);
				var lift = (0.5 - progress) * 26;
				var scale = 0.972 + progress * 0.04;
				var opacity = 0.78 + progress * 0.22;
				var rotate = clamp(relative * -2.6, -2.6, 2.6);

				this.style.setProperty('--foam-scroll-y', lift.toFixed(2) + 'px');
				this.style.setProperty('--foam-scroll-scale', scale.toFixed(3));
				this.style.setProperty('--foam-scroll-opacity', opacity.toFixed(3));
				this.style.setProperty('--foam-scroll-rotate', rotate.toFixed(2) + 'deg');
			});
		}

		function bindScrollMotion() {
			if (!scrollMotionEnabled) {
				$('.foam-scroll-card').addClass('is-static');
				$('.foam-scroll-reveal').addClass('is-revealed');
				return;
			}

			var ticking = false;

			function requestUpdate() {
				if (ticking) {
					return;
				}

				ticking = true;
				window.requestAnimationFrame(function () {
					updateScrollCards();
					ticking = false;
				});
			}

			$(window).on('scroll resize', requestUpdate);
			requestUpdate();
		}

		setRevealTargets();
		observeReveals();
		bindScrollMotion();

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

		if ($footerSubscribeForm.length) {
			$footerSubscribeForm.on('submit', function (event) {
				event.preventDefault();
				var $form = $(this);
				var $message = $form.next('.foam-popup-success');

				if (!$message.length) {
					$message = $('<p class="foam-popup-success foam-popup-success--footer"></p>').insertAfter($form);
				}

				$message.text(foamFormCommerce.popupSuccessText);
				this.reset();
			});
		}
	});
}(jQuery));

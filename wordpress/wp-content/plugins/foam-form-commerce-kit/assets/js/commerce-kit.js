(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var stickyButton = document.querySelector('.foam-sticky-cart__button');
		var searchToggle = document.querySelector('.foam-search-toggle');
		var searchPanel = document.querySelector('.foam-search-panel');
		var searchField = document.querySelector('#foam-search-field');
		var searchCloseTargets = Array.from(
			document.querySelectorAll('.foam-search-panel__close, .foam-search-panel__backdrop')
		);
		var popup = document.querySelector('.foam-exit-popup');
		var popupForm = document.querySelector('.foam-exit-popup__form');
		var popupCloseTargets = Array.from(
			document.querySelectorAll('.foam-exit-popup__close, .foam-exit-popup__backdrop')
		);
		var footerSubscribeForms = Array.from(document.querySelectorAll('.foam-footer-subscribe-form'));
		var popupKey = 'foam_exit_popup_seen';
		var reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

		function canUseStorage() {
			try {
				return typeof window.localStorage !== 'undefined';
			} catch (error) {
				return false;
			}
		}

		function getPopupSeen() {
			return canUseStorage() ? window.localStorage.getItem(popupKey) : null;
		}

		function setPopupSeen() {
			if (canUseStorage()) {
				window.localStorage.setItem(popupKey, '1');
			}
		}

		function scrollToCart() {
			var cartButton = document.querySelector('.single_add_to_cart_button');

			if (!cartButton) {
				return;
			}

			var top =
				window.scrollY +
				cartButton.getBoundingClientRect().top -
				(window.innerWidth <= 921 ? 96 : 120);

			window.scrollTo({
				top: Math.max(0, top),
				behavior: reduceMotionQuery.matches ? 'auto' : 'smooth'
			});

			window.setTimeout(function () {
				cartButton.focus({ preventScroll: true });
			}, reduceMotionQuery.matches ? 0 : 180);
		}

		if (stickyButton) {
			stickyButton.addEventListener('click', function () {
				var cartButton = document.querySelector('.single_add_to_cart_button');

				scrollToCart();

				if (cartButton) {
					window.setTimeout(function () {
						cartButton.click();
					}, reduceMotionQuery.matches ? 0 : 220);
				}
			});
		}

		function openSearchPanel() {
			if (!searchPanel) {
				return;
			}

			searchPanel.hidden = false;

			if (searchToggle) {
				searchToggle.setAttribute('aria-expanded', 'true');
			}

			window.setTimeout(function () {
				if (searchField) {
					searchField.focus({ preventScroll: true });
				}
			}, 24);
		}

		function closeSearchPanel() {
			if (!searchPanel) {
				return;
			}

			searchPanel.hidden = true;

			if (searchToggle) {
				searchToggle.setAttribute('aria-expanded', 'false');
			}
		}

		if (searchToggle) {
			searchToggle.addEventListener('click', function () {
				if (searchPanel && searchPanel.hidden) {
					openSearchPanel();
				} else {
					closeSearchPanel();
				}
			});
		}

		searchCloseTargets.forEach(function (target) {
			target.addEventListener('click', closeSearchPanel);
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				closeSearchPanel();
				closePopup();
			}
		});

		function openPopup() {
			if (!popup || getPopupSeen()) {
				return;
			}

			popup.classList.add('is-visible');
			popup.setAttribute('aria-hidden', 'false');
		}

		function closePopup() {
			if (!popup) {
				return;
			}

			popup.classList.remove('is-visible');
			popup.setAttribute('aria-hidden', 'true');
			setPopupSeen();
		}

		if (popup) {
			document.addEventListener('mouseleave', function (event) {
				if (event.clientY > 12 || getPopupSeen()) {
					return;
				}

				openPopup();
			});
		}

		popupCloseTargets.forEach(function (target) {
			target.addEventListener('click', closePopup);
		});

		if (popupForm) {
			popupForm.addEventListener('submit', function (event) {
				event.preventDefault();

				var success = document.createElement('p');
				success.className = 'foam-popup-success';
				success.textContent =
					window.foamFormCommerce && window.foamFormCommerce.popupSuccessText
						? window.foamFormCommerce.popupSuccessText
						: 'Thank you. Your note has been saved for future email updates.';

				popupForm.replaceWith(success);
				setPopupSeen();
			});
		}

		footerSubscribeForms.forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();

				var message = form.nextElementSibling;

				if (!message || !message.classList.contains('foam-popup-success')) {
					message = document.createElement('p');
					message.className = 'foam-popup-success foam-popup-success--footer';
					form.insertAdjacentElement('afterend', message);
				}

				message.textContent =
					window.foamFormCommerce && window.foamFormCommerce.popupSuccessText
						? window.foamFormCommerce.popupSuccessText
						: 'Thank you. Your note has been saved for future email updates.';

				form.reset();
			});
		});

		Array.from(document.querySelectorAll('.foam-scroll-cart')).forEach(function (link) {
			link.addEventListener('click', function (event) {
				event.preventDefault();
				scrollToCart();
			});
		});
	});
}());

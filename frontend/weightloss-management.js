(function () {
	'use strict';

	document.querySelectorAll('.blc-wellness-menu [role="tablist"]').forEach(function (tabList) {
		var tabs = Array.prototype.slice.call(tabList.querySelectorAll('[data-blc-wellness-tab]'));

		function activateTab(tab, moveFocus) {
			tabs.forEach(function (item) {
				var panel = document.getElementById(item.getAttribute('aria-controls'));
				var selected = item === tab;

				item.setAttribute('aria-selected', selected ? 'true' : 'false');
				item.setAttribute('tabindex', selected ? '0' : '-1');

				if (panel) {
					panel.hidden = !selected;
				}
			});

			if (moveFocus) {
				tab.focus();
			}
		}

		tabs.forEach(function (tab, index) {
			tab.addEventListener('click', function () {
				activateTab(tab, false);
			});

			tab.addEventListener('keydown', function (event) {
				var nextIndex = index;

				if ('ArrowRight' === event.key) {
					nextIndex = (index + 1) % tabs.length;
				} else if ('ArrowLeft' === event.key) {
					nextIndex = (index - 1 + tabs.length) % tabs.length;
				} else if ('Home' === event.key) {
					nextIndex = 0;
				} else if ('End' === event.key) {
					nextIndex = tabs.length - 1;
				} else {
					return;
				}

				event.preventDefault();
				activateTab(tabs[nextIndex], true);
			});
		});
	});
}());

(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var tab = event.target.closest('.blc-weightloss-tab');
		if (tab) {
			activateWeightlossTab(tab);
			return;
		}

		var openButton = event.target.closest('.blc-weightloss-open');
		if (openButton) {
			var dialog = document.getElementById(openButton.getAttribute('aria-controls'));
			if (dialog && typeof dialog.showModal === 'function') {
				dialog.showModal();
			}
			return;
		}

		var closeButton = event.target.closest('.blc-weightloss-close');
		if (closeButton) {
			var closeDialog = closeButton.closest('dialog');
			if (closeDialog) {
				closeDialog.close();
			}
		}
	});

	document.addEventListener('keydown', function (event) {
		var tab = event.target.closest('.blc-weightloss-tab');
		if (!tab) { return; }
		var tabs = Array.prototype.slice.call(tab.closest('[data-weightloss-tabs]').querySelectorAll('.blc-weightloss-tab'));
		var index = tabs.indexOf(tab);
		if ('ArrowRight' === event.key) { index = (index + 1) % tabs.length; }
		else if ('ArrowLeft' === event.key) { index = (index - 1 + tabs.length) % tabs.length; }
		else if ('Home' === event.key) { index = 0; }
		else if ('End' === event.key) { index = tabs.length - 1; }
		else { return; }
		event.preventDefault();
		activateWeightlossTab(tabs[index]);
		tabs[index].focus();
	});

	document.addEventListener('click', function (event) {
		if (event.target.matches('.blc-weightloss-dialog')) {
			var bounds = event.target.getBoundingClientRect();
			var outside = event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
			if (outside) {
				event.target.close();
			}
		}
	});

	function activateWeightlossTab(selectedTab) {
		var tabContainer = selectedTab.closest('[data-weightloss-tabs]');
		var tabs = tabContainer.querySelectorAll('.blc-weightloss-tab');
		Array.prototype.forEach.call(tabs, function (tab) {
			var selected = tab === selectedTab;
			tab.classList.toggle('is-active', selected);
			tab.setAttribute('aria-selected', selected ? 'true' : 'false');
			tab.setAttribute('tabindex', selected ? '0' : '-1');
			var panel = document.getElementById(tab.getAttribute('aria-controls'));
			if (panel) { panel.hidden = !selected; }
		});
	}
}());

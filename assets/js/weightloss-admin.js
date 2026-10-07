(function () {
	'use strict';

	document.addEventListener('click', function (event) {
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

	document.addEventListener('click', function (event) {
		if (event.target.matches('.blc-weightloss-dialog')) {
			var bounds = event.target.getBoundingClientRect();
			var outside = event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
			if (outside) {
				event.target.close();
			}
		}
	});
}());

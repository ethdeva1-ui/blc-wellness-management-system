(function () {
	'use strict';

	function formatNumber(value, maximumFractionDigits) {
		return new Intl.NumberFormat(undefined, {
			maximumFractionDigits: maximumFractionDigits
		}).format(value);
	}

	function updateCalculator(calculator) {
		var drinkSelect = calculator.querySelector('.blc-drink-calculator__drink');
		var servingsInput = calculator.querySelector('.blc-drink-calculator__servings');
		var selectedDrink = drinkSelect.options[drinkSelect.selectedIndex];
		var servings = Number.parseFloat(servingsInput.value);

		if (!Number.isFinite(servings)) {
			return;
		}

		servings = Math.min(20, Math.max(0.25, servings));

		var sugar = Number.parseFloat(selectedDrink.dataset.sugar) * servings;
		var calories = Number.parseFloat(selectedDrink.dataset.calories) * servings;
		var portion = selectedDrink.dataset.serving;

		calculator.querySelector('.blc-drink-calculator__sugar').textContent = formatNumber(sugar, 1) + ' g';
		calculator.querySelector('.blc-drink-calculator__calories').textContent = formatNumber(calories, 0) + ' kcal';
		calculator.querySelector('.blc-drink-calculator__portion').textContent =
			'Portion size: ' + portion + ' · Total: ' + formatNumber(servings, 2) + ' ' + (servings === 1 ? 'serving' : 'servings');
	}

	document.querySelectorAll('.blc-drink-calculator').forEach(function (calculator) {
		var drinkSelect = calculator.querySelector('.blc-drink-calculator__drink');
		var servingsInput = calculator.querySelector('.blc-drink-calculator__servings');

		drinkSelect.addEventListener('change', function () {
			updateCalculator(calculator);
		});

		servingsInput.addEventListener('input', function () {
			if (servingsInput.value !== '') {
				updateCalculator(calculator);
			}
		});

		servingsInput.addEventListener('blur', function () {
			var servings = Number.parseFloat(servingsInput.value);

			if (!Number.isFinite(servings)) {
				servings = 1;
			}

			servingsInput.value = Math.min(20, Math.max(0.25, servings));
			updateCalculator(calculator);
		});

		updateCalculator(calculator);
	});
}());

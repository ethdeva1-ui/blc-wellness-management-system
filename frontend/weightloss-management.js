(function () {
	'use strict';

	var config = window.BLCWeightLossConfig || {};
	var activityOptions = [
		{ value: 'sedentary', title: 'Sedentary', detail: 'Little to no exercise.' },
		{ value: 'light', title: 'Lightly Active', detail: 'Light exercise or activity 1–3 days per week.' },
		{ value: 'moderate', title: 'Moderately Active', detail: 'Moderate exercise 3–5 days per week.' },
		{ value: 'very', title: 'Very Active', detail: 'Hard exercise 6–7 days per week.' },
		{ value: 'extreme', title: 'Extremely Active', detail: 'Very intense exercise or physically demanding work.' }
	];
	var paceOptions = [
		{ value: 'slow', title: 'Slow & Steady', detail: 'Approximately 0.25 kg per week.' },
		{ value: 'standard', title: 'Standard', detail: 'Approximately 0.5 kg per week.' },
		{ value: 'faster', title: 'Faster', detail: 'Approximately 0.75 kg per week.' },
		{ value: 'aggressive', title: 'Aggressive', detail: 'Approximately 1 kg per week. This may be reduced for safety.' }
	];
	var steps = [
		{ title: 'Which units would you like to use?', key: 'units' },
		{ title: 'What is your age?', key: 'age' },
		{ title: 'What is your sex?', key: 'sex' },
		{ title: 'How tall are you?', key: 'height' },
		{ title: 'What is your current weight?', key: 'current_weight_kg' },
		{ title: 'What is your goal weight?', key: 'goal_weight_kg' },
		{ title: 'How active are you on a typical week?', key: 'activity' },
		{ title: 'How quickly would you like to reach your goal?', key: 'pace' },
		{ title: 'Do you have a target date?', key: 'target_date' }
	];
	var root;
	var state;

	function escapeHtml(value) {
		return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
		});
	}

	function number(value, decimals) {
		return new Intl.NumberFormat(undefined, { maximumFractionDigits: decimals || 0 }).format(value);
	}

	function weight(kg) {
		if (!Number.isFinite(Number(kg))) {
			return '—';
		}
		return state.profile.units === 'imperial'
			? number(Number(kg) * 2.20462262, 1) + ' lb'
			: number(kg, 1) + ' kg';
	}

	function heightFields() {
		if (state.profile.units === 'imperial') {
			var totalInches = Number(state.profile.height_cm || 0) / 2.54;
			var feet = totalInches ? Math.floor(totalInches / 12) : '';
			var inches = totalInches ? Math.round(totalInches - (feet * 12)) : '';
			if (inches === 12) {
				feet += 1;
				inches = 0;
			}
			return '<div class="blc-wl-fields blc-wl-fields--two">' +
				'<label>Feet<input name="feet" type="number" min="3" max="8" step="1" value="' + escapeHtml(feet) + '" inputmode="numeric" required></label>' +
				'<label>Inches<input name="inches" type="number" min="0" max="11" step="1" value="' + escapeHtml(inches) + '" inputmode="numeric" required></label></div>';
		}
		return '<label class="blc-wl-field">Height in cm<input name="height_cm" type="number" min="100" max="250" step="0.1" value="' + escapeHtml(state.profile.height_cm || '') + '" inputmode="decimal" required></label>';
	}

	function displayedWeight(kg) {
		if (!kg) {
			return '';
		}
		return state.profile.units === 'imperial' ? (Number(kg) * 2.20462262).toFixed(1) : Number(kg).toFixed(1);
	}

	function minimumTargetDate() {
		var date = new Date((config.today || new Date().toISOString().slice(0, 10)) + 'T12:00:00Z');
		date.setUTCDate(date.getUTCDate() + 1);
		return date.toISOString().slice(0, 10);
	}

	function questionProgress() {
		var current = state.step + 1;
		var bits = '';
		for (var i = 0; i < steps.length; i += 1) {
			bits += '<span class="blc-wl-progress__dot' + (i < current ? ' is-complete' : '') + '"></span>';
		}
		return '<div class="blc-wl-progress" aria-label="Step ' + current + ' of ' + steps.length + '">' +
			'<div class="blc-wl-progress__track">' + bits + '</div>' +
			'<p>Step ' + current + ' of ' + steps.length + '</p></div>';
	}

	function renderWelcome(error) {
		root.innerHTML = '<div class="blc-wl-welcome">' +
			'<p class="blc-wl-eyebrow">BLC WELLNESS</p>' +
			'<h3>Let’s create your weight loss goal</h3>' +
			'<p class="blc-wl-lead">Answer a few questions and we’ll create a personalized plan based on your current weight, goal, activity level, and preferred pace.</p>' +
			'<div class="blc-wl-safety"><strong>A note before you begin</strong><p>This estimate is for adults 18 and older. Don’t use it during pregnancy or breastfeeding, or when a medical condition or treatment requires individualized nutrition advice. This tool is not medical advice.</p>' +
			'<label class="blc-wl-check"><input type="checkbox" name="eligibility"> I confirm none of these conditions apply to me.</label></div>' +
			(error ? '<p class="blc-wl-error" role="alert">' + escapeHtml(error) + '</p>' : '') +
			'<button class="blc-wl-button blc-wl-button--primary" type="button" data-start>Get Started</button></div>';

		root.querySelector('[data-start]').addEventListener('click', function () {
			if (!root.querySelector('[name="eligibility"]').checked) {
				renderWelcome('Please confirm the eligibility statement before continuing.');
				return;
			}
			state.profile.eligibility_confirmed = true;
			state.step = 0;
			renderQuestion();
		});
	}

	function optionCards(options, selected) {
		return '<div class="blc-wl-options">' + options.map(function (option) {
			return '<button type="button" class="blc-wl-option' + (selected === option.value ? ' is-selected' : '') + '" data-choice="' + escapeHtml(option.value) + '" aria-pressed="' + (selected === option.value ? 'true' : 'false') + '">' +
				'<span class="blc-wl-option__title">' + escapeHtml(option.title) + '</span>' +
				(option.detail ? '<span class="blc-wl-option__detail">' + escapeHtml(option.detail) + '</span>' : '') +
				'</button>';
		}).join('') + '</div>';
	}

	function renderQuestion(error) {
		var step = steps[state.step];
		var body = '';
		if ('units' === step.key) {
			body = optionCards([
				{ value: 'metric', title: 'Metric', detail: 'kg, cm' },
				{ value: 'imperial', title: 'Imperial', detail: 'lb, ft/in' }
			], state.profile.units);
		} else if ('age' === step.key) {
			body = '<label class="blc-wl-field">Age in years<input name="age" type="number" min="18" max="120" step="1" value="' + escapeHtml(state.profile.age || '') + '" inputmode="numeric" required></label>';
		} else if ('sex' === step.key) {
			body = optionCards([
				{ value: 'female', title: 'Female' },
				{ value: 'male', title: 'Male' }
			], state.profile.sex);
		} else if ('height' === step.key) {
			body = heightFields();
		} else if ('current_weight_kg' === step.key || 'goal_weight_kg' === step.key) {
			var label = 'current_weight_kg' === step.key ? 'Current weight' : 'Goal weight';
			var value = 'current_weight_kg' === step.key ? state.profile.current_weight_kg : state.profile.goal_weight_kg;
			body = '<label class="blc-wl-field">' + label + ' (' + (state.profile.units === 'imperial' ? 'lb' : 'kg') + ')<input name="weight" type="number" min="25" max="1100" step="0.1" value="' + escapeHtml(displayedWeight(value)) + '" inputmode="decimal" required></label>';
		} else if ('activity' === step.key) {
			body = optionCards(activityOptions, state.profile.activity);
		} else if ('pace' === step.key) {
			var paceDisplay = state.profile.units === 'imperial'
				? paceOptions.map(function (item, i) { return { value: item.value, title: item.title, detail: ['Approximately 0.5 lb/week.', 'Approximately 1 lb/week.', 'Approximately 1.7 lb/week.', 'Approximately 2.2 lb/week.'][i] + (i === 3 ? ' This may be reduced for safety.' : '') }; })
				: paceOptions;
			body = optionCards(paceDisplay, state.profile.pace);
		} else if ('target_date' === step.key) {
			body = '<div class="blc-wl-options blc-wl-options--two">' +
				'<button type="button" class="blc-wl-option' + (state.dateMode !== 'date' ? ' is-selected' : '') + '" data-date-mode="none" aria-pressed="' + (state.dateMode !== 'date' ? 'true' : 'false') + '"><span class="blc-wl-option__title">No specific date</span></button>' +
				'<button type="button" class="blc-wl-option' + (state.dateMode === 'date' ? ' is-selected' : '') + '" data-date-mode="date" aria-pressed="' + (state.dateMode === 'date' ? 'true' : 'false') + '"><span class="blc-wl-option__title">Choose a target date</span></button></div>' +
			(state.dateMode === 'date' ? '<label class="blc-wl-field">Target date<input name="target_date" type="date" min="' + escapeHtml(minimumTargetDate()) + '" value="' + escapeHtml(state.profile.target_date || '') + '"></label>' : '');
		}

		root.innerHTML = '<div class="blc-wl-wizard">' + questionProgress() +
			'<div class="blc-wl-question"><p class="blc-wl-eyebrow">YOUR PLAN</p><h3 tabindex="-1">' + escapeHtml(step.title) + '</h3>' + body +
			(error ? '<p class="blc-wl-error" role="alert">' + escapeHtml(error) + '</p>' : '') +
			'<div class="blc-wl-actions">' +
			'<button type="button" class="blc-wl-button blc-wl-button--quiet" data-back>' + (state.step === 0 ? 'Start over' : 'Back') + '</button>' +
			'<button type="button" class="blc-wl-button blc-wl-button--primary" data-next>' + (state.step === steps.length - 1 ? 'Calculate my plan' : 'Next') + '</button>' +
			'</div></div></div>';
		root.querySelector('.blc-wl-question h3').focus();

		root.querySelectorAll('[data-choice]').forEach(function (button) {
			button.addEventListener('click', function () {
				var choice = button.getAttribute('data-choice');
				if ('units' === step.key) {
					state.profile.units = choice;
				} else if ('sex' === step.key) {
					state.profile.sex = choice;
				} else if ('activity' === step.key) {
					state.profile.activity = choice;
				} else if ('pace' === step.key) {
					state.profile.pace = choice;
				}
				root.querySelectorAll('[data-choice]').forEach(function (item) {
					var selected = item === button;
					item.classList.toggle('is-selected', selected);
					item.setAttribute('aria-pressed', selected ? 'true' : 'false');
				});
			});
		});

		root.querySelectorAll('[data-date-mode]').forEach(function (button) {
			button.addEventListener('click', function () {
				state.dateMode = button.getAttribute('data-date-mode');
				if (state.dateMode !== 'date') {
					state.profile.target_date = '';
				}
				renderQuestion();
				if (state.dateMode === 'date') {
					root.querySelector('[name="target_date"]').focus();
				} else {
					root.querySelector('[data-date-mode="none"]').focus();
				}
			});
		});

		root.querySelector('[data-back]').addEventListener('click', function () {
			if (state.step === 0) {
				renderWelcome();
				return;
			}
			state.step -= 1;
			renderQuestion();
		});
		root.querySelector('[data-next]').addEventListener('click', function () {
			var errorMessage = collectStep();
			if (errorMessage) {
				renderQuestion(errorMessage);
				return;
			}
			if (state.step < steps.length - 1) {
				state.step += 1;
				renderQuestion();
			} else {
				submitGoal();
			}
		});
	}

	function collectStep() {
		var profile = state.profile;
		if ('age' === steps[state.step].key) {
			profile.age = parseInt(root.querySelector('[name="age"]').value, 10);
			if (!profile.age || profile.age < 18 || profile.age > 120) {
				return profile.age && profile.age < 18 ? 'This calculator is for adults 18 and older. Please seek guidance from a qualified healthcare professional.' : 'Enter an age from 18 to 120.';
			}
		} else if ('sex' === steps[state.step].key && !profile.sex) {
			return 'Choose an option to continue.';
		} else if ('height' === steps[state.step].key) {
			if (profile.units === 'imperial') {
				var feet = Number(root.querySelector('[name="feet"]').value);
				var inches = Number(root.querySelector('[name="inches"]').value);
				if (!feet || feet < 3 || feet > 8 || !Number.isFinite(inches) || inches < 0 || inches > 11) {
					return 'Enter a height between 3 feet and 8 feet 11 inches.';
				}
				profile.height_cm = ((feet * 12) + inches) * 2.54;
			} else {
				profile.height_cm = Number(root.querySelector('[name="height_cm"]').value);
				if (profile.height_cm < 100 || profile.height_cm > 250) {
					return 'Enter a height between 100 and 250 cm.';
				}
			}
		} else if ('current_weight_kg' === steps[state.step].key || 'goal_weight_kg' === steps[state.step].key) {
			var amount = Number(root.querySelector('[name="weight"]').value);
			if (!amount || amount < 25 || amount > 1100) {
				return 'Enter a valid weight.';
			}
			var kg = profile.units === 'imperial' ? amount / 2.20462262 : amount;
			if ('current_weight_kg' === steps[state.step].key) {
				profile.current_weight_kg = kg;
			} else {
				profile.goal_weight_kg = kg;
				if (!profile.current_weight_kg || kg >= profile.current_weight_kg) {
					return 'Your goal weight must be lower than your current weight.';
				}
				var goalBmi = kg / Math.pow(Number(profile.height_cm) / 100, 2);
				if (goalBmi < 18.5) {
					return 'That goal is below the tool’s general adult healthy-weight screening range. Choose a higher goal or consult a qualified healthcare professional.';
				}
			}
		} else if ('activity' === steps[state.step].key && !profile.activity) {
			return 'Choose the activity level that best describes a typical week.';
		} else if ('pace' === steps[state.step].key && !profile.pace) {
			return 'Choose a pace to continue.';
		} else if ('target_date' === steps[state.step].key && state.dateMode === 'date') {
			var dateInput = root.querySelector('[name="target_date"]');
			if (!dateInput || !dateInput.value || dateInput.value <= (config.today || '')) {
				return 'Choose a target date in the future, or select no specific date.';
			}
			profile.target_date = dateInput.value;
		} else if ('target_date' === steps[state.step].key) {
			profile.target_date = '';
		}
		return '';
	}

	function displayDate(value) {
		if (!value) {
			return 'Unable to estimate';
		}
		return new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' }).format(new Date(value + 'T12:00:00Z'));
	}

	function chartMarkup(profile, results) {
		if (!results.weeks_to_goal || !results.weekly_loss_kg) {
			return '<p class="blc-wl-muted">A projection is unavailable for this estimate.</p>';
		}
		var totalWeeks = results.weeks_to_goal;
		var interval = Math.max(1, Math.ceil(totalWeeks / 5));
		var weeks = [0];
		for (var w = interval; w < totalWeeks; w += interval) {
			weeks.push(w);
		}
		if (weeks[weeks.length - 1] !== totalWeeks) {
			weeks.push(totalWeeks);
		}
		var maxWeight = profile.current_weight_kg;
		var minWeight = profile.goal_weight_kg;
		var range = Math.max(0.1, maxWeight - minWeight);
		var points = weeks.map(function (week, index) {
			var x = weeks.length === 1 ? 0 : (index / (weeks.length - 1)) * 560 + 20;
			var current = Math.max(minWeight, maxWeight - (results.weekly_loss_kg * week));
			var y = 18 + ((maxWeight - current) / range) * 124;
			return { week: week, weight: current, x: x, y: y };
		});
		var polyline = points.map(function (point) { return point.x + ',' + point.y; }).join(' ');
		var dots = points.map(function (point) { return '<circle cx="' + point.x + '" cy="' + point.y + '" r="5"></circle>'; }).join('');
		var rows = points.map(function (point) {
			return '<tr><th scope="row">Week ' + point.week + '</th><td>' + escapeHtml(weight(point.weight)) + '</td></tr>';
		}).join('');
		return '<svg class="blc-wl-chart" viewBox="0 0 600 170" role="img" aria-label="Estimated weight projection by week"><line x1="20" y1="148" x2="580" y2="148"></line><polyline points="' + polyline + '"></polyline>' + dots + '</svg>' +
			'<p class="blc-wl-chart-caption">Estimated trend only. Actual weight change varies over time.</p>' +
			'<div class="blc-wl-table-scroll"><table class="blc-wl-table"><thead><tr><th>Week</th><th>Estimated weight</th></tr></thead><tbody>' + rows + '</tbody></table></div>';
	}

	function resultMarkup() {
		var profile = state.profile;
		var results = state.results;
		var warnings = results.warnings || [];
		var estimateCards = [
			['BMR estimate', number(results.bmr_calories) + ' kcal/day'],
			['Maintenance estimate', number(results.maintenance_calories) + ' kcal/day'],
			['Daily calorie target', results.calorie_target ? number(results.calorie_target) + ' kcal/day' : 'Not available'],
			['Estimated daily deficit', results.daily_deficit == null ? 'Not available' : number(results.daily_deficit) + ' kcal'],
			['Estimated weekly loss', results.calorie_target ? weight(results.weekly_loss_kg) + '/week' : 'Not available'],
			['Estimated time to goal', results.weeks_to_goal ? number(results.weeks_to_goal) + ' weeks' : 'Unable to estimate'],
			['Estimated goal date', displayDate(results.estimated_goal_date)]
		];
		var progress = calculateProgress();
		var cardsHtml = estimateCards.map(function (card) {
			return '<div class="blc-wl-stat"><span>' + escapeHtml(card[0]) + '</span><strong>' + escapeHtml(card[1]) + '</strong></div>';
		}).join('');
		var warningHtml = warnings.length ? '<div class="blc-wl-warning" role="status"><strong>Plan adjustment</strong><ul>' + warnings.map(function (warning) { return '<li>' + escapeHtml(warning) + '</li>'; }).join('') + '</ul></div>' : '';

		return '<div class="blc-wl-results">' +
			'<div class="blc-wl-results__top"><div><p class="blc-wl-eyebrow">YOUR ESTIMATED PLAN</p><h3>Your Weight Loss Goal</h3></div><button type="button" class="blc-wl-button blc-wl-button--quiet" data-new-goal>Start a new plan</button></div>' +
			warningHtml +
			'<div class="blc-wl-summary">' +
			'<div><span>Current weight</span><strong>' + escapeHtml(weight(profile.current_weight_kg)) + '</strong></div>' +
			'<div><span>Goal weight</span><strong>' + escapeHtml(weight(profile.goal_weight_kg)) + '</strong></div>' +
			'<div><span>Weight to lose</span><strong>' + escapeHtml(weight(results.weight_to_lose_kg)) + '</strong><small>' + escapeHtml(number(results.loss_percentage, 1)) + '% of current weight</small></div>' +
			'</div>' +
			'<div class="blc-wl-progress-weight"><div class="blc-wl-progress-weight__labels"><strong>' + escapeHtml(weight(profile.current_weight_kg)) + '</strong><span>' + escapeHtml(weight(profile.goal_weight_kg)) + '</span></div><div class="blc-wl-progress-weight__bar"><span></span></div><p>' + escapeHtml(weight(results.weight_to_lose_kg)) + ' to lose</p></div>' +
			'<div class="blc-wl-stats">' + cardsHtml + '</div>' +
			'<section class="blc-wl-panel"><h4>Estimated progress by week</h4>' + chartMarkup(profile, results) + '</section>' +
			'<p class="blc-wl-personal-summary">Based on your information, your estimated maintenance requirement is <strong>' + number(results.maintenance_calories) + ' calories per day</strong>. ' +
			(results.calorie_target ? 'To work toward your goal at your adjusted pace, your estimated daily calorie target is <strong>' + number(results.calorie_target) + ' calories</strong>.' : 'A safe daily calorie target could not be estimated from these answers.') +
			' Your goal is estimated to take approximately <strong>' + (results.weeks_to_goal ? number(results.weeks_to_goal) + ' weeks' : 'an undetermined time') + '</strong>.</p>' +
			'<p class="blc-wl-disclaimer">Resting energy is estimated with Mifflin–St Jeor; maintenance applies an activity multiplier. The projected calorie-to-weight relationship is approximate. Estimates are not medical advice, and actual energy needs and weight change vary. Consult a qualified healthcare professional before making significant changes to your eating or activity.</p>' +
			nutritionMarkup() +
			'<section class="blc-wl-panel blc-wl-tracker"><div class="blc-wl-results__top"><div><p class="blc-wl-eyebrow">YOUR DASHBOARD</p><h4>Progress tracking</h4></div></div>' +
			'<div class="blc-wl-stats blc-wl-stats--tracker">' +
			'<div class="blc-wl-stat"><span>Starting weight</span><strong>' + escapeHtml(weight(profile.current_weight_kg)) + '</strong></div>' +
			'<div class="blc-wl-stat"><span>Current weight</span><strong>' + escapeHtml(weight(progress.current)) + '</strong></div>' +
			'<div class="blc-wl-stat"><span>Goal weight</span><strong>' + escapeHtml(weight(profile.goal_weight_kg)) + '</strong></div>' +
			'<div class="blc-wl-stat"><span>Progress</span><strong>' + number(progress.percent) + '%</strong></div>' +
			'</div><div class="blc-wl-progress-weight__bar blc-wl-progress-weight__bar--actual"><span style="width:' + progress.percent + '%"></span></div>' +
			'<p class="blc-wl-muted">Estimated time remaining: ' + escapeHtml(progress.remainingWeeks == null ? 'unavailable' : number(progress.remainingWeeks) + ' weeks') + '</p>' +
			progressChart() + progressForm() +
			'</section></div>';
	}

	function calculateProgress() {
		var profile = state.profile;
		var start = Number(profile.current_weight_kg);
		var goal = Number(profile.goal_weight_kg);
		var entries = state.progress || [];
		var current = entries.length ? Number(entries[entries.length - 1].weight_kg) : start;
		var percent = start > goal ? Math.max(0, Math.min(100, ((start - current) / (start - goal)) * 100)) : 0;
		var weekly = Number(state.results.weekly_loss_kg);
		var remainingWeeks = weekly > 0 && current > goal ? Math.ceil((current - goal) / weekly) : (weekly > 0 ? 0 : null);
		return { current: current, percent: Math.round(percent), remainingWeeks: remainingWeeks };
	}

	function progressChart() {
		var entries = state.progress || [];
		if (!entries.length) {
			return '<p class="blc-wl-muted">Your recorded weights will appear here after your first check-in.</p>';
		}
		var values = entries.map(function (entry) { return Number(entry.weight_kg); });
		var max = Math.max.apply(null, values);
		var min = Math.min.apply(null, values);
		var range = Math.max(0.1, max - min);
		var points = values.map(function (value, index) {
			var x = values.length === 1 ? 300 : 20 + (index / (values.length - 1)) * 560;
			var y = 18 + ((max - value) / range) * 124;
			return { x: x, y: y };
		});
		var line = points.map(function (point) { return point.x + ',' + point.y; }).join(' ');
		return '<h5>Recorded check-ins</h5><svg class="blc-wl-chart" viewBox="0 0 600 170" role="img" aria-label="Recorded weight progress"><line x1="20" y1="148" x2="580" y2="148"></line><polyline points="' + line + '"></polyline>' + points.map(function (point) { return '<circle cx="' + point.x + '" cy="' + point.y + '" r="5"></circle>'; }).join('') + '</svg>';
	}

	function nutritionMarkup() {
		var nutrition = state.nutrition || {};
		var created = Boolean(nutrition.plan_created && state.results.calorie_target);
		var values = created ? nutrition : null;
		var plan = values ? '<div class="blc-wl-nutrition-plan"><div class="blc-wl-stats">' +
			'<div class="blc-wl-stat"><span>Daily calories</span><strong>' + number(state.results.calorie_target) + ' kcal</strong></div>' +
			'<div class="blc-wl-stat"><span>Protein target</span><strong>' + number(values.protein_g) + ' g</strong></div>' +
			'<div class="blc-wl-stat"><span>Carbohydrate target</span><strong>' + number(values.carbs_g) + ' g</strong></div>' +
			'<div class="blc-wl-stat"><span>Fat target</span><strong>' + number(values.fat_g) + ' g</strong></div></div>' +
			'<h5>Meal ideas</h5><ul class="blc-wl-meals">' + values.meals.map(function (meal) { return '<li>' + escapeHtml(meal) + '</li>'; }).join('') + '</ul>' +
			'<p><strong>Meal timing:</strong> ' + escapeHtml(values.meal_timing) + '</p>' +
			'<p><strong>Water:</strong> ' + escapeHtml(values.water_goal) + '</p>' +
			'<p class="blc-wl-muted">Preferences: ' + escapeHtml(values.dietary_preference || 'No preference selected') + (values.cuisine ? ' · ' + escapeHtml(values.cuisine) : '') + '</p>' +
			((values.allergies || values.foods_avoided) ? '<p class="blc-wl-warning__small"><strong>Allergies / foods to avoid:</strong> ' + escapeHtml([values.allergies, values.foods_avoided].filter(Boolean).join(' · ')) + '. Check ingredients and cross-contact with the food provider.</p>' : '') +
			'</div>' : '';
		return '<section class="blc-wl-panel blc-wl-nutrition"><div class="blc-wl-results__top"><div><p class="blc-wl-eyebrow">FOOD AND NUTRITION</p><h4>Nutrition planning</h4></div></div>' +
			(!state.results.calorie_target ? '<p class="blc-wl-warning__small">A nutrition target is unavailable until a calorie target can be estimated safely.</p>' :
			'<button type="button" class="blc-wl-button blc-wl-button--primary" data-show-nutrition>' + (created ? 'Update My Nutrition Plan' : 'Create My Nutrition Plan') + '</button>') +
			plan + (state.results.calorie_target ? nutritionForm() : '') + '</section>';
	}

	function nutritionForm() {
		var nutrition = state.nutrition || {};
		return '<form class="blc-wl-form blc-wl-nutrition-form" data-nutrition-form' + (nutrition.plan_created ? ' hidden' : ' hidden') + '>' +
			'<label class="blc-wl-field">Dietary preference<select name="dietary_preference"><option value="">No preference</option>' +
			['Omnivore', 'Vegetarian', 'Vegan', 'Pescatarian', 'Halal', 'Kosher'].map(function (v) { return '<option' + (nutrition.dietary_preference === v ? ' selected' : '') + '>' + v + '</option>'; }).join('') +
			'</select></label>' +
			'<label class="blc-wl-field">Allergies<textarea name="allergies" rows="2" maxlength="500" placeholder="List any allergies">' + escapeHtml(nutrition.allergies || '') + '</textarea></label>' +
			'<label class="blc-wl-field">Foods you avoid<textarea name="foods_avoided" rows="2" maxlength="500" placeholder="List foods to leave out">' + escapeHtml(nutrition.foods_avoided || '') + '</textarea></label>' +
			'<div class="blc-wl-fields blc-wl-fields--two"><label class="blc-wl-field">Meals per day<select name="meals_per_day">' + [2, 3, 4, 5, 6].map(function (v) { return '<option value="' + v + '"' + (Number(nutrition.meals_per_day || 3) === v ? ' selected' : '') + '>' + v + '</option>'; }).join('') + '</select></label>' +
			'<label class="blc-wl-field">Cuisine preference<input name="cuisine" type="text" maxlength="120" value="' + escapeHtml(nutrition.cuisine || '') + '" placeholder="e.g. Filipino, Mediterranean"></label></div>' +
			'<p class="blc-wl-warning__small">Meal ideas are general templates. Review ingredients, labels, and cross-contact risks for allergies.</p>' +
			'<button class="blc-wl-button blc-wl-button--primary" type="submit">Create My Nutrition Plan</button></form>';
	}

	function progressForm() {
		var today = config.today || new Date().toISOString().slice(0, 10);
		return '<form class="blc-wl-form blc-wl-progress-form" data-progress-form><h5>Record a check-in</h5>' +
			'<div class="blc-wl-fields blc-wl-fields--four"><label class="blc-wl-field">Date<input name="date" type="date" value="' + escapeHtml(today) + '" required></label>' +
			'<label class="blc-wl-field">Weight (' + (state.profile.units === 'imperial' ? 'lb' : 'kg') + ')<input name="weight" type="number" min="25" max="1100" step="0.1" value="' + escapeHtml(displayedWeight(calculateProgress().current)) + '" required></label>' +
			'<label class="blc-wl-field">Calories<input name="calories" type="number" min="0" max="10000" step="1" placeholder="Optional"></label>' +
			'<label class="blc-wl-field">Exercise (minutes)<input name="exercise_minutes" type="number" min="0" max="1440" step="1" placeholder="Optional"></label></div>' +
			'<p class="blc-wl-error" data-save-error hidden></p><button class="blc-wl-button blc-wl-button--secondary" type="submit">Save check-in</button></form>' +
			((state.progress || []).length ? '<div class="blc-wl-table-scroll"><table class="blc-wl-table"><thead><tr><th>Date</th><th>Weight</th><th>Calories</th><th>Exercise</th></tr></thead><tbody>' + state.progress.slice().reverse().map(function (entry) { return '<tr><td>' + escapeHtml(entry.date) + '</td><td>' + escapeHtml(weight(entry.weight_kg)) + '</td><td>' + escapeHtml(entry.calories == null ? '—' : number(entry.calories)) + '</td><td>' + escapeHtml(entry.exercise_minutes == null ? '—' : number(entry.exercise_minutes) + ' min') + '</td></tr>'; }).join('') + '</tbody></table></div>' : '');
	}

	function nutritionFromForm(form) {
		var data = new FormData(form);
		var count = Number(data.get('meals_per_day')) || 3;
		var cuisine = String(data.get('cuisine') || '').trim();
		var dietary = String(data.get('dietary_preference') || '').trim();
		return {
			plan_created: true,
			dietary_preference: dietary,
			allergies: String(data.get('allergies') || '').trim(),
			foods_avoided: String(data.get('foods_avoided') || '').trim(),
			meals_per_day: count,
			cuisine: cuisine
		};
	}

	async function request(method, payload) {
		var response = await fetch(config.apiUrl, {
			method: method,
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce || '' },
			body: payload ? JSON.stringify(payload) : undefined
		});
		var data = await response.json();
		if (!response.ok) {
			throw new Error(data.message || 'Unable to save your plan. Please try again.');
		}
		return data;
	}

	function savePayload() {
		return { id: state.id, profile: state.profile, nutrition: state.nutrition, progress: state.progress };
	}

	async function persist() {
		var data = await request('POST', savePayload());
		state.id = data.id;
		state.profile = data.profile;
		state.results = data.results;
		state.nutrition = data.nutrition || {};
		state.progress = data.progress || [];
		return data;
	}

	async function submitGoal() {
		state.busy = true;
		root.innerHTML = '<div class="blc-wl-loading" role="status">Calculating and saving your estimate…</div>';
		try {
			var data = await request('POST', savePayload());
			state.id = data.id;
			state.profile = data.profile;
			state.results = data.results;
			state.nutrition = data.nutrition || {};
			state.progress = data.progress || [];
			renderResults();
		} catch (error) {
			state.busy = false;
			state.step = steps.length - 1;
			renderQuestion(error.message);
		}
	}

	function renderResults() {
		root.innerHTML = resultMarkup();
		var newGoal = root.querySelector('[data-new-goal]');
		newGoal.addEventListener('click', function () {
			state = newState();
			renderWelcome();
		});

		var nutritionButton = root.querySelector('[data-show-nutrition]');
		if (nutritionButton) {
			nutritionButton.addEventListener('click', function () {
				root.querySelector('[data-nutrition-form]').hidden = false;
				nutritionButton.hidden = true;
			});
		}

		var nutritionFormEl = root.querySelector('[data-nutrition-form]');
		if (nutritionFormEl) {
			nutritionFormEl.addEventListener('submit', async function (event) {
				event.preventDefault();
				state.nutrition = nutritionFromForm(nutritionFormEl);
				try {
					await persist();
					renderResults();
				} catch (error) {
					showFormError(nutritionFormEl, error.message);
				}
			});
		}

		var progressFormEl = root.querySelector('[data-progress-form]');
		progressFormEl.addEventListener('submit', async function (event) {
			event.preventDefault();
			var formData = new FormData(progressFormEl);
			var visibleWeight = Number(formData.get('weight'));
			var weightKg = state.profile.units === 'imperial' ? visibleWeight / 2.20462262 : visibleWeight;
			if (!weightKg || weightKg < 25 || weightKg > 500) {
				showFormError(progressFormEl, 'Enter a valid progress weight.');
				return;
			}
			state.progress = (state.progress || []).concat([{
				date: String(formData.get('date')),
				weight_kg: Math.round(weightKg * 100) / 100,
				calories: formData.get('calories') ? Number(formData.get('calories')) : null,
				exercise_minutes: formData.get('exercise_minutes') ? Number(formData.get('exercise_minutes')) : null
			}]);
			try {
				await persist();
				renderResults();
			} catch (error) {
				state.progress.pop();
				showFormError(progressFormEl, error.message);
			}
		});
	}

	function showFormError(form, message) {
		var error = form.querySelector('[data-save-error]');
		if (!error) {
			error = document.createElement('p');
			error.className = 'blc-wl-error';
			error.setAttribute('role', 'alert');
			form.prepend(error);
		}
		error.textContent = message;
		error.hidden = false;
	}

	function renderSavedPlanPrompt() {
		root.innerHTML = '<div class="blc-wl-welcome"><p class="blc-wl-eyebrow">BLC WELLNESS</p><h3>Your saved plan is ready</h3><p class="blc-wl-lead">Your last Weight Loss Management plan and check-ins are saved to your account.</p><div class="blc-wl-actions"><button type="button" class="blc-wl-button blc-wl-button--secondary" data-start-new>Start a new plan</button><button type="button" class="blc-wl-button blc-wl-button--primary" data-view-saved>View saved plan</button></div></div>';
		root.querySelector('[data-start-new]').addEventListener('click', function () {
			state = newState();
			renderWelcome();
		});
		root.querySelector('[data-view-saved]').addEventListener('click', renderResults);
	}

	function newState() {
		return {
			id: null,
			step: -1,
			dateMode: 'none',
			profile: { eligibility_confirmed: false, units: 'metric', target_date: '' },
			results: null,
			nutrition: {},
			progress: []
		};
	}

	function initTabs() {
		document.querySelectorAll('.blc-wellness-menu [role="tablist"]').forEach(function (tabList) {
			var tabs = Array.prototype.slice.call(tabList.querySelectorAll('[data-blc-wellness-tab]'));
			function activate(tab, focus) {
				tabs.forEach(function (item) {
					var panel = document.getElementById(item.getAttribute('aria-controls'));
					var selected = item === tab;
					item.setAttribute('aria-selected', selected ? 'true' : 'false');
					item.setAttribute('tabindex', selected ? '0' : '-1');
					if (panel) { panel.hidden = !selected; }
				});
				if (focus) { tab.focus(); }
			}
			tabs.forEach(function (tab, index) {
				tab.addEventListener('click', function () { activate(tab, false); });
				tab.addEventListener('keydown', function (event) {
					var next = index;
					if ('ArrowRight' === event.key) { next = (index + 1) % tabs.length; }
					else if ('ArrowLeft' === event.key) { next = (index - 1 + tabs.length) % tabs.length; }
					else if ('Home' === event.key) { next = 0; }
					else if ('End' === event.key) { next = tabs.length - 1; }
					else { return; }
					event.preventDefault();
					activate(tabs[next], true);
				});
			});
		});
	}

	async function initApp(app) {
		root = app;
		if (!config.loggedIn) {
			root.innerHTML = '<div class="blc-wl-login"><h3>Save your personal plan</h3><p>Sign in to use the calculator and save your answers and progress privately to your WordPress account.</p><a class="blc-wl-button blc-wl-button--primary" href="' + escapeHtml(config.loginUrl || '#') + '">Sign in</a></div>';
			return;
		}
		state = newState();
		try {
			var saved = await request('GET');
			if (saved.saved) {
				state.id = saved.id;
				state.profile = saved.profile || state.profile;
				state.dateMode = state.profile.target_date ? 'date' : 'none';
				state.results = saved.results || null;
				state.nutrition = saved.nutrition || {};
				state.progress = saved.progress || [];
				if (state.results) {
					renderSavedPlanPrompt();
					return;
				}
			}
		} catch (error) {
			root.innerHTML = '<p class="blc-wl-error" role="alert">' + escapeHtml(error.message) + '</p>';
			return;
		}
		renderWelcome();
	}

	function start() {
		initTabs();
		document.querySelectorAll('[data-blc-weightloss-app]').forEach(initApp);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
}());

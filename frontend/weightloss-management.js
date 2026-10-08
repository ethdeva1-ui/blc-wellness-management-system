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
	var dietTypes = [
		{ name: 'Balanced Diet', description: 'A flexible pattern that provides a variety of nutrients without eliminating whole food groups.', includes: 'Vegetables, fruits, whole grains, fish and seafood, poultry, lean meats, eggs, beans and legumes, nuts and seeds, milk and yogurt, fortified dairy alternatives, olive oil and other unsaturated fats.', limits: 'Highly processed foods, excess added sugar, excess sodium, sugar-sweetened beverages, excess saturated fat, and excessive alcohol.', example: 'Oatmeal with berries, nuts, and yogurt; grilled chicken with brown rice and vegetables; apple with peanut butter; baked salmon with roasted vegetables and quinoa.', notes: 'Can be adapted to different lifestyles, cultures, preferences, and nutritional needs. Its flexibility may help with long-term use.' },
		{ name: 'Ketogenic (Keto) Diet', description: 'A very-low-carbohydrate, high-fat pattern intended to shift the body toward using fat and ketones for energy (ketosis).', includes: 'Eggs, fish, meat and poultry, avocado, olive oil, nuts, seeds, cheese, low-carbohydrate vegetables, and leafy greens.', limits: 'Bread, pasta, rice, cereal, potatoes, sugary foods, sweetened beverages, many high-carbohydrate fruits, and most traditional desserts.', example: 'Grilled salmon with avocado, leafy greens, olive oil, and roasted low-carb vegetables.', notes: 'Carbohydrate restriction can make adequate fiber, vitamins, minerals, and overall nutritional balance harder to maintain. Discuss major carbohydrate restriction with a healthcare professional if you take medication or manage a health condition.' },
		{ name: 'Paleo Diet', description: 'Emphasizes foods thought to resemble those available before modern agriculture and highly processed foods became common.', includes: 'Lean meats, fish and seafood, eggs, vegetables, fruits, nuts, seeds, and natural oils.', limits: 'Grains, legumes, most dairy products, refined sugar, and highly processed foods.', example: 'Grilled chicken with roasted sweet potatoes, broccoli, and avocado.', notes: 'Eliminates nutritious foods such as whole grains, legumes, and dairy. Planning may be needed to maintain fiber, calcium, and other nutrients.' },
		{ name: 'Vegetarian Diet', description: 'Primarily plant-based eating that excludes meat and seafood. Some versions include eggs, dairy, or both.', includes: 'Vegetables, fruits, beans, lentils, whole grains, tofu, nuts, seeds, eggs, and dairy products.', limits: 'Beef, pork, poultry, fish, and seafood.', example: 'Lentil and vegetable curry with brown rice and a side salad.', notes: 'Pay attention to protein, iron, vitamin B12, zinc, calcium, vitamin D, and omega-3 fatty acids. A well-planned vegetarian diet can provide balanced nutrition.' },
		{ name: 'Vegan Diet', description: 'Excludes animal-derived foods and focuses entirely on plant-based foods.', includes: 'Vegetables, fruits, beans, lentils, chickpeas, tofu, tempeh, whole grains, nuts, seeds, and plant-based milk alternatives.', limits: 'Meat, poultry, fish, seafood, eggs, dairy, and other animal-derived foods.', example: 'Tofu stir-fry with mixed vegetables, brown rice, and sesame seeds.', notes: 'Plan for vitamin B12, iron, calcium, vitamin D, iodine, zinc, omega-3 fatty acids, and protein. Vitamin B12 deserves particular attention because reliable natural plant sources are limited.' },
		{ name: 'Mediterranean Diet', description: 'Inspired by traditional eating patterns in countries bordering the Mediterranean Sea; emphasizes whole and minimally processed foods without strict rules.', includes: 'Vegetables, fruits, whole grains, beans, lentils, nuts, seeds, olive oil, fish, and seafood; moderate poultry, eggs, and dairy may also fit.', limits: 'Highly processed foods, refined carbohydrates, added sugars, processed meats, and excess red meat.', example: 'Grilled fish with a Greek-style salad, whole grains, vegetables, and olive oil.', notes: 'Extensively studied and associated with cardiovascular and metabolic health benefits. Its flexibility may make it easier to follow long term.' },
		{ name: 'Intermittent Fasting', description: 'Focuses on when you eat by alternating eating and fasting periods.', includes: 'There is no specific food list; meals during eating periods can emphasize vegetables, fruits, whole grains, lean proteins, healthy fats, beans, and legumes.', limits: 'No food is inherently excluded by the schedule, but nutritious choices remain important.', example: 'Common schedules include 16:8 (16-hour fast, 8-hour eating window), 14:10, or 5:2 (usual eating five days and substantially reduced calories on two nonconsecutive days).', notes: 'May not suit people who are pregnant or breastfeeding, have an eating-disorder history, take medicines affected by meal timing, or have certain medical conditions. Discuss with a qualified healthcare professional.' },
		{ name: 'Low-Carb Diet', description: 'Reduces carbohydrate intake, usually less dramatically than a ketogenic diet; there is no single universal definition.', includes: 'Meat, fish, eggs, vegetables, nuts, seeds, avocado, healthy fats, selected fruits, and some dairy products.', limits: 'Sugary drinks, candy, desserts, white bread, refined pasta, refined grains, and other high-carbohydrate processed foods.', notes: 'Low-carb diets reduce carbohydrate intake; keto usually restricts it much more to try to maintain ketosis. The terms are not interchangeable.' },
		{ name: 'DASH Diet', description: 'Dietary Approaches to Stop Hypertension is an eating plan developed with blood-pressure management in mind.', includes: 'Vegetables, fruits, whole grains, low-fat dairy, fish, poultry, beans, nuts, and seeds.', limits: 'High-sodium foods, processed meats, salty snacks, sugar-sweetened beverages, foods high in saturated fat, and highly processed foods.', example: 'Grilled chicken with brown rice, steamed vegetables, fruit, and low-fat yogurt.', notes: 'A well-researched dietary pattern commonly recommended as part of lifestyle approaches for managing high blood pressure.' },
		{ name: 'Gluten-Free Diet', description: 'Eliminates gluten, a group of proteins found primarily in wheat, barley, and rye.', includes: 'Rice, potatoes, quinoa, corn, vegetables, fruits, beans, meat, fish, eggs, and most unprocessed dairy products.', limits: 'Unless specifically produced as gluten-free: wheat bread, traditional pasta, barley, rye, many cereals, baked goods, and some sauces and processed foods.', notes: 'Medically necessary for celiac disease and may be recommended for other gluten-related conditions. Without a medical need, removing gluten does not automatically make a diet healthier; some packaged gluten-free foods have less fiber or different nutrient profiles than whole-grain alternatives.' },
		{ name: 'Raw Food Diet', description: 'Focuses mainly on foods that have not been cooked or have only been heated to relatively low temperatures; many versions are mostly plant-based.', includes: 'Raw vegetables, fresh fruits, nuts, seeds, sprouted grains, and some fermented foods.', limits: 'Highly processed foods, refined sugars, and many cooked foods.', notes: 'A highly restrictive version can make adequate protein, vitamin B12, calcium, iron, vitamin D, and calories difficult to obtain. Raw or undercooked animal products can carry foodborne illness risks.' },
		{ name: 'Carnivore Diet', description: 'An extremely restrictive pattern consisting primarily or exclusively of animal-derived foods.', includes: 'Beef, pork, poultry, fish, eggs, and sometimes dairy products.', limits: 'Vegetables, fruits, grains, beans, lentils, nuts, and seeds.', notes: 'Excludes many major sources of dietary fiber, vitamin C, folate, plant phytonutrients, and other nutrients. Long-term safety and effectiveness evidence is limited; discuss this approach with a qualified healthcare professional.' },
		{ name: 'Flexitarian Diet', description: 'A flexible form of vegetarian-style eating: plant foods make up most meals, with animal products included occasionally.', includes: 'Vegetables, fruits, beans, lentils, whole grains, tofu, nuts, seeds, eggs, dairy, and occasional meat or seafood.', notes: 'Offers a plant-forward approach without requiring complete elimination of animal products, which may make it easier for some people to maintain.' },
		{ name: 'Whole30 Diet', description: 'A structured 30-day elimination-style program, followed by reintroducing foods.', includes: 'Meat, seafood, eggs, vegetables, fruits, nuts, seeds, and certain fats.', limits: 'Added sugars, alcohol, grains, legumes, most dairy, and certain additives during the program.', notes: 'Intentionally restrictive and not designed as a permanent eating pattern. Eliminating multiple food groups is not medically necessary for most people and may make nutritional needs harder to meet.' },
		{ name: 'Zone Diet', description: 'Balances macronutrients at meals, traditionally aiming for about 40% carbohydrates, 30% protein, and 30% fat.', includes: 'Vegetables, selected fruits, lean meats, fish, eggs, nuts, olive oil, and avocado; emphasizes lower-glycemic carbohydrates, lean protein, and unsaturated fats.', limits: 'Refined carbohydrates, sugary foods, highly processed foods, and large portions of high-glycemic carbohydrates.', example: 'Grilled chicken with vegetables, a moderate serving of whole-grain carbohydrates, and avocado.' }
	];
	var dietRecipes = [
		{ diet: 'Balanced Diet', title: 'Salmon, quinoa, and roasted vegetables', ingredients: 'Salmon fillet, quinoa, broccoli, bell pepper, olive oil, lemon, and black pepper.', steps: 'Cook quinoa according to its package. Roast chopped vegetables with a little olive oil until tender. Bake salmon until it flakes easily; serve with lemon.' },
		{ diet: 'Ketogenic (Keto) Diet', title: 'Salmon avocado salad', ingredients: 'Salmon, leafy greens, avocado, cucumber, olive oil, lemon juice, and herbs.', steps: 'Cook salmon until it flakes easily. Toss greens, sliced cucumber, and avocado with olive oil and lemon. Top with salmon and herbs.' },
		{ diet: 'Paleo Diet', title: 'Chicken and sweet potato tray bake', ingredients: 'Chicken breast, sweet potato, broccoli, avocado oil, garlic, and paprika.', steps: 'Cut sweet potato and broccoli into bite-sized pieces. Arrange with seasoned chicken on a tray, add a little oil, and roast until chicken is fully cooked and vegetables are tender.' },
		{ diet: 'Vegetarian Diet', title: 'Lentil and vegetable curry', ingredients: 'Lentils, tomatoes, spinach, onion, curry spices, vegetable broth, and brown rice.', steps: 'Cook onion with curry spices. Add lentils, tomatoes, and broth; simmer until lentils are tender. Stir in spinach and serve with cooked brown rice.' },
		{ diet: 'Vegan Diet', title: 'Tofu and vegetable stir-fry', ingredients: 'Firm tofu, broccoli, bell pepper, carrots, brown rice, garlic, ginger, and a plant-based, gluten-free tamari if desired.', steps: 'Cook brown rice. Pan-cook cubed tofu until golden, then add vegetables, garlic, and ginger and cook until tender-crisp. Serve over rice.' },
		{ diet: 'Mediterranean Diet', title: 'Herbed fish with chickpea salad', ingredients: 'Fish fillet, chickpeas, tomato, cucumber, parsley, olive oil, lemon, and whole-grain bread or cooked farro.', steps: 'Bake fish with lemon and herbs until it flakes easily. Toss chickpeas with chopped tomato, cucumber, parsley, olive oil, and lemon. Serve with a whole grain.' },
		{ diet: 'Intermittent Fasting', title: 'Colorful grain bowl', ingredients: 'Brown rice or quinoa, grilled chicken or tofu, roasted vegetables, leafy greens, and tahini-lemon dressing.', steps: 'Cook the grain and roast vegetables. Add chicken or tofu and greens to a bowl. Drizzle with tahini-lemon dressing. Enjoy during your usual eating window.' },
		{ diet: 'Low-Carb Diet', title: 'Turkey lettuce cups', ingredients: 'Lean ground turkey, lettuce leaves, mushrooms, bell pepper, garlic, and olive oil.', steps: 'Cook turkey in a pan with a little olive oil. Add chopped mushrooms, bell pepper, and garlic; cook through. Spoon into lettuce leaves.' },
		{ diet: 'DASH Diet', title: 'Chicken and brown rice plate', ingredients: 'Skinless chicken breast, brown rice, steamed green beans, lemon, garlic, and salt-free herbs.', steps: 'Cook brown rice. Season chicken with lemon, garlic, and herbs, then bake or grill until fully cooked. Steam green beans and serve; limit added salt.' },
		{ diet: 'Gluten-Free Diet', title: 'Quinoa and black bean bowl', ingredients: 'Quinoa, black beans, corn, tomato, avocado, lime, and cilantro; check packaged ingredients and cross-contact labels.', steps: 'Rinse and cook quinoa. Warm the beans and corn. Serve with chopped tomato and avocado, lime juice, and cilantro.' },
		{ diet: 'Raw Food Diet', title: 'Fresh zucchini and tomato salad', ingredients: 'Zucchini, cherry tomatoes, spinach, basil, lemon juice, olive oil, and pumpkin seeds.', steps: 'Use a vegetable peeler to make zucchini ribbons. Toss with halved tomatoes, spinach, basil, lemon, and olive oil. Top with pumpkin seeds.' },
		{ diet: 'Carnivore Diet', title: 'Simple baked salmon and eggs', ingredients: 'Salmon fillet, eggs, and optional plain dairy such as butter if included in the chosen version.', steps: 'Bake salmon until it flakes easily. Cook eggs to your preference and serve alongside. This sample reflects the diet’s restrictive food pattern, whose long-term evidence is limited.' },
		{ diet: 'Flexitarian Diet', title: 'Bean and vegetable tacos', ingredients: 'Black beans, corn tortillas, cabbage, tomato, avocado, lime, and optional plain yogurt.', steps: 'Warm beans and tortillas. Fill tortillas with beans, shredded cabbage, tomato, and avocado. Finish with lime and optional yogurt.' },
		{ diet: 'Whole30 Diet', title: 'Chicken and vegetable skillet', ingredients: 'Chicken breast, zucchini, bell pepper, mushrooms, olive oil, garlic, and compliant herbs; check labels for program compliance.', steps: 'Cut chicken and vegetables into bite-sized pieces. Cook chicken in olive oil until fully cooked. Add vegetables, garlic, and herbs; sauté until tender.' },
		{ diet: 'Zone Diet', title: 'Chicken, vegetables, and whole grain', ingredients: 'Grilled chicken, broccoli, leafy greens, a moderate portion of quinoa, and avocado.', steps: 'Cook quinoa. Grill chicken until fully cooked and steam broccoli. Serve with greens and avocado, using portions that fit the meal’s intended macronutrient balance.' }
	];
	// Approximate calories per serving; actual values vary with ingredients and portions.
	var dietRecipeSamples = {
		'Balanced Diet': [['Salmon, quinoa, and roasted vegetables', 480], ['Chicken brown rice bowl', 520], ['Lentil vegetable soup', 360], ['Greek yogurt berry oats', 390], ['Turkey avocado wrap', 450], ['Chickpea spinach salad', 410], ['Baked cod with potatoes', 430], ['Tofu vegetable stir-fry', 460], ['Egg and vegetable breakfast bowl', 350], ['Bean chili with brown rice', 490], ['Tuna white bean salad', 420], ['Chicken vegetable pasta', 510], ['Peanut butter banana toast', 330], ['Roasted vegetable quinoa bowl', 440], ['Yogurt, fruit, and nut parfait', 370]],
		'Ketogenic (Keto) Diet': [['Salmon avocado salad', 520], ['Egg and spinach scramble', 390], ['Chicken pesto zucchini noodles', 560], ['Beef lettuce cups', 480], ['Cauliflower mash with roast chicken', 540], ['Tuna avocado boats', 430], ['Pork chops with green beans', 590], ['Shrimp cauliflower rice', 420], ['Turkey cheddar-stuffed peppers', 500], ['Cobb salad with ranch', 610], ['Bunless burger with salad', 570], ['Baked cod with buttery greens', 460], ['Sausage and cabbage skillet', 550], ['Chicken avocado soup', 490], ['Mushroom and cheese omelet', 410]],
		'Paleo Diet': [['Chicken and sweet potato tray bake', 480], ['Beef and vegetable skillet', 520], ['Turkey cabbage cups with apple salsa', 390], ['Salmon with roasted broccoli', 460], ['Egg and sweet potato hash', 420], ['Pork tenderloin with apples', 500], ['Shrimp avocado salad', 430], ['Chicken squash broth bowl', 370], ['Stuffed bell peppers with beef', 490], ['Roasted chicken with squash', 530], ['Tuna cucumber boats', 350], ['Turkey meatballs with zucchini', 450], ['Steak with asparagus', 560], ['Coconut chia fruit bowl', 380], ['Baked cod with root vegetables', 440]],
		'Vegetarian Diet': [['Lentil and vegetable curry', 460], ['Spinach and feta omelet', 370], ['Black bean sweet potato bowl', 490], ['Chickpea tomato stew', 420], ['Mushroom barley soup', 360], ['Tofu sesame noodles', 510], ['Greek salad with chickpeas', 430], ['Vegetable frittata', 390], ['Peanut tofu lettuce wraps', 450], ['Bean and cheese quesadilla', 480], ['Eggplant lentil bake', 440], ['Cottage cheese fruit bowl', 350], ['Vegetable pesto pasta', 520], ['Stuffed peppers with quinoa', 410], ['Yogurt berry oat parfait', 370]],
		'Vegan Diet': [['Tofu and vegetable stir-fry', 460], ['Chickpea coconut curry', 520], ['Red lentil tomato soup', 350], ['Black bean avocado tacos', 430], ['Tempeh grain bowl', 510], ['Peanut edamame noodles', 490], ['Hummus roasted vegetable wrap', 440], ['Tofu scramble with potatoes', 400], ['Quinoa edamame salad', 420], ['Vegan bean chili', 460], ['Lentil shepherd’s pie', 530], ['Crispy tofu lettuce cups', 380], ['Overnight oats with chia', 390], ['Stuffed sweet potato with beans', 470], ['Mushroom barley bowl', 410]],
		'Mediterranean Diet': [['Herbed fish with chickpea salad', 470], ['Greek chicken grain bowl', 520], ['Tuscan white bean and tomato soup', 360], ['Hummus and roasted vegetable plate', 440], ['Salmon with farro and greens', 540], ['Chickpea cucumber salad', 390], ['Turkey meatballs with whole-grain couscous', 500], ['White bean and kale stew', 420], ['Eggplant tomato bake', 410], ['Tuna olive whole-grain toast', 430], ['Shrimp with lemon orzo', 490], ['Spinach feta omelet', 370], ['Roasted vegetable farro bowl', 450], ['Chicken souvlaki salad', 480], ['Yogurt fruit walnut bowl', 350]],
		'Intermittent Fasting': [['Colorful grain bowl', 510], ['Chicken lentil soup', 380], ['Salmon with roasted vegetables', 520], ['Egg and avocado toast', 420], ['Lentil quinoa salad', 460], ['Turkey bean chili', 490], ['Tofu vegetable stir-fry', 450], ['Greek yogurt fruit bowl', 340], ['Black bean sweet potato bowl', 480], ['Tuna chickpea salad', 430], ['Chicken whole-grain wrap', 470], ['Vegetable omelet with potatoes', 400], ['Shrimp brown rice bowl', 500], ['Cottage cheese berry bowl', 330], ['Roast chicken and vegetables', 540]],
		'Low-Carb Diet': [['Turkey lettuce cups', 390], ['Chicken broccoli skillet', 450], ['Egg salad avocado bowl', 420], ['Salmon with asparagus', 490], ['Beef stuffed peppers', 470], ['Greek chicken salad', 430], ['Tofu cabbage stir-fry', 380], ['Chicken meatballs with zucchini', 460], ['Shrimp spinach sauté', 360], ['Pork tenderloin with green beans', 510], ['Cheeseburger salad bowl', 520], ['Mushroom spinach omelet', 340], ['Tuna cucumber boats', 350], ['Chicken cauliflower rice bowl', 410], ['Roasted eggplant with feta', 390]],
		'DASH Diet': [['Chicken and brown rice plate', 500], ['Bean and vegetable soup', 360], ['Baked salmon with sweet potato', 520], ['Oatmeal with berries and walnuts', 390], ['Turkey quinoa stuffed peppers', 460], ['Lentil spinach stew', 410], ['Grilled chicken garden salad', 430], ['Low-fat yogurt fruit bowl', 320], ['Black bean corn bowl', 440], ['Vegetable omelet with whole-grain toast', 400], ['Baked cod with brown rice', 450], ['Chickpea cucumber whole-grain pita', 420], ['Tofu broccoli bowl', 460], ['Roasted vegetables with farro', 430], ['Apple with peanut butter and yogurt', 350]],
		'Gluten-Free Diet': [['Quinoa and black bean bowl', 470], ['Chicken rice vegetable soup', 390], ['Salmon with potatoes and greens', 520], ['Corn tortilla bean tacos', 430], ['Egg and spinach breakfast bowl', 360], ['Lentil curry with rice', 490], ['Shrimp quinoa salad', 440], ['Baked chicken with sweet potato', 500], ['Tofu rice stir-fry', 460], ['Greek yogurt fruit and nuts', 350], ['Turkey stuffed peppers with rice', 480], ['Chickpea tomato stew', 420], ['Tuna potato salad', 410], ['Beef and vegetable rice bowl', 530], ['Polenta with mushrooms and beans', 450]],
		'Raw Food Diet': [['Fresh zucchini and tomato salad', 280], ['Avocado cucumber rolls', 320], ['Apple walnut spinach salad', 350], ['Carrot citrus slaw with seeds', 290], ['Chia berry pudding', 360], ['Tomato basil zucchini ribbons', 310], ['Mango avocado lettuce cups', 330], ['Sprouted lentil salad', 380], ['Cucumber melon gazpacho', 240], ['Cashew herb vegetable wraps', 400], ['Pear almond kale salad', 340], ['Raw beet apple salad', 270], ['Stuffed tomatoes with sprouted quinoa', 390], ['Banana cacao chia bowl', 370], ['Orange fennel avocado salad', 300]],
		'Carnivore Diet': [['Baked salmon and eggs', 520], ['Beef patty with eggs', 610], ['Roast chicken thighs', 540], ['Pork chop with scrambled eggs', 590], ['Tuna and egg salad bowl', 450], ['Lamb burger patties', 620], ['Shrimp omelet', 430], ['Turkey patties with cheese', 500], ['Sardines with boiled eggs', 470], ['Slow-cooked beef roast', 580], ['Bacon and egg omelet', 550], ['Pork tenderloin medallions', 510], ['Grilled trout with eggs', 490], ['Chicken liver and eggs', 460], ['Beef short ribs', 680]],
		'Flexitarian Diet': [['Bean and vegetable tacos', 430], ['Salmon quinoa bowl', 520], ['Lentil bolognese with whole-grain pasta', 490], ['Chicken and roasted vegetable plate', 500], ['Tofu peanut stir-fry', 470], ['Chickpea Greek salad', 400], ['Turkey and bean chili', 480], ['Mushroom barley bowl', 420], ['Shrimp vegetable rice bowl', 490], ['Egg and spinach whole-grain toast', 380], ['Black bean sweet potato bowl', 460], ['Tuna avocado wrap', 450], ['Tempeh vegetable skewers', 440], ['Roast chicken lentil salad', 510], ['Vegetable frittata with side salad', 410]],
		'Whole30 Diet': [['Chicken and vegetable skillet', 430], ['Salmon with broccoli and squash', 500], ['Turkey stuffed peppers', 410], ['Whole30 turkey and kale breakfast skillet', 390], ['Beef lettuce wraps', 450], ['Shrimp zucchini skillet', 380], ['Roast chicken with carrots', 520], ['Pork chops with cabbage', 490], ['Tuna avocado cucumber boats', 360], ['Zucchini noodles with turkey meatballs', 440], ['Turkey vegetable soup', 350], ['Steak with asparagus', 540], ['Bunless burger with roasted vegetables', 510], ['Baked cod with green beans', 400], ['Chicken salad lettuce cups', 420]],
		'Zone Diet': [['Chicken, vegetables, and whole grain', 460], ['Salmon with greens and quinoa', 500], ['Turkey avocado lettuce bowl', 420], ['Egg white vegetable scramble with oats', 390], ['Tuna chickpea salad', 450], ['Lean beef broccoli bowl', 480], ['Tofu edamame grain bowl', 470], ['Chicken apple walnut salad', 440], ['Shrimp with barley and vegetables', 460], ['Cottage cheese berry bowl with almonds', 360], ['Turkey quinoa stuffed peppers', 450], ['Cod with lentils and greens', 430], ['Pork tenderloin with roasted vegetables', 490], ['Tempeh cabbage stir-fry', 410], ['Greek yogurt oats with fruit', 380]]
	};
	var dietRecipeGuidance = {
		'Balanced Diet': 'Build the plate with a protein, vegetables or fruit, a whole-grain or bean choice, and a modest amount of unsaturated fat.',
		'Ketogenic (Keto) Diet': 'Keep the side dishes low in carbohydrate and use fats such as olive oil or avocado as appropriate. This restrictive approach may need clinician guidance.',
		'Paleo Diet': 'Use vegetables, fruit, meat, seafood, eggs, nuts, or seeds; leave out grains, legumes, and most dairy to match this pattern.',
		'Vegetarian Diet': 'Use plant proteins, eggs, or dairy as desired, and leave out meat and seafood. Consider iron, B12, calcium, vitamin D, zinc, and omega-3 sources.',
		'Vegan Diet': 'Use plant proteins such as beans, lentils, tofu, or tempeh and avoid all animal-derived ingredients. Plan for vitamin B12 and other nutrients.',
		'Mediterranean Diet': 'Favor vegetables, legumes, whole grains, fish, nuts, and olive oil; use herbs, citrus, and spices for flavor.',
		'Intermittent Fasting': 'This is a meal-timing pattern rather than a food list. This recipe can be eaten during the chosen eating window; fasting is not suitable for everyone.',
		'Low-Carb Diet': 'Keep refined grains and sugary ingredients limited, and emphasize protein, non-starchy vegetables, and unsaturated fats.',
		'DASH Diet': 'Use low-sodium ingredients, limit added salt, and favor vegetables, fruits, whole grains, beans, and lean proteins.',
		'Gluten-Free Diet': 'Use naturally gluten-free ingredients or products labeled gluten-free, and check labels and cross-contact precautions, especially for celiac disease.',
		'Raw Food Diet': 'Keep ingredients raw or minimally heated for this pattern, wash produce carefully, and avoid raw or undercooked animal products.',
		'Carnivore Diet': 'This highly restrictive pattern excludes plant foods. Long-term evidence is limited, so discuss it with a qualified healthcare professional.',
		'Flexitarian Diet': 'Keep the meal plant-forward; animal protein can be included occasionally, but it is not required.',
		'Whole30 Diet': 'During the 30-day program, check every packaged ingredient against program rules; grains, legumes, most dairy, added sugars, and alcohol are excluded.',
		'Zone Diet': 'Choose portions to approximate the traditional meal balance of 40% carbohydrate, 30% protein, and 30% fat, emphasizing lower-glycemic carbohydrates.'
	};
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
		var selectedDiet = dietTypes.find(function (diet) { return diet.name === nutrition.diet_type; });
		var dietMarkup = nutrition.diet_type && selectedDiet ? '<div class="blc-wl-nutrition-plan blc-wl-selected-diet"><h5>Recommended Diet Type: ' + escapeHtml(selectedDiet.name) + '</h5>' + dietDetailsMarkup(selectedDiet) + '</div>' : '';
		return '<section class="blc-wl-panel blc-wl-nutrition"><div class="blc-wl-results__top"><div><p class="blc-wl-eyebrow">FOOD AND NUTRITION</p><h4>Nutrition planning</h4></div></div>' +
			'<div class="blc-wl-diet-actions"><button type="button" class="blc-wl-button blc-wl-button--secondary blc-wl-diet-button" data-show-diet>Recommended Diet Type</button></div>' + dietMarkup + dietTypeForm() +
			(!state.results.calorie_target ? '<p class="blc-wl-warning__small">A nutrition target is unavailable until a calorie target can be estimated safely.</p>' :
			'<button type="button" class="blc-wl-button blc-wl-button--primary" data-show-nutrition>' + (created ? 'Update My Nutrition Plan' : 'Create My Nutrition Plan') + '</button>') +
			plan + (state.results.calorie_target ? nutritionForm() : '') + '</section>';
	}

	function dietDetailsMarkup(diet) {
		var recipe = dietRecipes.find(function (item) { return item.diet === diet.name; });
		return '<p>' + escapeHtml(diet.description) + '</p>' +
			(diet.includes ? '<p><strong>Foods commonly included:</strong> ' + escapeHtml(diet.includes) + '</p>' : '') +
			(diet.limits ? '<p><strong>Foods commonly limited or avoided:</strong> ' + escapeHtml(diet.limits) + '</p>' : '') +
			(diet.example ? '<p><strong>Example:</strong> ' + escapeHtml(diet.example) + '</p>' : '') +
			(diet.notes ? '<p><strong>Things to consider:</strong> ' + escapeHtml(diet.notes) + '</p>' : '') +
			(recipe ? '<button type="button" class="blc-wl-button blc-wl-button--secondary blc-wl-recipe-button" data-recipe-samples="' + escapeHtml(diet.name) + '">View recipe samples</button>' : '');
	}

	function recipeSampleDetailsMarkup(title, recipe) {
		var detailedRecipe = recipe && recipe.title === title ? recipe : null;
		var ingredients = detailedRecipe ? detailedRecipe.ingredients : title + (recipe && recipe.diet === 'Carnivore Diet' ? ', with optional salt and dairy only if included in your version.' : recipe && recipe.diet === 'Raw Food Diet' ? ', with fresh herbs or citrus as desired.' : ', with herbs, seasoning, and a little cooking oil where appropriate.');
		var lowerTitle = title.toLowerCase();
		var steps = detailedRecipe ? detailedRecipe.steps : '';
		if (!steps && recipe && recipe.diet === 'Raw Food Diet') {
			steps = 'Wash produce carefully, chop or prepare the ingredients as named, then combine and season to taste.';
		} else if (!steps && recipe && recipe.diet === 'Carnivore Diet') {
			steps = 'Cook the meat or fish thoroughly using a suitable method, and cook eggs fully if included. Serve together.';
		} else if (!steps && /soup|stew|chili|curry/.test(lowerTitle)) {
			steps = 'Prepare the ingredients named in the dish. Simmer them together until vegetables and protein or legumes are cooked through and tender.';
		} else if (!steps && /salad|bowl|wrap|taco|lettuce cup|boats/.test(lowerTitle)) {
			steps = 'Cook any grain, beans, or protein that needs cooking. Chop or prepare the remaining ingredients, then assemble and season.';
		} else if (!steps && /bake|roast|tray/.test(lowerTitle)) {
			steps = 'Prepare the named ingredients and season to taste. Bake or roast until vegetables are tender and any meat or fish is safely cooked through.';
		} else if (!steps) {
			steps = 'Prepare the ingredients named in the dish. Cook grains or legumes until tender and cook any meat, seafood, eggs, or tofu as appropriate; combine and season.';
		}
		return '<p><strong>Ingredients</strong><br>' + escapeHtml(ingredients) + '</p><p><strong>Preparation</strong><br>' + escapeHtml(steps) + '</p><p><strong>For this diet:</strong> ' + escapeHtml(dietRecipeGuidance[recipe.diet] || '') + '</p>';
	}

	function handleDietRecipeClick(event) {
		var trigger = event.target.closest('[data-recipe-samples]');
		if (trigger) {
			var dietForm = trigger.closest('[data-diet-form]');
			var selectedDietName = dietForm ? dietForm.querySelector('[name="diet_type"]').value : ((state.nutrition || {}).diet_type || trigger.getAttribute('data-recipe-samples'));
			var recipe = dietRecipes.find(function (item) { return item.diet === selectedDietName; });
			if (!recipe) { return; }
			var oldDialog = root.querySelector('[data-diet-recipe-dialog]');
			if (oldDialog) { oldDialog.remove(); }
			var dialog = document.createElement('dialog');
			dialog.className = 'blc-wl-recipe-dialog';
			dialog.setAttribute('data-diet-recipe-dialog', '');
			dialog.setAttribute('aria-labelledby', 'blc-wl-recipe-title');
			var samples = dietRecipeSamples[recipe.diet] || [];
			dialog.innerHTML = '<div class="blc-wl-recipe-dialog__content"><button type="button" class="blc-wl-recipe-dialog__close" data-close-recipe aria-label="Close recipe samples">×</button><p class="blc-wl-eyebrow">' + escapeHtml(recipe.diet) + '</p><h3 id="blc-wl-recipe-title">Recipe Samples for ' + escapeHtml(recipe.diet) + '</h3><p class="blc-wl-muted">Showing ' + samples.length + ' recipes for this diet. Select a recipe title to view its ingredients and preparation. Calories are approximate per serving and vary with ingredients and portion sizes.</p><ol class="blc-wl-recipe-list">' + samples.map(function (sample) { return '<li><details><summary><span>' + escapeHtml(sample[0]) + '</span><strong>~' + number(sample[1]) + ' kcal</strong></summary><div class="blc-wl-recipe-detail">' + recipeSampleDetailsMarkup(sample[0], recipe) + '</div></details></li>'; }).join('') + '</ol><p class="blc-wl-muted">Sample ideas only. Adjust ingredients for your nutrition needs, allergies, and food safety.</p></div>';
			root.appendChild(dialog);
			dialog.addEventListener('click', function (dialogEvent) { if (dialogEvent.target === dialog) { dialog.close(); } });
			dialog.addEventListener('close', function () { dialog.remove(); trigger.focus(); });
			dialog.showModal();
			return;
		}
		var closeButton = event.target.closest('[data-close-recipe]');
		if (closeButton) {
			var activeDialog = closeButton.closest('[data-diet-recipe-dialog]');
			if (activeDialog) { activeDialog.close(); }
		}
	}

	function dietTypeForm() {
		var selected = (state.nutrition || {}).diet_type || '';
		var diet = dietTypes.find(function (item) { return item.name === selected; });
		return '<form class="blc-wl-form" data-diet-form hidden><label class="blc-wl-field">Select a diet type<select name="diet_type"><option value="">Choose a diet type</option>' +
			dietTypes.map(function (item) { return '<option value="' + escapeHtml(item.name) + '"' + (item.name === selected ? ' selected' : '') + '>' + escapeHtml(item.name) + '</option>'; }).join('') +
			'</select></label><div data-diet-description>' + (diet ? dietDetailsMarkup(diet) : '<p class="blc-wl-muted">Choose a diet type to see its details.</p>') + '</div><p class="blc-wl-error" data-save-error hidden></p><button class="blc-wl-button blc-wl-button--primary blc-wl-diet-button" type="submit">Save Recommended Diet Type</button></form>';
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
		if (!root._dietRecipeEventsBound) {
			root._dietRecipeEventsBound = true;
			root.addEventListener('click', handleDietRecipeClick);
		}
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
		var dietButton = root.querySelector('[data-show-diet]');
		var dietForm = root.querySelector('[data-diet-form]');
		if (dietButton && dietForm) {
			dietButton.addEventListener('click', function () { dietForm.hidden = !dietForm.hidden; });
			dietForm.querySelector('[name="diet_type"]').addEventListener('change', function (event) {
				var diet = dietTypes.find(function (item) { return item.name === event.target.value; });
				dietForm.querySelector('[data-diet-description]').innerHTML = diet ? dietDetailsMarkup(diet) : '<p class="blc-wl-muted">Choose a diet type to see its details.</p>';
			});
			dietForm.addEventListener('submit', async function (event) {
				event.preventDefault();
				var field = dietForm.querySelector('[name="diet_type"]');
				state.nutrition = Object.assign({}, state.nutrition || {}, { diet_type: field.value });
				try { await persist(); renderResults(); } catch (error) { showFormError(dietForm, error.message); }
			});
		}

		var nutritionFormEl = root.querySelector('[data-nutrition-form]');
		if (nutritionFormEl) {
			nutritionFormEl.addEventListener('submit', async function (event) {
				event.preventDefault();
				state.nutrition = Object.assign({}, state.nutrition || {}, nutritionFromForm(nutritionFormEl));
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
			profile: { eligibility_confirmed: false, units: 'imperial', target_date: '' },
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

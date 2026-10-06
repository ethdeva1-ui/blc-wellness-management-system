# BLC Wellness Management System

## Frontend page

When the plugin is activated, it creates a published **Wellness Programs** page at `/wellness-programs/` and a separate **Weight Loss Management** page at `/weight-loss-management/`. If the plugin was already active when this feature was added, visit the WordPress dashboard once to create the pages. On the Wellness Programs page, the menu works as tabs: Rethink Your Drink is shown first, and Weight Loss Management stays hidden until selected. Switching tabs does not navigate away.

You can also add `[blc_wellness_page]` to any page or post to display the complete layout elsewhere. The Rethink Your Drink section includes the sugar and calorie calculator and a comparison table of the typical per-serving estimates. Its PHP shortcode and drink estimates are in `backend/class-blc-drink-calculator.php`; its browser interaction is in `frontend/drink-calculator.js`.

The calculator estimates sugar and calories for a typical serving of regular cola, sweetened iced tea, orange juice, a sports drink, an energy drink, chocolate milk, or water. Select a drink and serving count to see the totals update. Values are approximate and can vary by recipe, brand, and serving size; use the product label for exact information.

## Shortcodes

Add these shortcodes to a WordPress page or post to display the wellness menu and its sections.

### Wellness menu

Place `[blc_wellness_menu]` where you want the navigation tabs to appear. The matching sections should also be present on the same page.

The Weight Loss Management section is implemented in `backend/class-blc-weightloss-management.php`; its frontend behavior is in `frontend/weightloss-management.js`. You can place it separately with `[blc_weightloss_management]`.

### Rethink Your Drink

Use the shortcode with opening and closing tags. Add the section content between them:

```text
[rethink_your_drink]
Add your Rethink Your Drink content here.
[/rethink_your_drink]
```

To build a custom page, add the menu and Rethink Your Drink section:

```text
[blc_wellness_menu]

[rethink_your_drink]
Your content here.
[/rethink_your_drink]
```

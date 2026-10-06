<?php
/**
 * Frontend sugar and calorie calculator.
 *
 * @package BLC_Wellness_Management_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Provides the drink calculator shortcode and its reference values. */
class BLC_Drink_Calculator {
	/** Register the calculator shortcode. */
	public static function init() {
		add_shortcode( 'blc_drink_calculator', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render the calculator form.
	 *
	 * @return string
	 */
	public static function render() {
		wp_enqueue_script(
			'blc-drink-calculator',
			plugins_url( 'frontend/drink-calculator.js', dirname( __DIR__ ) . '/index.php' ),
			array(),
			'1.0.1',
			true
		);

		$id     = wp_unique_id( 'blc-drink-calculator-' );
		$drinks = self::get_drinks();
		$html   = '<div class="blc-drink-calculator" id="' . esc_attr( $id ) . '">';
		$html  .= '<span id="rethink-your-drink-calculator" class="blc-drink-calculator__anchor" aria-hidden="true"></span>';
		$html  .= '<div class="blc-drink-calculator__intro"><h3>' . esc_html__( 'Sugar and calories in common drinks', 'blc-wellness-management-system' ) . '</h3>';
		$html  .= '<p>' . esc_html__( 'Choose a drink and serving count to estimate the total sugar and calories.', 'blc-wellness-management-system' ) . '</p></div>';
		$html  .= '<div class="blc-drink-calculator__controls">';
		$html  .= '<label for="' . esc_attr( $id . '-drink' ) . '">' . esc_html__( 'Drink', 'blc-wellness-management-system' ) . '</label>';
		$html  .= '<select class="blc-drink-calculator__drink" id="' . esc_attr( $id . '-drink' ) . '">';

		foreach ( $drinks as $key => $drink ) {
			$html .= '<option value="' . esc_attr( $key ) . '" data-sugar="' . esc_attr( $drink['sugar'] ) . '" data-calories="' . esc_attr( $drink['calories'] ) . '" data-serving="' . esc_attr( $drink['serving'] ) . '">' . esc_html( $drink['name'] . ' (' . $drink['serving'] . ')' ) . '</option>';
		}

		$html .= '</select>';
		$html .= '<label for="' . esc_attr( $id . '-servings' ) . '">' . esc_html__( 'Number of servings', 'blc-wellness-management-system' ) . '</label>';
		$html .= '<input class="blc-drink-calculator__servings" id="' . esc_attr( $id . '-servings' ) . '" type="number" min="0.25" max="20" step="0.25" value="1" inputmode="decimal">';
		$html .= '</div>';
		$html .= '<p class="blc-drink-calculator__portion" aria-live="polite"></p>';
		$html .= '<div class="blc-drink-calculator__results" aria-live="polite" aria-atomic="true">';
		$html .= '<div><span>' . esc_html__( 'Estimated sugar', 'blc-wellness-management-system' ) . '</span><strong class="blc-drink-calculator__sugar">0 g</strong></div>';
		$html .= '<div><span>' . esc_html__( 'Estimated calories', 'blc-wellness-management-system' ) . '</span><strong class="blc-drink-calculator__calories">0 kcal</strong></div>';
		$html .= '</div>';
		$html .= '<p class="blc-drink-calculator__note">' . esc_html__( 'These are approximate values for typical servings. Recipes and brands vary; check the product label for the most accurate information.', 'blc-wellness-management-system' ) . '</p>';
		$html .= '</div>';
		$html .= self::render_reference_table( $drinks );

		return $html;
	}

	/**
	 * Render the drink estimates as a comparison table.
	 *
	 * @param array $drinks Drink estimates.
	 * @return string
	 */
	private static function render_reference_table( $drinks ) {
		$html  = '<div class="blc-drink-table-wrap">';
		$html .= '<h3>' . esc_html__( 'Typical sugar and calories per serving', 'blc-wellness-management-system' ) . '</h3>';
		$html .= '<table class="blc-drink-table"><thead><tr>';
		$html .= '<th scope="col">' . esc_html__( 'Drink', 'blc-wellness-management-system' ) . '</th>';
		$html .= '<th scope="col">' . esc_html__( 'Serving size', 'blc-wellness-management-system' ) . '</th>';
		$html .= '<th scope="col">' . esc_html__( 'Sugar', 'blc-wellness-management-system' ) . '</th>';
		$html .= '<th scope="col">' . esc_html__( 'Calories', 'blc-wellness-management-system' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $drinks as $drink ) {
			$html .= '<tr>';
			$html .= '<th scope="row">' . esc_html( $drink['name'] ) . '</th>';
			$html .= '<td>' . esc_html( $drink['serving'] ) . '</td>';
			$html .= '<td>' . esc_html( $drink['sugar'] . ' g' ) . '</td>';
			$html .= '<td>' . esc_html( $drink['calories'] . ' kcal' ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table></div>';

		return $html;
	}

	/**
	 * Generic reference estimates per typical serving.
	 *
	 * @return array<string, array<string, string|int>>
	 */
	private static function get_drinks() {
		return array(
			'cola'          => array( 'name' => __( 'Regular cola', 'blc-wellness-management-system' ), 'serving' => '12 fl oz', 'sugar' => 39, 'calories' => 140 ),
			'sweet-tea'     => array( 'name' => __( 'Sweetened iced tea', 'blc-wellness-management-system' ), 'serving' => '16 fl oz', 'sugar' => 32, 'calories' => 120 ),
			'orange-juice'  => array( 'name' => __( 'Orange juice', 'blc-wellness-management-system' ), 'serving' => '8 fl oz', 'sugar' => 21, 'calories' => 110 ),
			'sports-drink'  => array( 'name' => __( 'Sports drink', 'blc-wellness-management-system' ), 'serving' => '20 fl oz', 'sugar' => 34, 'calories' => 130 ),
			'energy-drink'  => array( 'name' => __( 'Energy drink', 'blc-wellness-management-system' ), 'serving' => '16 fl oz', 'sugar' => 54, 'calories' => 220 ),
			'chocolate-milk' => array( 'name' => __( 'Chocolate milk', 'blc-wellness-management-system' ), 'serving' => '8 fl oz', 'sugar' => 24, 'calories' => 190 ),
			'water'         => array( 'name' => __( 'Water', 'blc-wellness-management-system' ), 'serving' => '12 fl oz', 'sugar' => 0, 'calories' => 0 ),
		);
	}
}

BLC_Drink_Calculator::init();

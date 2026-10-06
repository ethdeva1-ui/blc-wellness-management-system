<?php
/**
 * Weight Loss Management frontend component.
 *
 * @package BLC_Wellness_Management_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Renders the Weight Loss Management section and loads its frontend behavior. */
class BLC_Weightloss_Management {
	/** Register the standalone section shortcode. */
	public static function init() {
		add_shortcode( 'blc_weightloss_management', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Render the section shortcode.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public static function render_shortcode( $atts = array(), $content = '' ) {
		return self::render();
	}

	/**
	 * Render the section.
	 *
	 * @return string
	 */
	public static function render( $hidden = false ) {
		wp_enqueue_script(
			'blc-weightloss-management',
			plugins_url( 'frontend/weightloss-management.js', dirname( __DIR__ ) . '/index.php' ),
			array(),
			'1.0.1',
			true
		);

		$panel_attributes = $hidden ? ' role="tabpanel" aria-labelledby="blc-tab-weight-loss-management" hidden' : '';

		return '<section id="weight-loss-management" class="blc-wellness-section blc-weightloss-management"' . $panel_attributes . '>' .
			'<h2 id="blc-weightloss-management-title">' . esc_html__( 'Weight Loss Management', 'blc-wellness-management-system' ) . '</h2>' .
			'</section>';
	}
}

BLC_Weightloss_Management::init();

<?php
/**
 * Plugin Name: BLC Wellness Management System
 * Description: Wellness management system for Balanced life care.
 * Version: 2.3.0
 * Plugin URI: https://flowbrixai.com/
 * Author: Ethelyn Matias
 * Author URI: https://flowbrixai.com/
 * Text Domain: blc-wellness-management-system
 *
 * @package BLC_Wellness_Management_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLC_WELLNESS_VERSION', '2.3.0' );

require_once __DIR__ . '/backend/class-blc-drink-calculator.php';
require_once __DIR__ . '/backend/class-blc-weightloss-management.php';

add_shortcode( 'blc_wellness_menu', 'blc_wellness_render_menu_shortcode' );
add_shortcode( 'rethink_your_drink', 'blc_wellness_render_rethink_your_drink_shortcode' );
add_shortcode( 'blc_wellness_page', 'blc_wellness_render_page_shortcode' );
add_shortcode( 'blc_wellness_weight_loss_page', 'blc_wellness_render_weight_loss_page_shortcode' );
add_action( 'wp_enqueue_scripts', 'blc_wellness_enqueue_frontend_styles' );
add_action( 'admin_init', 'blc_wellness_admin_setup' );
register_activation_hook( __FILE__, 'blc_wellness_activate_plugin' );

/** Install the goals table and create frontend pages when activated. */
function blc_wellness_activate_plugin() {
	BLC_Weightloss_Management::install();
	blc_wellness_create_frontend_page();
}

/** Run one-time setup for existing installations. */
function blc_wellness_admin_setup() {
	if ( current_user_can( 'manage_options' ) ) {
		BLC_Weightloss_Management::maybe_install();
	}
	blc_wellness_create_frontend_page();
}

/** Load the styles for the public wellness page. */
function blc_wellness_enqueue_frontend_styles() {
	wp_enqueue_style(
		'blc-wellness-frontend',
		plugin_dir_url( __FILE__ ) . 'assets/css/frontend.css',
		array(),
		BLC_WELLNESS_VERSION
	);
}

/** Create the public page that contains the menu and drink calculator. */
function blc_wellness_create_frontend_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	blc_wellness_insert_page_if_missing(
		'wellness-programs',
		__( 'Wellness Programs', 'blc-wellness-management-system' ),
		'[blc_wellness_page]'
	);
	blc_wellness_insert_page_if_missing(
		'weight-loss-management',
		__( 'Weight Loss Management', 'blc-wellness-management-system' ),
		'[blc_wellness_weight_loss_page]'
	);
}

/** Create a published page by slug if it does not already exist. */
function blc_wellness_insert_page_if_missing( $slug, $title, $content ) {
	if ( get_page_by_path( $slug ) ) {
		return;
	}

	wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		)
	);
}

/** Render the front-end navigation menu. */
function blc_wellness_render_menu_shortcode() {
	return '<nav class="blc-wellness-menu" aria-label="' . esc_attr__( 'Wellness menu', 'blc-wellness-management-system' ) . '">' .
		'<ul role="tablist">' .
		'<li role="presentation"><button type="button" id="blc-tab-rethink-your-drink" role="tab" aria-selected="true" aria-controls="rethink-your-drink" tabindex="0" data-blc-wellness-tab>' . esc_html__( 'Rethink Your Drink', 'blc-wellness-management-system' ) . '</button></li>' .
		'<li role="presentation"><button type="button" id="blc-tab-weight-loss-management" role="tab" aria-selected="false" aria-controls="weight-loss-management" tabindex="-1" data-blc-wellness-tab>' . esc_html__( 'Weight Loss Management', 'blc-wellness-management-system' ) . '</button></li>' .
		'</ul>' .
		'</nav>';
}

/** Render the complete wellness programs page. */
function blc_wellness_render_page_shortcode() {
	return '<div class="blc-wellness-page">' .
		blc_wellness_render_menu_shortcode() .
		blc_wellness_render_section_shortcode(
			__( 'Rethink Your Drink', 'blc-wellness-management-system' ),
			'rethink-your-drink',
			'<p>' . esc_html__( 'Learn more about making thoughtful drink choices as part of your wellness journey.', 'blc-wellness-management-system' ) . '</p>' .
			BLC_Drink_Calculator::render(),
			'blc-tab-rethink-your-drink'
		) .
		BLC_Weightloss_Management::render( true ) .
		'</div>';
}

/** Render the standalone Weight Loss Management page heading. */
function blc_wellness_render_weight_loss_page_shortcode() {
	return '<div class="blc-wellness-page">' .
		'<header class="blc-wellness-hero">' .
		'<p class="blc-wellness-eyebrow">' . esc_html__( 'BLC WELLNESS', 'blc-wellness-management-system' ) . '</p>' .
		'</header>' .
		BLC_Weightloss_Management::render() .
		'</div>';
}

/** Render a section heading for a wellness menu item. */
function blc_wellness_render_section_shortcode( $title, $section_id, $content, $tab_id = '' ) {
	$tab_attributes = $tab_id ? ' role="tabpanel" aria-labelledby="' . esc_attr( $tab_id ) . '"' : '';

	return '<section id="' . esc_attr( $section_id ) . '" class="blc-wellness-section"' . $tab_attributes . '>' .
		'<h2>' . esc_html( $title ) . '</h2>' .
		do_shortcode( $content ) .
		'</section>';
}

/** Render the Rethink Your Drink section. */
function blc_wellness_render_rethink_your_drink_shortcode( $atts = array(), $content = '' ) {
	return blc_wellness_render_section_shortcode(
		__( 'Rethink Your Drink', 'blc-wellness-management-system' ),
		'rethink-your-drink',
		$content . BLC_Drink_Calculator::render()
	);
}

<?php
/**
 * Weight Loss Goal Calculator and Nutrition Planning backend.
 *
 * @package BLC_Wellness_Management_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Provides the calculator shortcode, private REST API, calculations, and storage. */
class BLC_Weightloss_Management {
	const REST_NAMESPACE = 'blc-wellness/v1';
	const SCHEMA_VERSION = '1';
	private static $admin_page_hook = array();

	/** Register public hooks. */
	public static function init() {
		add_shortcode( 'blc_weightloss_management', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'init', array( __CLASS__, 'register_event_post_type' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
	}

	/** Register private storage for wellness events. */
	public static function register_event_post_type() {
		register_post_type(
			'blc_wellness_event',
			array(
				'public'       => false,
				'show_ui'      => false,
				'supports'     => array( 'title', 'editor' ),
				'rewrite'      => false,
				'query_var'    => false,
				'capability_type' => 'post',
			)
		);
	}

	/** Add the administrator-only wellness program menu and its screens. */
	public static function register_admin_menu() {
		$parent_hook = add_menu_page(
			__( 'Wellness Program', 'blc-wellness-management-system' ),
			__( 'Wellness Program', 'blc-wellness-management-system' ),
			'manage_options',
			'blc-wellness-program',
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-chart-area',
			58
		);
		$data_hook = add_submenu_page(
			'blc-wellness-program',
			__( 'Weightloss Data', 'blc-wellness-management-system' ),
			__( 'Weightloss Data', 'blc-wellness-management-system' ),
			'manage_options',
			'blc-weight-loss-data',
			array( __CLASS__, 'render_admin_page' )
		);
		self::$admin_page_hook = array( $parent_hook, $data_hook );
		add_submenu_page(
			'blc-wellness-program',
			__( 'Events', 'blc-wellness-management-system' ),
			__( 'Events', 'blc-wellness-management-system' ),
			'manage_options',
			'blc-wellness-events',
			array( __CLASS__, 'render_events_admin_page' )
		);
		add_submenu_page(
			'blc-wellness-program',
			__( 'Settings', 'blc-wellness-management-system' ),
			__( 'Settings', 'blc-wellness-management-system' ),
			'manage_options',
			'blc-wellness-settings',
			array( __CLASS__, 'render_settings_admin_page' )
		);
		// Keep the top-level landing screen useful while listing only requested child items.
		remove_submenu_page( 'blc-wellness-program', 'blc-wellness-program' );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/** Render the Events submenu placeholder. */
	public static function render_events_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage events.', 'blc-wellness-management-system' ) );
		}

		$message = '';
		if ( isset( $_POST['blc_event_action'] ) && in_array( sanitize_key( wp_unslash( $_POST['blc_event_action'] ) ), array( 'save', 'update', 'delete' ), true ) ) {
			$action  = sanitize_key( wp_unslash( $_POST['blc_event_action'] ) );
			$message = 'delete' === $action ? self::delete_admin_event() : self::save_admin_event();
		}
		$edit_id = isset( $_GET['edit_event'] ) ? absint( $_GET['edit_event'] ) : 0;
		$edit    = $edit_id ? get_post( $edit_id ) : null;
		if ( ! $edit || 'blc_wellness_event' !== $edit->post_type ) {
			$edit_id = 0;
			$edit    = null;
		}

		$events = get_posts(
			array(
				'post_type'      => 'blc_wellness_event',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'meta_value',
				'meta_key'       => '_blc_event_start',
				'order'          => 'ASC',
			)
		);

		echo '<div class="wrap"><h1>' . esc_html__( 'Events', 'blc-wellness-management-system' ) . '</h1>';
		if ( $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}
		echo '<h2>' . esc_html( $edit ? __( 'Edit Event', 'blc-wellness-management-system' ) : __( 'Add Event', 'blc-wellness-management-system' ) ) . '</h2>';
		echo '<form method="post" action=""><table class="form-table"><tbody>';
		echo '<tr><th scope="row"><label for="blc-event-title">' . esc_html__( 'Event name', 'blc-wellness-management-system' ) . '</label></th><td><input id="blc-event-title" name="event_title" type="text" class="regular-text" value="' . esc_attr( $edit ? $edit->post_title : '' ) . '" required></td></tr>';
		echo '<tr><th scope="row"><label for="blc-event-description">' . esc_html__( 'Description', 'blc-wellness-management-system' ) . '</label></th><td><textarea id="blc-event-description" name="event_description" class="large-text" rows="4">' . esc_textarea( $edit ? $edit->post_content : '' ) . '</textarea></td></tr>';
		echo '<tr><th scope="row"><label for="blc-event-start">' . esc_html__( 'Start date and time', 'blc-wellness-management-system' ) . '</label></th><td><input id="blc-event-start" name="event_start" type="datetime-local" value="' . esc_attr( $edit ? get_post_meta( $edit_id, '_blc_event_start', true ) : '' ) . '" required></td></tr>';
		echo '<tr><th scope="row"><label for="blc-event-end">' . esc_html__( 'End date and time', 'blc-wellness-management-system' ) . '</label></th><td><input id="blc-event-end" name="event_end" type="datetime-local" value="' . esc_attr( $edit ? get_post_meta( $edit_id, '_blc_event_end', true ) : '' ) . '" required></td></tr>';
		echo '<tr><th scope="row"><label for="blc-event-location">' . esc_html__( 'Location', 'blc-wellness-management-system' ) . '</label></th><td><input id="blc-event-location" name="event_location" type="text" class="regular-text" value="' . esc_attr( $edit ? get_post_meta( $edit_id, '_blc_event_location', true ) : '' ) . '"></td></tr>';
		echo '<tr><th scope="row"><label for="blc-event-url">' . esc_html__( 'Join URL', 'blc-wellness-management-system' ) . '</label></th><td><input id="blc-event-url" name="event_url" type="url" class="regular-text" placeholder="https://" value="' . esc_attr( $edit ? get_post_meta( $edit_id, '_blc_event_url', true ) : '' ) . '"></td></tr>';
		echo '</tbody></table>';
		wp_nonce_field( $edit ? 'blc_update_event_' . $edit_id : 'blc_save_event', 'blc_event_nonce' );
		echo '<input type="hidden" name="blc_event_action" value="' . esc_attr( $edit ? 'update' : 'save' ) . '">';
		if ( $edit ) {
			echo '<input type="hidden" name="event_id" value="' . esc_attr( $edit_id ) . '">';
		}
		submit_button( $edit ? __( 'Update Event', 'blc-wellness-management-system' ) : __( 'Add Event', 'blc-wellness-management-system' ) );
		if ( $edit ) {
			echo ' <a class="button" href="' . esc_url( admin_url( 'admin.php?page=blc-wellness-events' ) ) . '">' . esc_html__( 'Cancel', 'blc-wellness-management-system' ) . '</a>';
		}
		echo '</form><hr><h2>' . esc_html__( 'Saved Events', 'blc-wellness-management-system' ) . '</h2>';
		if ( ! $events ) {
			echo '<p>' . esc_html__( 'No events have been added yet.', 'blc-wellness-management-system' ) . '</p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Event', 'blc-wellness-management-system' ) . '</th><th>' . esc_html__( 'Starts', 'blc-wellness-management-system' ) . '</th><th>' . esc_html__( 'Ends', 'blc-wellness-management-system' ) . '</th><th>' . esc_html__( 'Location', 'blc-wellness-management-system' ) . '</th><th>' . esc_html__( 'Join URL', 'blc-wellness-management-system' ) . '</th><th>' . esc_html__( 'Actions', 'blc-wellness-management-system' ) . '</th></tr></thead><tbody>';
			foreach ( $events as $event ) {
				$event_url = get_post_meta( $event->ID, '_blc_event_url', true );
				echo '<tr><td>' . esc_html( get_the_title( $event ) ) . '</td><td>' . esc_html( get_post_meta( $event->ID, '_blc_event_start', true ) ) . '</td><td>' . esc_html( get_post_meta( $event->ID, '_blc_event_end', true ) ) . '</td><td>' . esc_html( get_post_meta( $event->ID, '_blc_event_location', true ) ) . '</td><td>';
				if ( $event_url ) {
					echo '<a href="' . esc_url( $event_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $event_url ) . '</a>';
				}
				echo '</td><td><a class="button button-small" href="' . esc_url( add_query_arg( array( 'page' => 'blc-wellness-events', 'edit_event' => $event->ID ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Edit', 'blc-wellness-management-system' ) . '</a> <form method="post" action="" style="display:inline" onsubmit="return confirm(\'' . esc_js( __( 'Delete this event?', 'blc-wellness-management-system' ) ) . '\');">';
				wp_nonce_field( 'blc_delete_event_' . $event->ID, 'blc_event_nonce' );
				echo '<input type="hidden" name="blc_event_action" value="delete"><input type="hidden" name="event_id" value="' . esc_attr( $event->ID ) . '"><button type="submit" class="button button-small">' . esc_html__( 'Delete', 'blc-wellness-management-system' ) . '</button></form></td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '</div>';
	}

	/** Render upcoming and in-progress events for the public Wellness Programs page. */
	public static function render_frontend_events() {
		$events = get_posts(
			array(
				'post_type'      => 'blc_wellness_event',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$now    = current_datetime();
		$active = array();

		foreach ( $events as $event ) {
			$start_value = get_post_meta( $event->ID, '_blc_event_start', true );
			if ( '' === $start_value ) {
				$start_value = get_post_meta( $event->ID, '_blc_event_datetime', true );
			}
			$start = self::parse_event_datetime( $start_value );
			if ( ! $start ) {
				continue;
			}

			$end_value = get_post_meta( $event->ID, '_blc_event_end', true );
			$end       = '' !== $end_value ? self::parse_event_datetime( $end_value ) : null;
			if ( ( '' !== $end_value && ( ! $end || $end < $now ) ) || ( '' === $end_value && $start < $now ) ) {
				continue;
			}
			$active[] = array( 'post' => $event, 'start' => $start, 'end' => $end );
		}

		usort(
			$active,
			static function ( $first, $second ) {
				return $first['start'] <=> $second['start'];
			}
		);

		if ( ! $active ) {
			return '<div class="blc-events-empty"><span class="blc-events-empty__icon" aria-hidden="true">✦</span><h3>' . esc_html__( 'No upcoming events', 'blc-wellness-management-system' ) . '</h3><p>' . esc_html__( 'Please check back soon for wellness events and activities.', 'blc-wellness-management-system' ) . '</p></div>';
		}

		$html = '<div class="blc-events-grid">';
		foreach ( $active as $item ) {
			$event    = $item['post'];
			$start    = $item['start'];
			$end      = $item['end'];
			$location = get_post_meta( $event->ID, '_blc_event_location', true );
			$event_url = get_post_meta( $event->ID, '_blc_event_url', true );
			$html .= '<article class="blc-event-card">';
			$html .= '<div class="blc-event-card__date"><span class="blc-event-card__month">' . esc_html( wp_date( 'M', $start->getTimestamp() ) ) . '</span><span class="blc-event-card__day">' . esc_html( wp_date( 'j', $start->getTimestamp() ) ) . '</span></div>';
			$html .= '<div class="blc-event-card__content"><p class="blc-event-card__eyebrow">' . esc_html( wp_date( 'l, ' . get_option( 'date_format' ), $start->getTimestamp() ) ) . '</p>';
			$html .= '<h3>' . esc_html( get_the_title( $event ) ) . '</h3>';
			$html .= '<p class="blc-event-card__time"><span aria-hidden="true">◷</span> ' . esc_html( wp_date( get_option( 'time_format' ), $start->getTimestamp() ) );
			if ( $end ) {
				$html .= ' – ' . esc_html( wp_date( wp_date( 'Y-m-d', $start->getTimestamp() ) === wp_date( 'Y-m-d', $end->getTimestamp() ) ? get_option( 'time_format' ) : get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $end->getTimestamp() ) );
			}
			$html .= '</p>';
			if ( $location ) {
				$html .= '<p class="blc-event-card__location"><span aria-hidden="true">⌖</span> ' . esc_html( $location ) . '</p>';
			}
			if ( $event->post_content ) {
				$html .= '<div class="blc-event-card__description">' . wp_kses_post( wpautop( $event->post_content ) ) . '</div>';
			}
			if ( $event_url ) {
				$html .= '<a class="blc-event-card__link" href="' . esc_url( $event_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Join event', 'blc-wellness-management-system' ) . '<span aria-hidden="true"> →</span></a>';
			}
			$html .= '</div></article>';
		}
		return $html . '</div>';
	}

	/** Parse the datetime-local value saved for an event in the site timezone. */
	private static function parse_event_datetime( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return false;
		}

		$date   = DateTimeImmutable::createFromFormat( '!Y-m-d\\TH:i', $value, wp_timezone() );
		$errors = DateTimeImmutable::getLastErrors();
		if ( ! $date || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) || $date->format( 'Y-m-d\\TH:i' ) !== $value ) {
			return false;
		}
		return $date;
	}

	/** Validate and store an event submitted from the admin screen. */
	private static function save_admin_event() {
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$action   = isset( $_POST['blc_event_action'] ) ? sanitize_key( wp_unslash( $_POST['blc_event_action'] ) ) : 'save';
		$nonce    = isset( $_POST['blc_event_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['blc_event_nonce'] ) ) : '';
		$nonce_action = 'update' === $action ? 'blc_update_event_' . $event_id : 'blc_save_event';
		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return __( 'The event could not be saved. Please reload and try again.', 'blc-wellness-management-system' );
		}
		if ( $event_id && ( ! get_post( $event_id ) || 'blc_wellness_event' !== get_post_type( $event_id ) ) ) {
			return __( 'The selected event could not be found.', 'blc-wellness-management-system' );
		}

		$title       = isset( $_POST['event_title'] ) ? sanitize_text_field( wp_unslash( $_POST['event_title'] ) ) : '';
		$description = isset( $_POST['event_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['event_description'] ) ) : '';
		$event_start = isset( $_POST['event_start'] ) ? sanitize_text_field( wp_unslash( $_POST['event_start'] ) ) : '';
		$event_end   = isset( $_POST['event_end'] ) ? sanitize_text_field( wp_unslash( $_POST['event_end'] ) ) : '';
		$location    = isset( $_POST['event_location'] ) ? sanitize_text_field( wp_unslash( $_POST['event_location'] ) ) : '';
		$event_url   = isset( $_POST['event_url'] ) ? esc_url_raw( wp_unslash( $_POST['event_url'] ), array( 'http', 'https' ) ) : '';
		if ( '' === $title || '' === $event_start || '' === $event_end ) {
			return __( 'Event name, start, and end date/time are required.', 'blc-wellness-management-system' );
		}
		if ( $event_end < $event_start ) {
			return __( 'The end date and time must be after the start date and time.', 'blc-wellness-management-system' );
		}

		$post_data = array(
			'ID'           => $event_id,
			'post_type'    => 'blc_wellness_event',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $description,
		);
		$saved_id = wp_insert_post(
			$post_data,
			true
		);
		if ( is_wp_error( $saved_id ) ) {
			return __( 'The event could not be saved.', 'blc-wellness-management-system' );
		}

		update_post_meta( $saved_id, '_blc_event_start', $event_start );
		update_post_meta( $saved_id, '_blc_event_end', $event_end );
		update_post_meta( $saved_id, '_blc_event_location', $location );
		update_post_meta( $saved_id, '_blc_event_url', $event_url );
		return $event_id ? __( 'Event updated.', 'blc-wellness-management-system' ) : __( 'Event added.', 'blc-wellness-management-system' );
	}

	/** Verify and delete an event submitted from the admin list. */
	private static function delete_admin_event() {
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$nonce    = isset( $_POST['blc_event_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['blc_event_nonce'] ) ) : '';
		if ( ! $event_id || ! wp_verify_nonce( $nonce, 'blc_delete_event_' . $event_id ) || 'blc_wellness_event' !== get_post_type( $event_id ) ) {
			return __( 'The event could not be deleted. Please reload and try again.', 'blc-wellness-management-system' );
		}

		return wp_delete_post( $event_id, true ) ? __( 'Event deleted.', 'blc-wellness-management-system' ) : __( 'The event could not be deleted.', 'blc-wellness-management-system' );
	}

	/** Render the Settings submenu placeholder. */
	public static function render_settings_admin_page() {
		self::render_admin_placeholder( __( 'Settings', 'blc-wellness-management-system' ), __( 'Wellness program settings will appear here.', 'blc-wellness-management-system' ) );
	}

	/** Render a simple administrator-only submenu screen. */
	private static function render_admin_placeholder( $title, $message ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'blc-wellness-management-system' ) );
		}

		echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $message ) . '</p></div>';
	}

	/** Load modal assets only on the weight loss data screen. */
	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, self::$admin_page_hook, true ) ) {
			return;
		}

		wp_enqueue_style( 'blc-weightloss-admin', plugins_url( '../assets/css/weightloss-admin.css', __FILE__ ), array(), BLC_WELLNESS_VERSION );
		wp_enqueue_script( 'blc-weightloss-admin', plugins_url( '../assets/js/weightloss-admin.js', __FILE__ ), array(), BLC_WELLNESS_VERSION, true );
	}

	/** Render saved plans and their associated WordPress users. */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view weight loss data.', 'blc-wellness-management-system' ) );
		}

		global $wpdb;
		$table       = self::table_name();
		$users_table = $wpdb->users;
		$search      = isset( $_GET['s'] ) && is_scalar( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';
		$page_number = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page    = 20;
		$offset      = ( $page_number - 1 ) * $per_page;
		$where       = '';

		if ( '' !== $search ) {
			$like  = '%' . $wpdb->esc_like( $search ) . '%';
			$where = $wpdb->prepare(
				'WHERE (u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s OR CAST(g.user_id AS CHAR) = %s)',
				$like,
				$like,
				$like,
				$search
			);
		}

		$count_sql = "SELECT COUNT(DISTINCT g.user_id) FROM {$table} g LEFT JOIN {$users_table} u ON u.ID = g.user_id {$where}";
		$total     = (int) $wpdb->get_var( $count_sql );
		$user_sql  = "SELECT g.user_id, MAX(g.updated_at) AS last_updated FROM {$table} g LEFT JOIN {$users_table} u ON u.ID = g.user_id {$where} GROUP BY g.user_id ORDER BY last_updated DESC, g.user_id ASC LIMIT %d OFFSET %d";
		$user_page = $wpdb->get_col( $wpdb->prepare( $user_sql, $per_page, $offset ) );
		$user_ids  = array_map( 'absint', is_array( $user_page ) ? $user_page : array() );
		$records   = array();
		if ( ! empty( $user_ids ) ) {
			$user_placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
			$list_sql          = "SELECT g.*, u.display_name, u.user_login, u.user_email FROM {$table} g LEFT JOIN {$users_table} u ON u.ID = g.user_id WHERE g.user_id IN ({$user_placeholders}) ORDER BY u.display_name ASC, g.user_id ASC, g.updated_at DESC, g.id DESC";
			$records           = $wpdb->get_results( $wpdb->prepare( $list_sql, $user_ids ), ARRAY_A );
		}
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );

		echo '<div class="wrap"><h1>' . esc_html__( 'Weight Loss Management Data', 'blc-wellness-management-system' ) . '</h1>';
		echo '<p>' . esc_html__( 'Saved plans and progress records are grouped by the WordPress account that owns them.', 'blc-wellness-management-system' ) . '</p>';
		echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
		echo '<input type="hidden" name="page" value="blc-weight-loss-data">';
		echo '<p class="search-box"><label class="screen-reader-text" for="blc-weight-loss-search">' . esc_html__( 'Search users', 'blc-wellness-management-system' ) . '</label>';
		echo '<input type="search" id="blc-weight-loss-search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Name, username, email, or user ID', 'blc-wellness-management-system' ) . '">';
		echo '<input type="submit" class="button" value="' . esc_attr__( 'Search', 'blc-wellness-management-system' ) . '"></p></form>';

		if ( empty( $records ) ) {
			echo '<p>' . esc_html__( 'No saved weight loss plans were found.', 'blc-wellness-management-system' ) . '</p></div>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'User', 'blc-wellness-management-system' ) . '</th>';
		echo '<th>' . esc_html__( 'Current weight', 'blc-wellness-management-system' ) . '</th>';
		echo '<th>' . esc_html__( 'Goal weight', 'blc-wellness-management-system' ) . '</th>';
		echo '<th>' . esc_html__( 'Maintenance', 'blc-wellness-management-system' ) . '</th>';
		echo '<th>' . esc_html__( 'Calorie target', 'blc-wellness-management-system' ) . '</th>';
		echo '<th>' . esc_html__( 'Last updated', 'blc-wellness-management-system' ) . '</th>';
		echo '<th>' . esc_html__( 'Saved details', 'blc-wellness-management-system' ) . '</th>';
		echo '</tr></thead><tbody>';
		$user_record_counts = array();
		foreach ( $records as $record ) {
			$user_key = (string) $record['user_id'];
			$user_record_counts[ $user_key ] = isset( $user_record_counts[ $user_key ] ) ? $user_record_counts[ $user_key ] + 1 : 1;
		}
		$rendered_users = array();

		foreach ( $records as $record ) {
			$profile   = json_decode( $record['profile_json'], true );
			$results   = json_decode( $record['results_json'], true );
			$nutrition = json_decode( $record['nutrition_json'], true );
			$progress  = json_decode( $record['progress_json'], true );
			$profile   = is_array( $profile ) ? $profile : array();
			$results   = is_array( $results ) ? $results : array();
			$nutrition = is_array( $nutrition ) ? $nutrition : array();
			$progress  = is_array( $progress ) ? $progress : array();
			$units     = isset( $profile['units'] ) ? $profile['units'] : $record['units'];
			$name      = ! empty( $record['display_name'] ) ? $record['display_name'] : __( 'Deleted user', 'blc-wellness-management-system' );
			$email     = isset( $record['user_email'] ) ? $record['user_email'] : '';
			$dialog_id = 'blc-weightloss-record-' . (int) $record['id'];
			$user_key  = (string) $record['user_id'];
			$is_first_user_record = ! isset( $rendered_users[ $user_key ] );

			echo '<tr' . ( $is_first_user_record ? ' class="blc-weightloss-user-group-start"' : '' ) . '>';
			if ( $is_first_user_record ) {
				echo '<td rowspan="' . esc_attr( $user_record_counts[ $user_key ] ) . '"><strong>' . esc_html( $name ) . '</strong><br><span class="description">' . esc_html( $email ) . '</span><br><span class="description">' . sprintf( esc_html__( 'User ID: %d', 'blc-wellness-management-system' ), (int) $record['user_id'] ) . '</span></td>';
				$rendered_users[ $user_key ] = true;
			}
			echo '<td>' . esc_html( self::format_weight( isset( $profile['current_weight_kg'] ) ? $profile['current_weight_kg'] : null, $units ) ) . '</td>';
			echo '<td>' . esc_html( self::format_weight( isset( $profile['goal_weight_kg'] ) ? $profile['goal_weight_kg'] : null, $units ) ) . '</td>';
			echo '<td>' . esc_html( isset( $results['maintenance_calories'] ) ? number_format_i18n( (int) $results['maintenance_calories'] ) . ' kcal/day' : '—' ) . '</td>';
			echo '<td>' . esc_html( ! empty( $results['calorie_target'] ) ? number_format_i18n( (int) $results['calorie_target'] ) . ' kcal/day' : '—' ) . '</td>';
			echo '<td>' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $record['updated_at'] ) ) . '</td>';
			echo '<td><span class="description">' . sprintf( esc_html__( 'Plan ID: %d', 'blc-wellness-management-system' ), (int) $record['id'] ) . '</span><br><button type="button" class="button button-primary blc-weightloss-open" aria-haspopup="dialog" aria-controls="' . esc_attr( $dialog_id ) . '">' . esc_html__( 'View details', 'blc-wellness-management-system' ) . '</button>';
			echo '<dialog class="blc-weightloss-dialog" id="' . esc_attr( $dialog_id ) . '" aria-labelledby="' . esc_attr( $dialog_id . '-title' ) . '"><div class="blc-weightloss-dialog__header"><div><p class="blc-weightloss-dialog__eyebrow">' . esc_html__( 'Saved weight loss plan', 'blc-wellness-management-system' ) . '</p><h2 id="' . esc_attr( $dialog_id . '-title' ) . '">' . esc_html( $name ) . '</h2><p>' . esc_html( $email ) . '</p></div><button type="button" class="blc-weightloss-close" aria-label="' . esc_attr__( 'Close details', 'blc-wellness-management-system' ) . '">&times;</button></div><div class="blc-weightloss-dialog__body">';
			$details_panel_id = $dialog_id . '-details-panel';
			$diet_panel_id    = $dialog_id . '-diet-panel';
			$orders_panel_id  = $dialog_id . '-orders-panel';
			echo '<div class="blc-weightloss-tabs" data-weightloss-tabs><div class="blc-weightloss-tabs__nav" role="tablist" aria-label="' . esc_attr__( 'User plan data', 'blc-wellness-management-system' ) . '">';
			echo '<button type="button" class="blc-weightloss-tab is-active" role="tab" id="' . esc_attr( $details_panel_id . '-tab' ) . '" aria-controls="' . esc_attr( $details_panel_id ) . '" aria-selected="true" tabindex="0">' . esc_html__( 'Plan Details', 'blc-wellness-management-system' ) . '</button>';
			echo '<button type="button" class="blc-weightloss-tab" role="tab" id="' . esc_attr( $diet_panel_id . '-tab' ) . '" aria-controls="' . esc_attr( $diet_panel_id ) . '" aria-selected="false" tabindex="-1">' . esc_html__( 'Recommended Diet Plan', 'blc-wellness-management-system' ) . '</button>';
			echo '<button type="button" class="blc-weightloss-tab" role="tab" id="' . esc_attr( $orders_panel_id . '-tab' ) . '" aria-controls="' . esc_attr( $orders_panel_id ) . '" aria-selected="false" tabindex="-1">' . esc_html__( 'WooCommerce Orders', 'blc-wellness-management-system' ) . '</button></div>';
			echo '<div class="blc-weightloss-tab-panel" id="' . esc_attr( $details_panel_id ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $details_panel_id . '-tab' ) . '">';
			self::render_admin_record_details( $profile, $results, $nutrition, $progress, $units );
			echo '</div><div class="blc-weightloss-tab-panel" id="' . esc_attr( $diet_panel_id ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $diet_panel_id . '-tab' ) . '" hidden>';
			self::render_admin_recommended_diet( $nutrition );
			echo '</div><div class="blc-weightloss-tab-panel" id="' . esc_attr( $orders_panel_id ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $orders_panel_id . '-tab' ) . '" hidden>';
			self::render_admin_user_orders( (int) $record['user_id'] );
			echo '</div></div>';
			echo '</div></dialog></td></tr>';
		}

		echo '</tbody></table>';
		if ( $total_pages > 1 ) {
			$base_url = add_query_arg( 'page', 'blc-weight-loss-data', admin_url( 'admin.php' ) ) . '&paged=%#%';
			if ( '' !== $search ) {
				$base_url .= '&s=' . rawurlencode( $search );
			}
			echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(
				paginate_links(
					array(
						'base'      => $base_url,
						'format'    => '',
						'current'   => $page_number,
						'total'     => $total_pages,
						'prev_text' => '&lsaquo;',
						'next_text' => '&rsaquo;',
						'type'      => 'plain',
					)
				)
			) . '</div></div>';
		}
		echo '</div>';
	}

	/** Render recent WooCommerce orders for a WordPress user. */
	private static function render_admin_user_orders( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			echo '<p>' . esc_html__( 'WooCommerce is not active, so order data is unavailable.', 'blc-wellness-management-system' ) . '</p>';
			return;
		}

		static $orders_cache = array();
		$user_id = absint( $user_id );
		if ( ! array_key_exists( $user_id, $orders_cache ) ) {
			$orders_cache[ $user_id ] = wc_get_orders(
				array(
					'customer_id' => $user_id,
					'limit'       => 20,
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			);
		}
		$orders = $orders_cache[ $user_id ];
		if ( empty( $orders ) || ! is_array( $orders ) ) {
			echo '<p>' . esc_html__( 'No WooCommerce orders were found for this user.', 'blc-wellness-management-system' ) . '</p>';
			return;
		}

		echo '<div class="blc-wellness-admin-details"><h4>' . esc_html__( 'Recent orders', 'blc-wellness-management-system' ) . '</h4><p class="description">' . esc_html__( 'Showing up to the 20 most recent orders associated with this WordPress account.', 'blc-wellness-management-system' ) . '</p>';
		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
				continue;
			}
			$status = $order->get_status();
			$status_label = function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $status ) : ucfirst( $status );
			$order_date = $order->get_date_created();
			$date_label = $order_date && function_exists( 'wc_format_datetime' ) ? wc_format_datetime( $order_date ) : '—';
			echo '<section class="blc-weightloss-order"><h5>' . sprintf( esc_html__( 'Order #%d', 'blc-wellness-management-system' ), (int) $order->get_id() ) . '</h5><dl>';
			echo '<dt>' . esc_html__( 'Date', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html( $date_label ) . '</dd>';
			echo '<dt>' . esc_html__( 'Status', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html( $status_label ) . '</dd>';
			echo '<dt>' . esc_html__( 'Total', 'blc-wellness-management-system' ) . '</dt><dd>' . wp_kses_post( $order->get_formatted_order_total() ) . '</dd>';
			echo '<dt>' . esc_html__( 'Products', 'blc-wellness-management-system' ) . '</dt><dd><ul class="blc-weightloss-order-products">';
			foreach ( $order->get_items() as $item ) {
				if ( ! is_object( $item ) || ! method_exists( $item, 'get_name' ) ) {
					continue;
				}
				$quantity = method_exists( $item, 'get_quantity' ) ? absint( $item->get_quantity() ) : 1;
				$product  = method_exists( $item, 'get_product' ) ? $item->get_product() : false;
				echo '<li class="blc-weightloss-order-product"><strong>' . esc_html( $item->get_name() ) . '</strong><dl>';
				if ( $product && is_object( $product ) && method_exists( $product, 'get_sku' ) && $product->get_sku() ) {
					echo '<dt>' . esc_html__( 'SKU', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html( $product->get_sku() ) . '</dd>';
				}
				if ( $product && is_object( $product ) && method_exists( $product, 'get_short_description' ) ) {
					$short_description = trim( wp_strip_all_tags( $product->get_short_description() ) );
					if ( '' !== $short_description ) {
						echo '<dt>' . esc_html__( 'Description', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html( $short_description ) . '</dd>';
					}
				}
				if ( method_exists( $item, 'get_formatted_meta_data' ) ) {
					$item_meta = $item->get_formatted_meta_data( '' );
					foreach ( $item_meta as $meta ) {
						if ( empty( $meta->display_key ) || ! isset( $meta->display_value ) ) {
							continue;
						}
						echo '<dt>' . esc_html( wp_strip_all_tags( $meta->display_key ) ) . '</dt><dd>' . esc_html( wp_strip_all_tags( $meta->display_value ) ) . '</dd>';
					}
				}
				$item_total = method_exists( $item, 'get_total' ) ? (float) $item->get_total() : 0;
				$item_tax   = method_exists( $item, 'get_total_tax' ) ? (float) $item->get_total_tax() : 0;
				$currency   = method_exists( $order, 'get_currency' ) ? $order->get_currency() : '';
				$line_total = function_exists( 'wc_price' ) ? wc_price( $item_total + $item_tax, array( 'currency' => $currency ) ) : number_format_i18n( $item_total + $item_tax, 2 );
				echo '<dt>' . esc_html__( 'Quantity', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html( number_format_i18n( $quantity ) ) . '</dd>';
				echo '<dt>' . esc_html__( 'Line total', 'blc-wellness-management-system' ) . '</dt><dd>' . wp_kses_post( $line_total ) . '</dd></dl></li>';
			}
			echo '</ul></dd></dl></section>';
		}
		echo '</div>';
	}

	/** Render diet recommendation and related nutrition preferences in the admin tab. */
	private static function render_admin_recommended_diet( $nutrition ) {
		$diet_type = isset( $nutrition['diet_type'] ) ? (string) $nutrition['diet_type'] : '';
		$diet_descriptions = array(
			'Balanced Diet'             => __( 'A flexible pattern that includes vegetables, fruits, whole grains, protein foods, and healthy fats without eliminating whole food groups.', 'blc-wellness-management-system' ),
			'Ketogenic (Keto) Diet'     => __( 'A very-low-carbohydrate, high-fat pattern intended to shift the body toward using fat and ketones for energy.', 'blc-wellness-management-system' ),
			'Paleo Diet'                => __( 'Emphasizes meat, seafood, vegetables, fruits, nuts, and seeds while excluding grains, legumes, and most dairy.', 'blc-wellness-management-system' ),
			'Vegetarian Diet'           => __( 'Plant-forward eating that excludes meat and seafood; versions may include eggs and dairy.', 'blc-wellness-management-system' ),
			'Vegan Diet'                => __( 'Excludes animal-derived foods and focuses entirely on plant foods; vitamin B12 and other nutrients need planning.', 'blc-wellness-management-system' ),
			'Mediterranean Diet'        => __( 'A flexible, plant-forward pattern emphasizing vegetables, fruits, whole grains, legumes, nuts, olive oil, and fish.', 'blc-wellness-management-system' ),
			'Intermittent Fasting'      => __( 'Alternates planned eating and fasting periods; food quality still matters and fasting is not appropriate for everyone.', 'blc-wellness-management-system' ),
			'Low-Carb Diet'             => __( 'Reduces carbohydrate intake to a variable degree, often limiting sugary and refined foods.', 'blc-wellness-management-system' ),
			'DASH Diet'                 => __( 'Emphasizes nutrient-rich foods while limiting sodium and foods high in saturated fat and added sugar.', 'blc-wellness-management-system' ),
			'Gluten-Free Diet'          => __( 'Eliminates gluten found primarily in wheat, barley, and rye; medically necessary for celiac disease.', 'blc-wellness-management-system' ),
			'Raw Food Diet'             => __( 'Focuses on foods that are raw or minimally heated; restrictive versions may make adequate nutrition difficult.', 'blc-wellness-management-system' ),
			'Carnivore Diet'            => __( 'Consists mainly or entirely of animal foods and excludes plant foods; long-term evidence is limited.', 'blc-wellness-management-system' ),
			'Flexitarian Diet'          => __( 'Centers meals on plant foods while allowing occasional meat, fish, or other animal products.', 'blc-wellness-management-system' ),
			'Whole30 Diet'              => __( 'A 30-day elimination-style program that removes several food groups before reintroducing foods; it is not intended as a permanent plan.', 'blc-wellness-management-system' ),
			'Zone Diet'                 => __( 'A structured approach that traditionally targets about 40% carbohydrate, 30% protein, and 30% fat at meals.', 'blc-wellness-management-system' ),
		);

		echo '<div class="blc-wellness-admin-details"><h4>' . esc_html__( 'Recommended diet', 'blc-wellness-management-system' ) . '</h4><dl>';
		if ( '' === $diet_type ) {
			echo '<dt>' . esc_html__( 'Selection', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html__( 'No diet type selected.', 'blc-wellness-management-system' ) . '</dd>';
		} else {
			echo '<dt>' . esc_html__( 'Diet type', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html( $diet_type ) . '</dd>';
			if ( isset( $diet_descriptions[ $diet_type ] ) ) {
				echo '<dt>' . esc_html__( 'About this diet', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html( $diet_descriptions[ $diet_type ] ) . '</dd>';
			}
		}
		$labels = array(
			'dietary_preference' => __( 'Dietary preference', 'blc-wellness-management-system' ),
			'allergies'          => __( 'Allergies', 'blc-wellness-management-system' ),
			'foods_avoided'      => __( 'Foods avoided', 'blc-wellness-management-system' ),
			'meals_per_day'      => __( 'Meals per day', 'blc-wellness-management-system' ),
			'cuisine'            => __( 'Cuisine preference', 'blc-wellness-management-system' ),
		);
		foreach ( $labels as $key => $label ) {
			if ( isset( $nutrition[ $key ] ) && '' !== $nutrition[ $key ] ) {
				echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $nutrition[ $key ] ) . '</dd>';
			}
		}
		echo '</dl><p class="description">' . esc_html__( 'This is the diet type the user selected. It is not a clinical prescription.', 'blc-wellness-management-system' ) . '</p></div>';
	}

	/** Render one saved record's questionnaire, plan, and progress details. */
	private static function render_admin_record_details( $profile, $results, $nutrition, $progress, $units ) {
		$profile_labels = array(
			'age'               => __( 'Age', 'blc-wellness-management-system' ),
			'sex'               => __( 'Sex', 'blc-wellness-management-system' ),
			'height_cm'         => __( 'Height', 'blc-wellness-management-system' ),
			'activity'          => __( 'Activity level', 'blc-wellness-management-system' ),
			'pace'              => __( 'Selected pace', 'blc-wellness-management-system' ),
			'target_date'       => __( 'Requested date', 'blc-wellness-management-system' ),
			'current_weight_kg' => __( 'Current weight', 'blc-wellness-management-system' ),
			'goal_weight_kg'    => __( 'Goal weight', 'blc-wellness-management-system' ),
		);

		echo '<div class="blc-wellness-admin-details"><h4>' . esc_html__( 'Questionnaire answers', 'blc-wellness-management-system' ) . '</h4><dl>';
		foreach ( $profile_labels as $key => $label ) {
			if ( ! isset( $profile[ $key ] ) || '' === $profile[ $key ] ) {
				continue;
			}
			$value = in_array( $key, array( 'current_weight_kg', 'goal_weight_kg' ), true )
				? self::format_weight( $profile[ $key ], $units )
				: ( 'height_cm' === $key ? number_format_i18n( (float) $profile[ $key ], 1 ) . ' cm' : $profile[ $key ] );
			echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd>';
		}
		echo '</dl><h4>' . esc_html__( 'Calculated estimates', 'blc-wellness-management-system' ) . '</h4><dl>';
		$result_labels = array(
			'bmr_calories'         => __( 'BMR estimate', 'blc-wellness-management-system' ),
			'maintenance_calories' => __( 'Maintenance calories', 'blc-wellness-management-system' ),
			'calorie_target'       => __( 'Daily calorie target', 'blc-wellness-management-system' ),
			'daily_deficit'        => __( 'Daily deficit', 'blc-wellness-management-system' ),
			'weekly_loss_kg'       => __( 'Weekly loss estimate', 'blc-wellness-management-system' ),
			'weeks_to_goal'        => __( 'Weeks to goal', 'blc-wellness-management-system' ),
			'estimated_goal_date'  => __( 'Estimated goal date', 'blc-wellness-management-system' ),
		);
		foreach ( $result_labels as $key => $label ) {
			if ( ! isset( $results[ $key ] ) || null === $results[ $key ] ) {
				continue;
			}
			$value = in_array( $key, array( 'bmr_calories', 'maintenance_calories', 'calorie_target', 'daily_deficit' ), true )
				? number_format_i18n( (int) $results[ $key ] ) . ' kcal'
				: ( 'weekly_loss_kg' === $key ? self::format_weight( $results[ $key ], $units ) . '/' . __( 'week', 'blc-wellness-management-system' ) : $results[ $key ] );
			echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd>';
		}
		if ( ! empty( $results['warnings'] ) && is_array( $results['warnings'] ) ) {
			echo '<dt>' . esc_html__( 'Safety notes', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html( implode( ' ', $results['warnings'] ) ) . '</dd>';
		}
		echo '</dl><h4>' . esc_html__( 'Nutrition preferences', 'blc-wellness-management-system' ) . '</h4><dl>';
		$nutrition_labels = array(
			'dietary_preference' => __( 'Dietary preference', 'blc-wellness-management-system' ),
			'allergies'          => __( 'Allergies', 'blc-wellness-management-system' ),
			'foods_avoided'      => __( 'Foods avoided', 'blc-wellness-management-system' ),
			'meals_per_day'      => __( 'Meals per day', 'blc-wellness-management-system' ),
			'cuisine'            => __( 'Cuisine preference', 'blc-wellness-management-system' ),
			'protein_g'          => __( 'Protein target', 'blc-wellness-management-system' ),
			'carbs_g'            => __( 'Carbohydrate target', 'blc-wellness-management-system' ),
			'fat_g'              => __( 'Fat target', 'blc-wellness-management-system' ),
		);
		foreach ( $nutrition_labels as $key => $label ) {
			if ( ! isset( $nutrition[ $key ] ) || '' === $nutrition[ $key ] ) {
				continue;
			}
			$value = in_array( $key, array( 'protein_g', 'carbs_g', 'fat_g' ), true ) ? $nutrition[ $key ] . ' g' : $nutrition[ $key ];
			echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd>';
		}
		if ( ! empty( $nutrition['meals'] ) && is_array( $nutrition['meals'] ) ) {
			echo '<dt>' . esc_html__( 'Meal ideas', 'blc-wellness-management-system' ) . '</dt><dd>' . esc_html( implode( ' ', $nutrition['meals'] ) ) . '</dd>';
		}
		echo '</dl><h4>' . esc_html__( 'Progress entries', 'blc-wellness-management-system' ) . '</h4>';
		if ( empty( $progress ) ) {
			echo '<p>' . esc_html__( 'No check-ins recorded.', 'blc-wellness-management-system' ) . '</p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Date', 'blc-wellness-management-system' ) . '</th><th>' . esc_html__( 'Weight', 'blc-wellness-management-system' ) . '</th><th>' . esc_html__( 'Calories', 'blc-wellness-management-system' ) . '</th><th>' . esc_html__( 'Exercise', 'blc-wellness-management-system' ) . '</th></tr></thead><tbody>';
			foreach ( $progress as $entry ) {
				echo '<tr><td>' . esc_html( isset( $entry['date'] ) ? $entry['date'] : '—' ) . '</td><td>' . esc_html( self::format_weight( isset( $entry['weight_kg'] ) ? $entry['weight_kg'] : null, $units ) ) . '</td><td>' . esc_html( isset( $entry['calories'] ) && null !== $entry['calories'] ? number_format_i18n( (int) $entry['calories'] ) : '—' ) . '</td><td>' . esc_html( isset( $entry['exercise_minutes'] ) && null !== $entry['exercise_minutes'] ? number_format_i18n( (int) $entry['exercise_minutes'] ) . ' min' : '—' ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '</div>';
	}

	/** Format canonical kilograms in the user's chosen display units. */
	private static function format_weight( $weight_kg, $units ) {
		if ( null === $weight_kg || ! is_numeric( $weight_kg ) ) {
			return '—';
		}
		if ( 'imperial' === $units ) {
			return number_format_i18n( (float) $weight_kg * 2.20462262, 1 ) . ' lb';
		}
		return number_format_i18n( (float) $weight_kg, 1 ) . ' kg';
	}

	/** Create or update the user goals table. */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			units varchar(12) NOT NULL,
			profile_json longtext NOT NULL,
			results_json longtext NOT NULL,
			nutrition_json longtext NOT NULL,
			progress_json longtext NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id)
		) {$charset_collate};";

		dbDelta( $sql );
		$created_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) );
		if ( $created_table === $table_name ) {
			update_option( 'blc_wellness_weight_loss_schema_version', self::SCHEMA_VERSION );
		}
	}

	/** Install schema upgrades for an already-active plugin. */
	public static function maybe_install() {
		if ( get_option( 'blc_wellness_weight_loss_schema_version' ) !== self::SCHEMA_VERSION ) {
			self::install();
		}
	}

	/** Register the calculator and tracker. */
	public static function render_shortcode( $atts = array(), $content = '' ) {
		return self::render();
	}

	/**
	 * Render the app inside the Weight Loss Management panel.
	 *
	 * @param bool $hidden Hide until its tab is activated.
	 * @return string
	 */
	public static function render( $hidden = false ) {
		$handle = 'blc-weightloss-management';
		$script_path = dirname( __DIR__ ) . '/frontend/weightloss-management.js';
		wp_enqueue_script(
			$handle,
			plugins_url( 'frontend/weightloss-management.js', dirname( __DIR__ ) . '/index.php' ),
			array(),
			file_exists( $script_path ) ? (string) filemtime( $script_path ) : BLC_WELLNESS_VERSION,
			true
		);
		wp_localize_script(
			$handle,
			'BLCWeightLossConfig',
			array(
				'apiUrl'   => esc_url_raw( rest_url( self::REST_NAMESPACE . '/weight-loss-goals' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'loggedIn' => is_user_logged_in(),
				'loginUrl' => wp_login_url( get_permalink() ),
				'today'    => current_time( 'Y-m-d' ),
			)
		);

		$panel_attributes = $hidden ? ' role="tabpanel" aria-labelledby="blc-tab-weight-loss-management" hidden' : '';

		return '<section id="weight-loss-management" class="blc-wellness-section blc-weightloss-management"' . $panel_attributes . '>' .
			'<h2 id="blc-weightloss-management-title">' . esc_html__( 'Weight Loss Management', 'blc-wellness-management-system' ) . '</h2>' .
			'<div class="blc-weightloss-app" data-blc-weightloss-app><div class="blc-weightloss-loading">' .
			esc_html__( 'Loading your Weight Loss Goal Calculator…', 'blc-wellness-management-system' ) .
			'</div></div></section>';
	}

	/** Register authenticated endpoints. */
	public static function register_rest_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/weight-loss-goals',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_goal' ),
					'permission_callback' => array( __CLASS__, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'save_goal' ),
					'permission_callback' => array( __CLASS__, 'check_permission' ),
				),
			)
		);
	}

	/** Require a signed-in WordPress user. */
	public static function check_permission() {
		if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
			return new WP_Error(
				'blc_weight_loss_login_required',
				__( 'Sign in to save and view your private weight loss plan.', 'blc-wellness-management-system' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/** Return the signed-in user's most recently updated plan. */
	public static function get_goal() {
		global $wpdb;

		$table = self::table_name();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY updated_at DESC, id DESC LIMIT 1",
			get_current_user_id()
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return rest_ensure_response( array( 'saved' => false ) );
		}

		return rest_ensure_response(
			array(
				'saved'     => true,
				'id'        => (int) $row['id'],
				'profile'   => json_decode( $row['profile_json'], true ),
				'results'   => json_decode( $row['results_json'], true ),
				'nutrition' => json_decode( $row['nutrition_json'], true ),
				'progress'  => json_decode( $row['progress_json'], true ),
			)
		);
	}

	/** Validate, calculate, and save the current user's plan and progress. */
	public static function save_goal( WP_REST_Request $request ) {
		global $wpdb;

		$payload  = $request->get_json_params();
		$payload  = is_array( $payload ) ? $payload : array();
		$profile  = self::sanitize_profile( isset( $payload['profile'] ) ? $payload['profile'] : array() );

		if ( is_wp_error( $profile ) ) {
			return $profile;
		}

		$results = self::calculate_results( $profile );
		$nutrition = self::sanitize_nutrition(
			isset( $payload['nutrition'] ) ? $payload['nutrition'] : array(),
			$profile,
			$results
		);
		$progress = self::sanitize_progress( isset( $payload['progress'] ) ? $payload['progress'] : array() );
		$id       = isset( $payload['id'] ) ? absint( $payload['id'] ) : 0;
		$user_id  = get_current_user_id();
		$table    = self::table_name();
		$now      = current_time( 'mysql', true );
		$data     = array(
			'user_id'        => $user_id,
			'units'          => $profile['units'],
			'profile_json'   => wp_json_encode( $profile ),
			'results_json'   => wp_json_encode( $results ),
			'nutrition_json' => wp_json_encode( $nutrition ),
			'progress_json'  => wp_json_encode( $progress ),
			'updated_at'     => $now,
		);
		$formats  = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' );

		if ( $id ) {
			$owner = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$table} WHERE id = %d", $id ) );
			if ( (int) $owner !== $user_id ) {
				return new WP_Error( 'blc_weight_loss_not_found', __( 'That saved plan could not be found.', 'blc-wellness-management-system' ), array( 'status' => 404 ) );
			}
			$wpdb->update( $table, $data, array( 'id' => $id, 'user_id' => $user_id ), $formats, array( '%d', '%d' ) );
		} else {
			$data['created_at'] = $now;
			$formats[]          = '%s';
			$wpdb->insert( $table, $data, $formats );
			$id = (int) $wpdb->insert_id;
		}

		if ( ! $id || $wpdb->last_error ) {
			return new WP_Error( 'blc_weight_loss_save_failed', __( 'Your plan could not be saved. Please try again.', 'blc-wellness-management-system' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'saved'     => true,
				'id'        => $id,
				'profile'   => $profile,
				'results'   => $results,
				'nutrition' => $nutrition,
				'progress'  => $progress,
			)
		);
	}

	/** Validate questionnaire answers and normalize text fields. */
	private static function sanitize_profile( $input ) {
		$input = is_array( $input ) ? $input : array();
		$units = isset( $input['units'] ) && is_scalar( $input['units'] ) ? sanitize_key( (string) $input['units'] ) : '';
		$sex   = isset( $input['sex'] ) && is_scalar( $input['sex'] ) ? sanitize_key( (string) $input['sex'] ) : '';
		$activity = isset( $input['activity'] ) && is_scalar( $input['activity'] ) ? sanitize_key( (string) $input['activity'] ) : '';
		$pace  = isset( $input['pace'] ) && is_scalar( $input['pace'] ) ? sanitize_key( (string) $input['pace'] ) : '';

		if ( empty( $input['eligibility_confirmed'] ) ) {
			return new WP_Error( 'blc_weight_loss_ineligible', __( 'This planning tool is for adults who are not pregnant or breastfeeding and do not have a condition requiring individualized nutrition advice.', 'blc-wellness-management-system' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $units, array( 'metric', 'imperial' ), true ) || ! in_array( $sex, array( 'male', 'female' ), true ) ) {
			return new WP_Error( 'blc_weight_loss_invalid_profile', __( 'Choose valid units and sex to calculate your estimate.', 'blc-wellness-management-system' ), array( 'status' => 400 ) );
		}

		$age          = isset( $input['age'] ) ? (int) $input['age'] : 0;
		$height_cm    = isset( $input['height_cm'] ) ? (float) $input['height_cm'] : 0;
		$current_kg   = isset( $input['current_weight_kg'] ) ? (float) $input['current_weight_kg'] : 0;
		$goal_kg      = isset( $input['goal_weight_kg'] ) ? (float) $input['goal_weight_kg'] : 0;
		$activities   = array( 'sedentary', 'light', 'moderate', 'very', 'extreme' );
		$paces        = array( 'slow', 'standard', 'faster', 'aggressive' );

		if ( $age < 18 || $age > 120 ) {
			return new WP_Error( 'blc_weight_loss_age', __( 'This calculator is for adults age 18 and older.', 'blc-wellness-management-system' ), array( 'status' => 400 ) );
		}
		if ( $height_cm < 100 || $height_cm > 250 || $current_kg < 25 || $current_kg > 500 || $goal_kg < 25 || $goal_kg >= $current_kg ) {
			return new WP_Error( 'blc_weight_loss_measurements', __( 'Check your height and weight. Your goal weight must be below your current weight.', 'blc-wellness-management-system' ), array( 'status' => 400 ) );
		}
		$goal_bmi = $goal_kg / ( ( $height_cm / 100 ) * ( $height_cm / 100 ) );
		if ( $goal_bmi < 18.5 ) {
			return new WP_Error( 'blc_weight_loss_goal_low', __( 'That goal weight is below the tool’s general adult healthy-weight screening range. Choose a higher goal or talk with a qualified healthcare professional.', 'blc-wellness-management-system' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $activity, $activities, true ) || ! in_array( $pace, $paces, true ) ) {
			return new WP_Error( 'blc_weight_loss_choices', __( 'Choose an activity level and weight-loss pace.', 'blc-wellness-management-system' ), array( 'status' => 400 ) );
		}

		$date = isset( $input['target_date'] ) && is_scalar( $input['target_date'] ) ? sanitize_text_field( (string) $input['target_date'] ) : '';
		if ( $date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return new WP_Error( 'blc_weight_loss_date', __( 'Choose a valid target date.', 'blc-wellness-management-system' ), array( 'status' => 400 ) );
		}
		if ( $date ) {
			$date_object = DateTime::createFromFormat( '!Y-m-d', $date, wp_timezone() );
			if ( ! $date_object || $date_object->format( 'Y-m-d' ) !== $date ) {
				return new WP_Error( 'blc_weight_loss_date', __( 'Choose a valid target date.', 'blc-wellness-management-system' ), array( 'status' => 400 ) );
			}
		}
		if ( $date && $date <= current_time( 'Y-m-d' ) ) {
			return new WP_Error( 'blc_weight_loss_date_past', __( 'Choose a target date in the future.', 'blc-wellness-management-system' ), array( 'status' => 400 ) );
		}

		return array(
			'eligibility_confirmed' => true,
			'units'                 => $units,
			'age'                   => $age,
			'sex'                   => $sex,
			'height_cm'             => round( $height_cm, 1 ),
			'current_weight_kg'     => round( $current_kg, 2 ),
			'goal_weight_kg'        => round( $goal_kg, 2 ),
			'activity'              => $activity,
			'pace'                  => $pace,
			'target_date'           => $date,
		);
	}

	/** Calculate energy estimates and cap unsafe targets. */
	private static function calculate_results( $profile ) {
		$activity_factors = array(
			'sedentary' => 1.2,
			'light'     => 1.375,
			'moderate'  => 1.55,
			'very'      => 1.725,
			'extreme'   => 1.9,
		);
		$pace_rates = array( 'slow' => 0.25, 'standard' => 0.5, 'faster' => 0.75, 'aggressive' => 1.0 );
		$current_kg = $profile['current_weight_kg'];
		$goal_kg    = $profile['goal_weight_kg'];
		$weight_loss = $current_kg - $goal_kg;
		$bmr        = ( 10 * $current_kg ) + ( 6.25 * $profile['height_cm'] ) - ( 5 * $profile['age'] ) + ( 'male' === $profile['sex'] ? 5 : -161 );
		$tdee       = $bmr * $activity_factors[ $profile['activity'] ];
		$max_rate   = min( 0.9, $current_kg * 0.01 );
		$rate       = min( $pace_rates[ $profile['pace'] ], $max_rate );
		$warnings   = array();

		if ( $rate < $pace_rates[ $profile['pace'] ] ) {
			$warnings[] = __( 'The selected pace was reduced to keep the estimated rate within a more gradual range.', 'blc-wellness-management-system' );
		}

		if ( $profile['target_date'] ) {
			$days_to_target = ( strtotime( $profile['target_date'] ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS;
			$date_rate      = $weight_loss / ( $days_to_target / 7 );
			if ( $date_rate > $max_rate ) {
				$warnings[] = __( 'Your target date would require a faster rate than this estimate supports. The plan uses a more gradual timeline instead.', 'blc-wellness-management-system' );
			} elseif ( $date_rate > $rate ) {
				$warnings[] = __( 'Your target date is faster than the selected pace. The plan keeps your selected pace and shows its estimated goal date instead.', 'blc-wellness-management-system' );
			} elseif ( $date_rate > 0 ) {
				$rate = $date_rate;
			}
		}

		$minimum_calories = 1200;
		$requested_deficit = $rate * 7700 / 7;
		$available_deficit = max( 0, $tdee - $minimum_calories );
		if ( $requested_deficit > $available_deficit ) {
			$rate = $available_deficit * 7 / 7700;
			$warnings[] = __( 'The requested pace would require a calorie target below the tool’s minimum. The estimated pace has been reduced to avoid recommending very low intake.', 'blc-wellness-management-system' );
		}

		$target_calories = $tdee > $minimum_calories ? max( $minimum_calories, round( $tdee - ( $rate * 7700 / 7 ) ) ) : null;
		if ( null === $target_calories ) {
			$warnings[] = __( 'A calorie-deficit target could not be estimated safely from these answers. Please speak with a qualified healthcare professional.', 'blc-wellness-management-system' );
		}

		$weeks = $rate > 0 ? (int) ceil( $weight_loss / $rate ) : null;
		$date  = $weeks ? gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' UTC' ) + ( $weeks * WEEK_IN_SECONDS ) ) : null;
		return array(
			'bmr_calories'          => (int) round( $bmr ),
			'maintenance_calories'  => (int) round( $tdee ),
			'calorie_target'        => $target_calories ? (int) $target_calories : null,
			'daily_deficit'         => $target_calories ? (int) round( $tdee - $target_calories ) : null,
			'weekly_loss_kg'        => round( $rate, 2 ),
			'weight_to_lose_kg'     => round( $weight_loss, 2 ),
			'loss_percentage'       => round( ( $weight_loss / $current_kg ) * 100, 1 ),
			'weeks_to_goal'         => $weeks,
			'estimated_goal_date'   => $date,
			'minimum_calories'      => $minimum_calories,
			'warnings'              => $warnings,
			'estimate'              => true,
		);
	}

	/** Sanitize nutrition preferences before they are stored. */
	private static function sanitize_nutrition( $input, $profile, $results ) {
		$input = is_array( $input ) ? $input : array();
		$meals = isset( $input['meals_per_day'] ) && is_scalar( $input['meals_per_day'] ) ? absint( $input['meals_per_day'] ) : 3;
		$allowed_diet_types = array(
			'Balanced Diet', 'Ketogenic (Keto) Diet', 'Paleo Diet', 'Vegetarian Diet', 'Vegan Diet',
			'Mediterranean Diet', 'Intermittent Fasting', 'Low-Carb Diet', 'DASH Diet', 'Gluten-Free Diet',
			'Raw Food Diet', 'Carnivore Diet', 'Flexitarian Diet', 'Whole30 Diet', 'Zone Diet',
		);
		$diet_type = isset( $input['diet_type'] ) && is_scalar( $input['diet_type'] ) ? sanitize_text_field( (string) $input['diet_type'] ) : '';
		$nutrition = array(
			'diet_type'          => in_array( $diet_type, $allowed_diet_types, true ) ? $diet_type : '',
			'dietary_preference' => isset( $input['dietary_preference'] ) && is_scalar( $input['dietary_preference'] ) ? sanitize_text_field( (string) $input['dietary_preference'] ) : '',
			'allergies'          => isset( $input['allergies'] ) && is_scalar( $input['allergies'] ) ? sanitize_textarea_field( (string) $input['allergies'] ) : '',
			'foods_avoided'      => isset( $input['foods_avoided'] ) && is_scalar( $input['foods_avoided'] ) ? sanitize_textarea_field( (string) $input['foods_avoided'] ) : '',
			'meals_per_day'      => min( 6, max( 2, $meals ) ),
			'cuisine'            => isset( $input['cuisine'] ) && is_scalar( $input['cuisine'] ) ? sanitize_text_field( (string) $input['cuisine'] ) : '',
		);
		if ( empty( $input['plan_created'] ) || empty( $results['calorie_target'] ) ) {
			return $nutrition;
		}

		$calories = (int) $results['calorie_target'];
		$protein  = min( (int) round( $profile['goal_weight_kg'] * 1.6 ), (int) floor( $calories * 0.3 / 4 ) );
		$fat      = (int) round( $calories * 0.25 / 9 );
		$carbs    = max( 0, (int) round( ( $calories - ( $protein * 4 ) - ( $fat * 9 ) ) / 4 ) );
		$meals    = array();
		for ( $index = 1; $index <= $nutrition['meals_per_day']; $index++ ) {
			$meal = sprintf(
				/* translators: %d is a meal number. */
				__( 'Meal %d: Choose a protein that fits your preferences, vegetables or fruit, and a high-fiber carbohydrate.', 'blc-wellness-management-system' ),
				$index
			);
			if ( $nutrition['cuisine'] ) {
				$meal .= ' ' . sprintf(
					/* translators: %s is a cuisine preference. */
					__( 'Use flavors you enjoy from %s cuisine.', 'blc-wellness-management-system' ),
					$nutrition['cuisine']
				);
			}
			$meals[] = $meal;
		}

		$nutrition['plan_created'] = true;
		$nutrition['protein_g']    = $protein;
		$nutrition['carbs_g']      = $carbs;
		$nutrition['fat_g']        = $fat;
		$nutrition['meals']        = $meals;
		$nutrition['meal_timing']  = sprintf(
			/* translators: %d is meals per day. */
			__( 'Spread your %d meals through your usual waking hours in a pattern that suits your schedule.', 'blc-wellness-management-system' ),
			$nutrition['meals_per_day']
		);
		$nutrition['water_goal'] = 'male' === $profile['sex']
			? __( 'General reference for total water from foods and all drinks: 3.7 L/day. Needs vary with activity, climate, and health; follow clinician advice.', 'blc-wellness-management-system' )
			: __( 'General reference for total water from foods and all drinks: 2.7 L/day. Needs vary with activity, climate, and health; follow clinician advice.', 'blc-wellness-management-system' );

		return $nutrition;
	}

	/** Sanitize weekly progress entries. */
	private static function sanitize_progress( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$entries = array();
		foreach ( array_slice( $input, -104 ) as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$weight = isset( $entry['weight_kg'] ) ? (float) $entry['weight_kg'] : 0;
			if ( $weight < 25 || $weight > 500 ) {
				continue;
			}
			$date = isset( $entry['date'] ) && is_scalar( $entry['date'] ) ? sanitize_text_field( (string) $entry['date'] ) : current_time( 'Y-m-d' );
			$date_object = DateTime::createFromFormat( '!Y-m-d', $date, wp_timezone() );
			if ( ! $date_object || $date_object->format( 'Y-m-d' ) !== $date ) {
				$date = current_time( 'Y-m-d' );
			}
			$entries[] = array(
				'date'             => $date,
				'weight_kg'        => round( $weight, 2 ),
				'calories'         => isset( $entry['calories'] ) ? min( 10000, max( 0, (int) $entry['calories'] ) ) : null,
				'exercise_minutes' => isset( $entry['exercise_minutes'] ) ? min( 1440, max( 0, (int) $entry['exercise_minutes'] ) ) : null,
			);
		}

		usort(
			$entries,
			static function ( $first, $second ) {
				return strcmp( $first['date'], $second['date'] );
			}
		);

		return $entries;
	}

	/** Return the fully prefixed custom table name. */
	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'weight_loss_goals';
	}
}

BLC_Weightloss_Management::init();

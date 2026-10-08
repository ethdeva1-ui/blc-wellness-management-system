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
	private static $admin_page_hook = '';

	/** Register public hooks. */
	public static function init() {
		add_shortcode( 'blc_weightloss_management', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
	}

	/** Add the administrator-only weight loss data screen. */
	public static function register_admin_menu() {
		self::$admin_page_hook = add_menu_page(
			__( 'Weight Loss Management Data', 'blc-wellness-management-system' ),
			__( 'Weight Loss Data', 'blc-wellness-management-system' ),
			'manage_options',
			'blc-weight-loss-data',
			array( __CLASS__, 'render_admin_page' ),
			'dashicons-chart-area',
			58
		);
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/** Load modal assets only on the weight loss data screen. */
	public static function enqueue_admin_assets( $hook ) {
		if ( self::$admin_page_hook !== $hook ) {
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

		$count_sql = "SELECT COUNT(*) FROM {$table} g LEFT JOIN {$users_table} u ON u.ID = g.user_id {$where}";
		$total     = (int) $wpdb->get_var( $count_sql );
		$list_sql  = "SELECT g.*, u.display_name, u.user_login, u.user_email FROM {$table} g LEFT JOIN {$users_table} u ON u.ID = g.user_id {$where} ORDER BY g.updated_at DESC, g.id DESC LIMIT %d OFFSET %d";
		$records   = $wpdb->get_results(
			$wpdb->prepare( $list_sql, $per_page, $offset ),
			ARRAY_A
		);
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

			echo '<tr><td><strong>' . esc_html( $name ) . '</strong><br><span class="description">' . esc_html( $email ) . '</span><br><span class="description">' . sprintf( esc_html__( 'User ID: %d · Plan ID: %d', 'blc-wellness-management-system' ), (int) $record['user_id'], (int) $record['id'] ) . '</span></td>';
			echo '<td>' . esc_html( self::format_weight( isset( $profile['current_weight_kg'] ) ? $profile['current_weight_kg'] : null, $units ) ) . '</td>';
			echo '<td>' . esc_html( self::format_weight( isset( $profile['goal_weight_kg'] ) ? $profile['goal_weight_kg'] : null, $units ) ) . '</td>';
			echo '<td>' . esc_html( isset( $results['maintenance_calories'] ) ? number_format_i18n( (int) $results['maintenance_calories'] ) . ' kcal/day' : '—' ) . '</td>';
			echo '<td>' . esc_html( ! empty( $results['calorie_target'] ) ? number_format_i18n( (int) $results['calorie_target'] ) . ' kcal/day' : '—' ) . '</td>';
			echo '<td>' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $record['updated_at'] ) ) . '</td>';
			echo '<td><button type="button" class="button button-primary blc-weightloss-open" aria-haspopup="dialog" aria-controls="' . esc_attr( $dialog_id ) . '">' . esc_html__( 'View details', 'blc-wellness-management-system' ) . '</button>';
			echo '<dialog class="blc-weightloss-dialog" id="' . esc_attr( $dialog_id ) . '" aria-labelledby="' . esc_attr( $dialog_id . '-title' ) . '"><div class="blc-weightloss-dialog__header"><div><p class="blc-weightloss-dialog__eyebrow">' . esc_html__( 'Saved weight loss plan', 'blc-wellness-management-system' ) . '</p><h2 id="' . esc_attr( $dialog_id . '-title' ) . '">' . esc_html( $name ) . '</h2><p>' . esc_html( $email ) . '</p></div><button type="button" class="blc-weightloss-close" aria-label="' . esc_attr__( 'Close details', 'blc-wellness-management-system' ) . '">&times;</button></div><div class="blc-weightloss-dialog__body">';
			self::render_admin_record_details( $profile, $results, $nutrition, $progress, $units );
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
			'diet_type'          => __( 'Recommended diet type', 'blc-wellness-management-system' ),
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

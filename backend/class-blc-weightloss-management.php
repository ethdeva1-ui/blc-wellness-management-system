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

	/** Register public hooks. */
	public static function init() {
		add_shortcode( 'blc_weightloss_management', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
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
		wp_enqueue_script(
			$handle,
			plugins_url( 'frontend/weightloss-management.js', dirname( __DIR__ ) . '/index.php' ),
			array(),
			'2.0.0',
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
		$nutrition = array(
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

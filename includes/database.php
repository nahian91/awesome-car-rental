<?php
/**
 * Database Setup & Schema Migration (`database.php`)
 * Provisions and upgrades custom MySQL tables.
 *
 * @package Awesome_Car_Rental
 * @since   1.4.5
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ACRB_DB_VERSION' ) ) {
	define( 'ACRB_DB_VERSION', '1.4.5' );
}

/**
 * Create or upgrade custom database tables safely using dbDelta.
 */
function acrb_create_custom_tables(): void {
	global $wpdb;

	$table_cars        = $wpdb->prefix . 'dev_nahian_cars';
	$table_bookings    = $wpdb->prefix . 'dev_nahian_bookings';
	$table_categories  = $wpdb->prefix . 'dev_nahian_categories';
	$table_staff       = $wpdb->prefix . 'dev_nahian_staff';
	$table_customers   = $wpdb->prefix . 'dev_nahian_customers';
	$table_inspections = $wpdb->prefix . 'dev_nahian_inspections';
	$table_maintenance = $wpdb->prefix . 'dev_nahian_maintenance';
	$table_promo       = $wpdb->prefix . 'dev_nahian_promo_codes';
	$table_finance     = $wpdb->prefix . 'dev_nahian_income_expense';

	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// 1. Cars Table Schema
	$sql_cars = "CREATE TABLE {$table_cars} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		brand varchar(255) DEFAULT '',
		year int(4) DEFAULT 2026,
		vin varchar(100) DEFAULT '',
		plate varchar(50) DEFAULT '',
		category varchar(100) DEFAULT '',
		color varchar(50) DEFAULT '',
		status varchar(50) DEFAULT 'active',
		description longtext DEFAULT '',
		pickup_locations longtext DEFAULT '',
		dropoff_locations longtext DEFAULT '',
		insurance_plans longtext DEFAULT '',
		border_crossing longtext DEFAULT '',
		seasonal_rates longtext DEFAULT '',
		transmission varchar(50) DEFAULT 'Automatic',
		fuel varchar(50) DEFAULT 'Petrol',
		seats int(3) DEFAULT 5,
		bags int(3) DEFAULT 2,
		min_days int(3) DEFAULT 1,
		price decimal(10,2) DEFAULT 0.00,
		odometer int(10) DEFAULT 0,
		fuel_level int(3) DEFAULT 100,
		image longtext DEFAULT '',
		gallery longtext DEFAULT '',
		video varchar(255) DEFAULT '',
		notes longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY status (status),
		KEY category (category)
	) {$charset_collate};";

	// 2. Bookings Table Schema (Fully Updated with Insurance, Border Permits & Promo Codes)
    $sql_bookings = "CREATE TABLE {$table_bookings} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        booking_code varchar(50) DEFAULT '',
        car_id mediumint(9) NOT NULL,
        car_name varchar(255) DEFAULT '',
        customer_name varchar(255) DEFAULT '',
        customer_email varchar(255) DEFAULT '',
        customer_phone varchar(100) DEFAULT '',
        pickup_location varchar(255) DEFAULT '',
        return_location varchar(255) DEFAULT '',
        pickup_date datetime DEFAULT NULL,
        return_date datetime DEFAULT NULL,
        insurance_plan varchar(255) DEFAULT '',
        border_permit varchar(255) DEFAULT '',
        promo_code varchar(50) DEFAULT '',
        total_amount decimal(10,2) DEFAULT 0.00,
        payment_method varchar(50) DEFAULT 'pay_later',
        booking_status varchar(50) DEFAULT 'confirmed',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) {$charset_collate};";

	// 3. Categories Table Schema
	$sql_categories = "CREATE TABLE {$table_categories} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		slug varchar(255) NOT NULL,
		description longtext DEFAULT '',
		image longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY slug (slug)
	) {$charset_collate};";

	// 4. Staff Table Schema
	$sql_staff = "CREATE TABLE {$table_staff} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		email varchar(255) DEFAULT '',
		phone varchar(100) DEFAULT '',
		role varchar(100) DEFAULT '',
		status varchar(50) DEFAULT 'active',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) {$charset_collate};";

	// 5. Customers Table Schema
	$sql_customers = "CREATE TABLE {$table_customers} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		user_id bigint(20) UNSIGNED DEFAULT 0,
		name varchar(255) NOT NULL,
		email varchar(255) DEFAULT '',
		phone varchar(100) DEFAULT '',
		license_number varchar(100) DEFAULT '',
		address longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY email (email(191)),
		KEY user_id (user_id)
	) {$charset_collate};";

	// 6. Inspections Table Schema
	$sql_inspections = "CREATE TABLE {$table_inspections} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		car_id mediumint(9) NOT NULL,
		car_name varchar(255) DEFAULT '',
		booking_id mediumint(9) DEFAULT 0,
		stage varchar(50) DEFAULT 'Check-Out',
		mileage varchar(100) DEFAULT '',
		fuel_level varchar(50) DEFAULT 'Full (100%)',
		cleanliness varchar(50) DEFAULT 'Clean',
		inspector varchar(255) DEFAULT '',
		damages longtext DEFAULT '',
		inspection_date datetime DEFAULT CURRENT_TIMESTAMP,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) {$charset_collate};";

	// 7. Maintenance Table Schema
	$sql_maintenance = "CREATE TABLE {$table_maintenance} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		car_name varchar(255) NOT NULL,
		service_type varchar(255) DEFAULT '',
		cost decimal(10,2) DEFAULT 0.00,
		date_in date DEFAULT NULL,
		status varchar(50) DEFAULT 'pending',
		notes longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) {$charset_collate};";

	// 8. Promo Codes Table Schema
	$sql_promo = "CREATE TABLE {$table_promo} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		code varchar(100) NOT NULL,
		discount_type varchar(50) DEFAULT 'percentage',
		discount_value decimal(10,2) DEFAULT 0.00,
		min_spend decimal(10,2) DEFAULT 0.00,
		expiry_date date DEFAULT NULL,
		max_uses mediumint(9) DEFAULT 0,
		status varchar(50) DEFAULT 'active',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY code (code)
	) {$charset_collate};";

	// 9. Financials Table Schema
	$sql_finance = "CREATE TABLE {$table_finance} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		type varchar(50) NOT NULL,
		category varchar(100) DEFAULT '',
		amount decimal(10,2) DEFAULT 0.00,
		transaction_date date DEFAULT NULL,
		description longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) {$charset_collate};";

	dbDelta( $sql_cars );
	dbDelta( $sql_bookings );
	dbDelta( $sql_categories );
	dbDelta( $sql_staff );
	dbDelta( $sql_customers );
	dbDelta( $sql_inspections );
	dbDelta( $sql_maintenance );
	dbDelta( $sql_promo );
	dbDelta( $sql_finance );

	update_option( 'acrb_db_version', ACRB_DB_VERSION );
}

/**
 * Hook triggered on plugin activation.
 * Creates any missing tables and updates column schemas.
 */
function acrb_activate_plugin_database(): void {
	acrb_create_custom_tables();
	delete_transient( 'acrb_dashboard_revenue' );
}
if ( defined( 'ACRB_PLUGIN_FILE' ) ) {
	register_activation_hook( ACRB_PLUGIN_FILE, 'acrb_activate_plugin_database' );
}

/**
 * Hook triggered on plugin deactivation.
 * Cleans up temporary caches and transients safely without destroying user records.
 */
function acrb_deactivate_plugin_database(): void {
	delete_transient( 'acrb_dashboard_revenue' );
	wp_cache_flush();
}
if ( defined( 'ACRB_PLUGIN_FILE' ) ) {
	register_deactivation_hook( ACRB_PLUGIN_FILE, 'acrb_deactivate_plugin_database' );
}

/**
 * Fallback check on admin page load.
 * Verifies version upgrades and checks if any required tables are missing.
 */
function acrb_maybe_create_custom_tables(): void {
	$current_db_version = get_option( 'acrb_db_version', '0' );

	if ( version_compare( (string) $current_db_version, ACRB_DB_VERSION, '<' ) ) {
		acrb_create_custom_tables();
		return;
	}

	global $wpdb;

	$required_tables = array(
		$wpdb->prefix . 'dev_nahian_cars',
		$wpdb->prefix . 'dev_nahian_bookings',
		$wpdb->prefix . 'dev_nahian_categories',
		$wpdb->prefix . 'dev_nahian_staff',
		$wpdb->prefix . 'dev_nahian_customers',
		$wpdb->prefix . 'dev_nahian_inspections',
		$wpdb->prefix . 'dev_nahian_maintenance',
		$wpdb->prefix . 'dev_nahian_promo_codes',
		$wpdb->prefix . 'dev_nahian_income_expense',
	);

	foreach ( $required_tables as $table_name ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			acrb_create_custom_tables();
			break;
		}
	}
}
add_action( 'admin_init', 'acrb_maybe_create_custom_tables' );
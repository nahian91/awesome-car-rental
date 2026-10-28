<?php
/**
 * Database Setup & Schema Migration (`database.php`)
 * Provisions and upgrades custom MySQL tables for Cars, Bookings, Categories, Staff, Customers, Maintenance, Coupons, and Financials.
 *
 * @package Awesome_Car_Rental
 * @since   1.4.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create custom database tables safely using dbDelta.
 */
function acrb_create_custom_tables() {
	global $wpdb;

	$table_cars           = $wpdb->prefix . 'dev_nahian_cars';
	$table_bookings       = $wpdb->prefix . 'dev_nahian_bookings';
	$table_categories     = $wpdb->prefix . 'dev_nahian_categories';
	$table_staff          = $wpdb->prefix . 'dev_nahian_staff';
	$table_customers      = $wpdb->prefix . 'dev_nahian_customers';
	$table_maintenance    = $wpdb->prefix . 'dev_nahian_maintenance';
	$table_promo          = $wpdb->prefix . 'dev_nahian_promo_codes';
	$table_finance        = $wpdb->prefix . 'dev_nahian_income_expense';
	$charset_collate      = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// 1. Cars Table
	$sql_cars = "CREATE TABLE IF NOT EXISTS {$table_cars} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		brand varchar(255) DEFAULT '',
		year int(4) DEFAULT 2026,
		vin varchar(100) DEFAULT '',
		plate varchar(50) DEFAULT '',
		category varchar(100) DEFAULT '',
		color varchar(50) DEFAULT '',
		status varchar(50) DEFAULT 'ready',
		location varchar(255) DEFAULT '',
		horsepower int(5) DEFAULT 0,
		acceleration varchar(50) DEFAULT '',
		speed varchar(50) DEFAULT '',
		drivetrain varchar(50) DEFAULT 'AWD',
		transmission varchar(50) DEFAULT 'automatic',
		fuel varchar(50) DEFAULT 'gasoline',
		seats int(3) DEFAULT 2,
		doors int(3) DEFAULT 4,
		bags int(3) DEFAULT 2,
		price decimal(10,2) DEFAULT 0.00,
		deposit decimal(10,2) DEFAULT 0.00,
		odometer int(10) DEFAULT 0,
		fuel_level int(3) DEFAULT 100,
		image longtext DEFAULT '',
		notes longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate};";

	// 2. Bookings Table
	$sql_bookings = "CREATE TABLE IF NOT EXISTS {$table_bookings} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		booking_code varchar(50) DEFAULT '',
		car_id mediumint(9) NOT NULL,
		car_name varchar(255) DEFAULT '',
		customer_name varchar(255) DEFAULT '',
		customer_email varchar(255) DEFAULT '',
		customer_phone varchar(100) DEFAULT '',
		pickup_date date DEFAULT NULL,
		return_date date DEFAULT NULL,
		total_amount decimal(10,2) DEFAULT 0.00,
		booking_status varchar(50) DEFAULT 'confirmed',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate};";

	// 3. Categories Table
	$sql_categories = "CREATE TABLE IF NOT EXISTS {$table_categories} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		slug varchar(255) NOT NULL,
		description longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY slug (slug)
	) {$charset_collate};";

	// 4. Staff Table
	$sql_staff = "CREATE TABLE IF NOT EXISTS {$table_staff} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		email varchar(255) DEFAULT '',
		phone varchar(100) DEFAULT '',
		role varchar(100) DEFAULT '',
		status varchar(50) DEFAULT 'active',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate};";

	// 5. Customers Table
	$sql_customers = "CREATE TABLE IF NOT EXISTS {$table_customers} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL,
		email varchar(255) DEFAULT '',
		phone varchar(100) /******/ DEFAULT '',
		address longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate};";

	// 6. Maintenance Table (Repairs)
	$sql_maintenance = "CREATE TABLE IF NOT EXISTS {$table_maintenance} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		car_name varchar(255) NOT NULL,
		service_type varchar(255) DEFAULT '',
		cost decimal(10,2) DEFAULT 0.00,
		date_in date DEFAULT NULL,
		status varchar(50) DEFAULT 'pending',
		notes longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate};";

	// 7. Promo Codes Table (Coupons)
	$sql_promo = "CREATE TABLE IF NOT EXISTS {$table_promo} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		code varchar(100) NOT NULL,
		discount_type varchar(50) DEFAULT 'percentage',
		discount_value decimal(10,2) DEFAULT 0.00,
		expiry_date date DEFAULT NULL,
		status varchar(50) DEFAULT 'active',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY code (code)
	) {$charset_collate};";

	// 8. Income & Expense Table (Financials)
	$sql_finance = "CREATE TABLE IF NOT EXISTS {$table_finance} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		type varchar(50) NOT NULL,
		category varchar(100) DEFAULT '',
		amount decimal(10,2) DEFAULT 0.00,
		transaction_date date DEFAULT NULL,
		description longtext DEFAULT '',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id)
	) {$charset_collate};";

	dbDelta( $sql_cars );
	dbDelta( $sql_bookings );
	dbDelta( $sql_categories );
	dbDelta( $sql_staff );
	dbDelta( $sql_customers );
	dbDelta( $sql_maintenance );
	dbDelta( $sql_promo );
	dbDelta( $sql_finance );
}
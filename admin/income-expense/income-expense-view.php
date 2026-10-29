<?php
/**
 * Income & Expense CRM View Component (`income-expense-view.php`)
 * Location: admin/income-expense/income-expense-view.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_finance = $wpdb->prefix . 'dev_nahian_income_expense';
$currency      = get_option( 'acrb_currency', '£' );
$entry_id      = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
$page_slug     = 'awesome_car_rental';
$base_url      = admin_url( 'admin.php?page=' . $page_slug . '&tab=income_expense' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_finance} WHERE id = %d", $entry_id ), ARRAY_A );

if ( ! $entry ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Ledger transaction not found.', 'awesome-car-rental' ) . '</p></div>';
    return;
}

$t_id   = absint( $entry['id'] );
$type   = sanitize_key( $entry['type'] ?? 'expense' );
$amount = floatval( $entry['amount'] ?? 0 );
$cat    = esc_html( $entry['category'] ?? 'General' );
$desc   = esc_html( $entry['description'] ?? '—' );
$date   = esc_html( $entry['transaction_date'] ?? '—' );
$logged = esc_html( $entry['created_at'] ?? '—' );
$edit_url = add_query_arg( array( 'sub' => 'edit', 'entry_id' => $t_id ), $base_url );

$is_inc      = ( 'income' === $type );
$badge_bg    = $is_inc ? '#ecfdf5' : '#fee2e2';
$badge_color = $is_inc ? '#059669' : '#b91c1c';
?>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: start;">
    <!-- Transaction Dossier Card -->
    <div class="acrb-card" style="text-align: center;">
        <div style="width: 72px; height: 72px; background: #eef2ff; color: #4f46e5; font-size: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto; border: 2px solid #e0e7ff;">
            <span class="dashicons dashicons-money-alt" style="font-size: 32px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"></span>
        </div>
        <h3 style="margin: 0 0 6px; font-size: 17px; font-weight: 700; color: #0f172a;">#<?php echo absint( $t_id ); ?> Transaction</h3>
        <span style="background: <?php echo esc_attr( $badge_bg ); ?>; color: <?php echo esc_attr( $badge_color ); ?>; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
            ● <?php echo esc_html( $type ); ?>
        </span>

        <div style="margin-top: 20px; text-align: left; border-top: 1px solid #f1f5f9; padding-top: 16px; display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Amount', 'awesome-car-rental' ); ?></span>
                <strong style="color: <?php echo esc_attr( $badge_color ); ?>; font-size: 14px;">
                    <?php echo esc_html( ( $is_inc ? '+' : '-' ) . $currency . number_format( $amount, 2 ) ); ?>
                </strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $cat ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Transaction Date', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $date ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 8px;">
                <span style="color: #64748b;"><?php esc_html_e( 'Logged On', 'awesome-car-rental' ); ?></span>
                <strong style="color: #0f172a;"><?php echo esc_html( $logged ); ?></strong>
            </div>
        </div>

        <div style="margin-top: 20px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-primary" style="width: 100%;">
                <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span><?php esc_html_e( 'Edit Transaction', 'awesome-car-rental' ); ?></span>
            </a>
        </div>
    </div>

    <!-- Description & Context -->
    <div class="acrb-card">
        <h2><?php esc_html_e( 'Transaction Details', 'awesome-car-rental' ); ?></h2>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px;">
            <h4 style="margin: 0 0 10px 0; font-size: 13.5px; font-weight: 700; color: #334155; text-transform: uppercase;"><?php esc_html_e( 'Description / Reference', 'awesome-car-rental' ); ?></h4>
            <p style="margin: 0; font-size: 14px; color: #475569; line-height: 1.6; white-space: pre-line;">
                <?php echo esc_html( $desc ); ?>
            </p>
        </div>
    </div>
</div>
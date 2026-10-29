<?php
/**
 * Add / Edit Ledger Component (`income-expense-add-edit.php`)
 * Location: admin/income-expense/income-expense-add-edit.php
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
$entry_id      = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;

// DATA FETCHING FOR EDIT STATE
$entry = null;
if ( $entry_id > 0 ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_finance} WHERE id = %d", $entry_id ), ARRAY_A );
}

$e_type   = $entry['type'] ?? 'expense';
$e_amount = $entry['amount'] ?? '';
$e_cat    = $entry['category'] ?? 'Maintenance';
$e_date   = ! empty( $entry['transaction_date'] ) ? $entry['transaction_date'] : current_time( 'Y-m-d' );
$e_desc   = $entry['description'] ?? '';
?>

<div class="acrb-card" style="max-width: 900px; margin: 0 auto;">
    <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;">
            <p><strong><?php esc_html_e( 'Success:', 'awesome-car-rental' ); ?></strong> 
                <?php 
                if ( 'updated' === $_GET['updated'] ) {
                    esc_html_e( 'Ledger transaction updated successfully.', 'awesome-car-rental' );
                } else {
                    esc_html_e( 'New ledger transaction recorded successfully.', 'awesome-car-rental' );
                }
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
        <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">
            <?php echo $entry_id > 0 ? sprintf( esc_html__( 'Edit Ledger Transaction #%d', 'awesome-car-rental' ), absint( $entry_id ) ) : esc_html__( 'Record New Ledger Transaction', 'awesome-car-rental' ); ?>
        </h2>
    </div>
    
    <form method="post">
        <?php wp_nonce_field( 'acrb_ledger_action', 'acrb_ledger_nonce' ); ?>
        <input type="hidden" name="entry_id" value="<?php echo absint( $entry_id ); ?>">

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Transaction Type', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="entry_type" class="acrb-select">
                        <option value="expense" <?php selected( $e_type, 'expense' ); ?>><?php esc_html_e( 'Expense (Money Out)', 'awesome-car-rental' ); ?></option>
                        <option value="income" <?php selected( $e_type, 'income' ); ?>><?php esc_html_e( 'Income (Manual Money In)', 'awesome-car-rental' ); ?></option>
                    </select>
                    <span class="acrb-select-chevron">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php printf( esc_html__( 'Amount (%s)', 'awesome-car-rental' ), esc_html( $currency ) ); ?></label>
                <input type="number" step="0.01" name="entry_amount" value="<?php echo esc_attr( (string) $e_amount ); ?>" class="acrb-input" placeholder="0.00" required>
            </div>
        </div>

        <div class="acrb-form-group" style="margin-bottom:18px;">
            <label class="acrb-label"><?php esc_html_e( 'Transaction Description / Reference', 'awesome-car-rental' ); ?></label>
            <input type="text" name="entry_title" value="<?php echo esc_attr( $e_desc ); ?>" class="acrb-input" placeholder="<?php esc_attr_e( 'e.g. Monthly Commercial Fleet Insurance Policy', 'awesome-car-rental' ); ?>" required>
        </div>

        <div class="acrb-form-grid-2">
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></label>
                <div class="acrb-select-wrap">
                    <select name="entry_category" class="acrb-select">
                        <option value="Maintenance" <?php selected( $e_cat, 'Maintenance' ); ?>><?php esc_html_e( 'Maintenance & Repairs', 'awesome-car-rental' ); ?></option>
                        <option value="Insurance" <?php selected( $e_cat, 'Insurance' ); ?>><?php esc_html_e( 'Insurance', 'awesome-car-rental' ); ?></option>
                        <option value="Fuel & Wash" <?php selected( $e_cat, 'Fuel & Wash' ); ?>><?php esc_html_e( 'Fuel & Detailing', 'awesome-car-rental' ); ?></option>
                        <option value="Lease / Loan" <?php selected( $e_cat, 'Lease / Loan' ); ?>><?php esc_html_e( 'Vehicle Lease / Loan', 'awesome-car-rental' ); ?></option>
                        <option value="Marketing" <?php selected( $e_cat, 'Marketing' ); ?>><?php esc_html_e( 'Marketing & Ads', 'awesome-car-rental' ); ?></option>
                        <option value="Salary" <?php selected( $e_cat, 'Salary' ); ?>><?php esc_html_e( 'Staff Salary / Wages', 'awesome-car-rental' ); ?></option>
                        <option value="Other" <?php selected( $e_cat, 'Other' ); ?>><?php esc_html_e( 'General / Other', 'awesome-car-rental' ); ?></option>
                    </select>
                    <span class="acrb-select-chevron">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
            </div>
            <div class="acrb-form-group">
                <label class="acrb-label"><?php esc_html_e( 'Transaction Date', 'awesome-car-rental' ); ?></label>
                <input type="date" name="entry_date" value="<?php echo esc_attr( $e_date ); ?>" class="acrb-input">
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right;">
            <button type="submit" name="acrb_save_ledger" class="acrb-btn-primary" style="height: 44px; padding: 0 32px; font-size: 14px;">
                <span class="dashicons <?php echo $entry_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>" style="font-size:17px; width:17px; height:17px; display:inline-flex; align-items:center; justify-content:center;"></span>
                <span><?php echo $entry_id > 0 ? esc_html__( 'Update Ledger Entry', 'awesome-car-rental' ) : esc_html__( 'Save Ledger Entry', 'awesome-car-rental' ); ?></span>
            </button>
        </div>
    </form>
</div>
<?php
/**
 * Add / Edit Ledger Component (`income-expense-add-edit.php`)
 * Location: admin/income-expense/income-expense-add-edit.php
 *
 * @package Awesome_Car_Rental
 * @since   3.0.4
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-car-rental' ), 403 );
}

global $wpdb;
$table_finance   = $wpdb->prefix . 'dev_nahian_income_expense';
$currency_symbol = get_option( 'acrb_currency', '$' );
$entry_id        = isset( $_GET['entry_id'] ) ? absint( wp_unslash( $_GET['entry_id'] ) ) : ( isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0 );
$list_url        = admin_url( 'admin.php?page=awesome_car_rental&tab=income_expense' );

// Data fetching for edit state
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

<style>
    .acrb-ledger-card {
        max-width: 860px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 28px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        box-sizing: border-box;
    }
    .acrb-ledger-card *,
    .acrb-ledger-card *::before,
    .acrb-ledger-card *::after {
        box-sizing: border-box;
    }
    .acrb-ledger-header-title {
        margin: 0 0 24px;
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 14px;
    }

    /* Grids */
    .acrb-ledger-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 18px;
    }
    .acrb-ledger-form-group {
        display: flex;
        flex-direction: column;
    }
    .acrb-ledger-form-group-full {
        margin-bottom: 18px;
    }
    .acrb-ledger-label {
        display: block;
        font-weight: 700;
        color: #1e293b;
        font-size: 13.5px;
        margin-bottom: 6px;
    }

    /* Form Controls */
    .acrb-ledger-input,
    .acrb-ledger-select {
        display: block;
        width: 100% !important;
        max-width: 100% !important;
        height: 42px !important;
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 0 14px !important;
        font-size: 13.5px !important;
        font-family: inherit !important;
        color: #1e293b !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03) !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease !important;
        line-height: 40px !important;
    }
    .acrb-ledger-input:hover,
    .acrb-ledger-select:hover {
        border-color: #94a3b8 !important;
        background: #ffffff !important;
    }
    .acrb-ledger-input:focus,
    .acrb-ledger-select:focus {
        background: #ffffff !important;
        border-color: #4f46e5 !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
    }

    /* Select Dropdown Wrap */
    .acrb-ledger-select-wrap {
        position: relative;
        width: 100%;
    }
    .acrb-ledger-select-wrap select {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 36px !important;
        cursor: pointer;
    }
    .acrb-ledger-select-arrow {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #64748b;
        font-size: 18px;
        width: 18px;
        height: 18px;
    }

    /* Action Buttons */
    .acrb-ledger-btn-actions {
        margin-top: 28px;
        display: flex;
        gap: 12px;
        align-items: center;
        border-top: 1px solid #f1f5f9;
        padding-top: 20px;
    }
    .acrb-ledger-btn-submit {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 11px 24px;
        font-size: 13.5px;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
        transition: background 0.15s ease, transform 0.15s ease;
    }
    .acrb-ledger-btn-submit:hover {
        background: #4338ca;
        color: #ffffff;
        transform: translateY(-1px);
    }
    .acrb-ledger-btn-submit .dashicons {
        font-size: 17px;
        width: 17px;
        height: 17px;
    }
    .acrb-ledger-btn-cancel {
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 14px;
        border-radius: 8px;
        transition: color 0.15s ease;
    }
    .acrb-ledger-btn-cancel:hover {
        color: #0f172a;
    }

    @media (max-width: 780px) {
        .acrb-ledger-grid-2 {
            grid-template-columns: 1fr;
            gap: 14px;
        }
    }
</style>

<div class="acrb-ledger-card">
    <h2 class="acrb-ledger-header-title">
        <?php echo $entry_id > 0 ? sprintf( esc_html__( 'Edit Ledger Transaction #%d', 'awesome-car-rental' ), absint( $entry_id ) ) : esc_html__( 'Record New Ledger Transaction', 'awesome-car-rental' ); ?>
    </h2>

    <form method="post">
        <?php wp_nonce_field( 'acrb_ledger_action', 'acrb_ledger_nonce' ); ?>
        <input type="hidden" name="entry_id" value="<?php echo absint( $entry_id ); ?>">

        <div class="acrb-ledger-grid-2">
            <!-- Transaction Type -->
            <div class="acrb-ledger-form-group">
                <label class="acrb-ledger-label" for="entry_type"><?php esc_html_e( 'Transaction Type', 'awesome-car-rental' ); ?></label>
                <div class="acrb-ledger-select-wrap">
                    <select id="entry_type" name="entry_type" class="acrb-ledger-select">
                        <option value="expense" <?php selected( $e_type, 'expense' ); ?>><?php esc_html_e( 'Expense (Money Out)', 'awesome-car-rental' ); ?></option>
                        <option value="income" <?php selected( $e_type, 'income' ); ?>><?php esc_html_e( 'Income (Manual Money In)', 'awesome-car-rental' ); ?></option>
                    </select>
                    <span class="dashicons dashicons-arrow-down-alt2 acrb-ledger-select-arrow"></span>
                </div>
            </div>

            <!-- Amount -->
            <div class="acrb-ledger-form-group">
                <label class="acrb-ledger-label" for="entry_amount"><?php printf( esc_html__( 'Amount (%s)', 'awesome-car-rental' ), esc_html( $currency_symbol ) ); ?></label>
                <input type="number" id="entry_amount" step="0.01" min="0.01" name="entry_amount" value="<?php echo esc_attr( (string) $e_amount ); ?>" class="acrb-ledger-input" placeholder="0.00" required>
            </div>
        </div>

        <!-- Description / Reference -->
        <div class="acrb-ledger-form-group acrb-ledger-form-group-full">
            <label class="acrb-ledger-label" for="entry_title"><?php esc_html_e( 'Transaction Description / Reference', 'awesome-car-rental' ); ?></label>
            <input type="text" id="entry_title" name="entry_title" value="<?php echo esc_attr( $e_desc ); ?>" class="acrb-ledger-input" placeholder="<?php esc_attr_e( 'e.g. Monthly Commercial Fleet Insurance Policy', 'awesome-car-rental' ); ?>" required>
        </div>

        <div class="acrb-ledger-grid-2">
            <!-- Category -->
            <div class="acrb-ledger-form-group">
                <label class="acrb-ledger-label" for="entry_category"><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></label>
                <div class="acrb-ledger-select-wrap">
                    <select id="entry_category" name="entry_category" class="acrb-ledger-select">
                        <option value="Maintenance" <?php selected( $e_cat, 'Maintenance' ); ?>><?php esc_html_e( 'Maintenance & Repairs', 'awesome-car-rental' ); ?></option>
                        <option value="Insurance" <?php selected( $e_cat, 'Insurance' ); ?>><?php esc_html_e( 'Insurance', 'awesome-car-rental' ); ?></option>
                        <option value="Fuel & Wash" <?php selected( $e_cat, 'Fuel & Wash' ); ?>><?php esc_html_e( 'Fuel & Detailing', 'awesome-car-rental' ); ?></option>
                        <option value="Lease / Loan" <?php selected( $e_cat, 'Lease / Loan' ); ?>><?php esc_html_e( 'Vehicle Lease / Loan', 'awesome-car-rental' ); ?></option>
                        <option value="Marketing" <?php selected( $e_cat, 'Marketing' ); ?>><?php esc_html_e( 'Marketing & Ads', 'awesome-car-rental' ); ?></option>
                        <option value="Salary" <?php selected( $e_cat, 'Salary' ); ?>><?php esc_html_e( 'Staff Salary / Wages', 'awesome-car-rental' ); ?></option>
                        <option value="Other" <?php selected( $e_cat, 'Other' ); ?>><?php esc_html_e( 'General / Other', 'awesome-car-rental' ); ?></option>
                    </select>
                    <span class="dashicons dashicons-arrow-down-alt2 acrb-ledger-select-arrow"></span>
                </div>
            </div>

            <!-- Transaction Date -->
            <div class="acrb-ledger-form-group">
                <label class="acrb-ledger-label" for="entry_date"><?php esc_html_e( 'Transaction Date', 'awesome-car-rental' ); ?></label>
                <input type="date" id="entry_date" name="entry_date" value="<?php echo esc_attr( $e_date ); ?>" class="acrb-ledger-input" required>
            </div>
        </div>

        <!-- Action Controls -->
        <div class="acrb-ledger-btn-actions">
            <button type="submit" name="acrb_save_ledger" class="acrb-ledger-btn-submit">
                <span class="dashicons <?php echo $entry_id > 0 ? 'dashicons-saved' : 'dashicons-plus-alt2'; ?>"></span>
                <span><?php echo $entry_id > 0 ? esc_html__( 'Update Ledger Entry', 'awesome-car-rental' ) : esc_html__( 'Save Ledger Entry', 'awesome-car-rental' ); ?></span>
            </button>
            <a href="<?php echo esc_url( $list_url ); ?>" class="acrb-ledger-btn-cancel">
                <?php esc_html_e( 'Cancel', 'awesome-car-rental' ); ?>
            </a>
        </div>
    </form>
</div>
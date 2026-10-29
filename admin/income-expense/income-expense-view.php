<?php
/**
 * Income & Expense CRM View Component (`income-expense-view.php`)
 * Location: admin/income-expense/income-expense-view.php
 *
 * @package Awesome_Car_Rental
 * @since   3.0.7
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
$currency_pos    = get_option( 'acrb_currency_pos', 'left' );
$entry_id        = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : ( isset( $_GET['entry_id'] ) ? absint( wp_unslash( $_GET['entry_id'] ) ) : 0 );
$page_slug       = 'awesome_car_rental';
$base_url        = admin_url( 'admin.php?page=' . $page_slug . '&tab=income_expense' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_finance} WHERE id = %d", $entry_id ), ARRAY_A );

if ( ! $entry ) {
    ?>
    <div class="acrb-ledger-notfound">
        <p><?php esc_html_e( 'Ledger transaction not found.', 'awesome-car-rental' ); ?></p>
        <a href="<?php echo esc_url( $base_url ); ?>" class="acrb-ledger-btn-back">
            &larr; <?php esc_html_e( 'Back to Ledger', 'awesome-car-rental' ); ?>
        </a>
    </div>
    <?php
    return;
}

$t_id       = absint( $entry['id'] );
$type       = sanitize_key( $entry['type'] ?? 'expense' );
$amount     = floatval( $entry['amount'] ?? 0 );
$cat        = esc_html( $entry['category'] ?? 'General' );
$desc       = esc_html( $entry['description'] ?? '—' );
$date       = ! empty( $entry['transaction_date'] ) ? esc_html( $entry['transaction_date'] ) : '—';
$logged     = ! empty( $entry['created_at'] ) ? date_i18n( 'M j, Y g:i A', strtotime( $entry['created_at'] ) ) : '—';
$edit_url   = add_query_arg( array( 'sub' => 'edit', 'entry_id' => $t_id ), $base_url );
$delete_url = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'entry_id' => $t_id ), $base_url ), 'acrb_del_ledger_' . $t_id );

$is_inc       = ( 'income' === $type );
$type_class   = $is_inc ? 'acrb-type-income' : 'acrb-type-expense';
$amt_class    = $is_inc ? 'acrb-amt-income' : 'acrb-amt-expense';

$formatted_val = number_format( $amount, 2 );
$display_amt   = ( 'left' === $currency_pos ) ? $currency_symbol . $formatted_val : $formatted_val . $currency_symbol;
$display_amt   = ( $is_inc ? '+' : '-' ) . $display_amt;
?>

<style>
    .acrb-ledger-view-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 20px;
        align-items: start;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .acrb-ledger-view-grid *,
    .acrb-ledger-view-grid *::before,
    .acrb-ledger-view-grid *::after {
        box-sizing: border-box;
    }

    /* Cards */
    .acrb-ledger-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
    }
    .acrb-ledger-dossier {
        text-align: center;
    }

    /* Avatar Icon Wrap */
    .acrb-ledger-icon-wrap {
        width: 72px;
        height: 72px;
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        color: #4338ca;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 14px auto;
        border: 2px solid #c7d2fe;
    }
    .acrb-ledger-icon-wrap .dashicons {
        font-size: 32px;
        width: 32px;
        height: 32px;
    }

    /* Headings & Badges */
    .acrb-ledger-view-title {
        margin: 0 0 6px;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
    }
    .acrb-ledger-type-badge {
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        display: inline-block;
        white-space: nowrap;
    }
    .acrb-type-income {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }
    .acrb-type-expense {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* Meta Rows */
    .acrb-ledger-meta-list {
        margin-top: 20px;
        text-align: left;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        font-size: 13px;
    }
    .acrb-ledger-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }
    .acrb-ledger-meta-label {
        color: #64748b;
        font-weight: 500;
        flex-shrink: 0;
    }
    .acrb-ledger-meta-val {
        color: #0f172a;
        font-weight: 700;
        text-align: right;
    }
    .acrb-amt-income {
        color: #059669 !important;
    }
    .acrb-amt-expense {
        color: #dc2626 !important;
    }

    /* Action Links */
    .acrb-ledger-view-actions {
        margin-top: 20px;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .acrb-ledger-btn-edit {
        background: #4f46e5;
        color: #ffffff;
        border: none;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        transition: background 0.15s ease, transform 0.15s ease;
    }
    .acrb-ledger-btn-edit:hover {
        background: #4338ca;
        color: #ffffff;
        transform: translateY(-1px);
    }
    .acrb-ledger-btn-edit .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    .acrb-ledger-btn-del {
        color: #dc2626;
        background: #fff5f5;
        border: 1px solid #fee2e2;
        padding: 8px 14px;
        font-size: 12.5px;
        font-weight: 700;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .acrb-ledger-btn-del:hover {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fca5a5;
    }
    .acrb-ledger-btn-del .dashicons {
        font-size: 15px;
        width: 15px;
        height: 15px;
    }
    .acrb-ledger-btn-back {
        color: #64748b;
        text-decoration: none;
        font-size: 12.5px;
        font-weight: 600;
        text-align: center;
        padding: 6px;
        transition: color 0.15s ease;
    }
    .acrb-ledger-btn-back:hover {
        color: #0f172a;
    }

    /* Details Panel */
    .acrb-ledger-section-title {
        margin: 0 0 16px;
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }
    .acrb-ledger-desc-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .acrb-ledger-desc-label {
        margin: 0 0 10px 0;
        font-size: 11.5px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .acrb-ledger-desc-content {
        margin: 0;
        font-size: 14px;
        color: #1e293b;
        line-height: 1.6;
        white-space: pre-line;
    }

    /* Context Telemetry Block */
    .acrb-ledger-context-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    .acrb-ledger-context-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
    }
    .acrb-ledger-context-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        display: block;
    }
    .acrb-ledger-context-val {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        margin-top: 4px;
    }
    .acrb-ledger-context-desc {
        font-size: 12px;
        color: #64748b;
        margin: 4px 0 0;
    }

    /* Not Found Alert */
    .acrb-ledger-notfound {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 16px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    @media (max-width: 900px) {
        .acrb-ledger-view-grid {
            grid-template-columns: 1fr;
        }
        .acrb-ledger-context-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="acrb-ledger-view-grid">
    <!-- Transaction Dossier Card -->
    <div class="acrb-ledger-card acrb-ledger-dossier">
        <div class="acrb-ledger-icon-wrap">
            <span class="dashicons dashicons-money-alt"></span>
        </div>
        <h3 class="acrb-ledger-view-title"><?php printf( esc_html__( 'Transaction #%d', 'awesome-car-rental' ), absint( $t_id ) ); ?></h3>
        <span class="acrb-ledger-type-badge <?php echo esc_attr( $type_class ); ?>">
            ● <?php echo esc_html( ucfirst( $type ) ); ?>
        </span>

        <div class="acrb-ledger-meta-list">
            <div class="acrb-ledger-meta-row">
                <span class="acrb-ledger-meta-label"><?php esc_html_e( 'Amount', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-ledger-meta-val <?php echo esc_attr( $amt_class ); ?>" style="font-size:15px;">
                    <?php echo esc_html( $display_amt ); ?>
                </strong>
            </div>
            <div class="acrb-ledger-meta-row">
                <span class="acrb-ledger-meta-label"><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-ledger-meta-val"><?php echo esc_html( $cat ); ?></strong>
            </div>
            <div class="acrb-ledger-meta-row">
                <span class="acrb-ledger-meta-label"><?php esc_html_e( 'Transaction Date', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-ledger-meta-val"><?php echo esc_html( $date ); ?></strong>
            </div>
            <div class="acrb-ledger-meta-row">
                <span class="acrb-ledger-meta-label"><?php esc_html_e( 'Logged On', 'awesome-car-rental' ); ?></span>
                <strong class="acrb-ledger-meta-val"><?php echo esc_html( $logged ); ?></strong>
            </div>
        </div>

        <div class="acrb-ledger-view-actions">
            <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-ledger-btn-edit">
                <span class="dashicons dashicons-edit"></span>
                <span><?php esc_html_e( 'Edit Transaction', 'awesome-car-rental' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $delete_url ); ?>" class="acrb-ledger-btn-del" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this ledger transaction?', 'awesome-car-rental' ); ?>');">
                <span class="dashicons dashicons-trash"></span>
                <span><?php esc_html_e( 'Delete Entry', 'awesome-car-rental' ); ?></span>
            </a>
            <a href="<?php echo esc_url( $base_url ); ?>" class="acrb-ledger-btn-back">
                &larr; <?php esc_html_e( 'Back to Ledger', 'awesome-car-rental' ); ?>
            </a>
        </div>
    </div>

    <!-- Details & Context Panel -->
    <div class="acrb-ledger-card">
        <h2 class="acrb-ledger-section-title"><?php esc_html_e( 'Transaction Details', 'awesome-car-rental' ); ?></h2>

        <div class="acrb-ledger-desc-box">
            <h4 class="acrb-ledger-desc-label"><?php esc_html_e( 'Description / Reference', 'awesome-car-rental' ); ?></h4>
            <p class="acrb-ledger-desc-content"><?php echo esc_html( $desc ); ?></p>
        </div>

        <div class="acrb-ledger-context-grid">
            <div class="acrb-ledger-context-card">
                <span class="acrb-ledger-context-label"><?php esc_html_e( 'Financial Impact', 'awesome-car-rental' ); ?></span>
                <div class="acrb-ledger-context-val <?php echo esc_attr( $amt_class ); ?>">
                    <?php echo $is_inc ? esc_html__( 'Cash Inflow (+)', 'awesome-car-rental' ) : esc_html__( 'Cash Outflow (-)', 'awesome-car-rental' ); ?>
                </div>
                <p class="acrb-ledger-context-desc">
                    <?php echo $is_inc ? esc_html__( 'Recorded as non-booking revenue or manual capital input.', 'awesome-car-rental' ) : esc_html__( 'Deducted directly from overall fleet net operating profitability.', 'awesome-car-rental' ); ?>
                </p>
            </div>

            <div class="acrb-ledger-context-card">
                <span class="acrb-ledger-context-label"><?php esc_html_e( 'Audit Ledger ID', 'awesome-car-rental' ); ?></span>
                <div class="acrb-ledger-context-val" style="font-family:ui-monospace,monospace;">
                    #<?php echo absint( $t_id ); ?>
                </div>
                <p class="acrb-ledger-context-desc">
                    <?php printf( esc_html__( 'Categorized under %s classification.', 'awesome-car-rental' ), esc_html( $cat ) ); ?>
                </p>
            </div>
        </div>
    </div>
</div>
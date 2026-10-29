<?php
/**
 * Income & Expense List Component (`income-expense-list.php`)
 * Location: admin/income-expense/income-expense-list.php
 *
 * @package Awesome_Car_Rental
 * @since   3.0.6
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
$table_bookings  = $wpdb->prefix . 'dev_nahian_bookings';
$currency_symbol = get_option( 'acrb_currency', '$' );
$currency_pos    = get_option( 'acrb_currency_pos', 'left' );
$page_slug       = 'awesome_car_rental';
$base_url        = admin_url( 'admin.php?page=' . $page_slug . '&tab=income_expense' );

// 1. Calculate Aggregated Financial KPIs
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_manual_expense = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_finance} WHERE type = 'expense'" );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_manual_income  = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_finance} WHERE type = 'income'" );

// Fetch Confirmed / Active / Completed Rental Bookings Revenue
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking_revenue = (float) $wpdb->get_var( "SELECT SUM(total_amount) FROM {$table_bookings} WHERE booking_status IN ('confirmed', 'active', 'completed')" );

$grand_total_income = $total_manual_income + $booking_revenue;
$net_profit         = $grand_total_income - $total_manual_expense;
$is_profitable      = ( $net_profit >= 0 );

// Currency format helper
$format_money = static function( float $val ) use ( $currency_symbol, $currency_pos ): string {
    $formatted = number_format( abs( $val ), 2 );
    return ( 'left' === $currency_pos ) ? $currency_symbol . $formatted : $formatted . $currency_symbol;
};

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$entries = $wpdb->get_results( "SELECT * FROM {$table_finance} ORDER BY transaction_date DESC, id DESC", ARRAY_A );
?>

<style>
    /* Metric KPI Cards Grid */
    .acrb-ledger-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 18px;
        margin-bottom: 22px;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .acrb-ledger-kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px 22px;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 8px;
    }
    .acrb-ledger-kpi-label {
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .acrb-ledger-kpi-label .dashicons {
        font-size: 15px;
        width: 15px;
        height: 15px;
    }
    .acrb-ledger-kpi-val {
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .acrb-kpi-revenue {
        color: #059669;
    }
    .acrb-kpi-expense {
        color: #dc2626;
    }
    .acrb-kpi-profit-pos {
        color: #059669;
    }
    .acrb-kpi-profit-neg {
        color: #dc2626;
    }
    .acrb-ledger-kpi-sub {
        font-size: 12px;
        color: #94a3b8;
    }

    /* Table Container */
    .acrb-table-wrap {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        box-sizing: border-box;
    }
    .acrb-ledger-table {
        width: 100% !important;
        border-collapse: collapse;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    .acrb-ledger-table thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 13px 16px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    .acrb-ledger-table tbody td {
        vertical-align: middle;
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 13px;
    }
    .acrb-ledger-table tbody tr:hover {
        background-color: #fcfdfe;
    }

    /* Cell Elements */
    .acrb-ledger-id-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        color: #475569;
        background: #f1f5f9;
        padding: 2px 7px;
        border-radius: 5px;
        font-size: 11.5px;
        border: 1px solid #e2e8f0;
    }
    .acrb-ledger-desc {
        color: #0f172a;
        font-size: 13.5px;
        font-weight: 700;
        display: block;
    }
    .acrb-ledger-category-badge {
        color: #475569;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 3px 9px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }

    /* Type Badges */
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

    /* Amount Values */
    .acrb-ledger-amt {
        font-size: 14px;
        font-weight: 800;
        white-space: nowrap;
        letter-spacing: -0.01em;
    }
    .acrb-amt-income {
        color: #059669;
    }
    .acrb-amt-expense {
        color: #dc2626;
    }
    .acrb-ledger-date {
        color: #64748b;
        font-size: 12.5px;
        white-space: nowrap;
    }

    /* Action Buttons */
    .acrb-ledger-th-actions {
        text-align: right !important;
        width: 120px;
    }
    .acrb-ledger-cell-actions {
        text-align: right !important;
    }
    .acrb-ledger-actions-group {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
    }
    .acrb-btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 7px;
        color: #475569;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }
    .acrb-btn-action:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    .acrb-btn-action .dashicons {
        font-size: 15px;
        width: 15px;
        height: 15px;
    }
    .acrb-btn-delete {
        color: #dc2626;
        border-color: #fee2e2;
        background: #fff5f5;
    }
    .acrb-btn-delete:hover {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fca5a5;
    }

    /* Empty State */
    .acrb-ledger-empty {
        text-align: center;
        padding: 48px 20px;
        color: #64748b;
        font-size: 13.5px;
    }
</style>

<!-- Financial KPI Summary Cards -->
<div class="acrb-ledger-kpi-grid">
    <div class="acrb-ledger-kpi-card">
        <span class="acrb-ledger-kpi-label">
            <span class="dashicons dashicons-chart-line"></span>
            <?php esc_html_e( 'Total Revenue (Rentals + Manual)', 'awesome-car-rental' ); ?>
        </span>
        <span class="acrb-ledger-kpi-val acrb-kpi-revenue">
            <?php echo esc_html( $format_money( $grand_total_income ) ); ?>
        </span>
        <span class="acrb-ledger-kpi-sub">
            <?php printf( esc_html__( 'Rentals: %s | Manual: %s', 'awesome-car-rental' ), esc_html( $format_money( $booking_revenue ) ), esc_html( $format_money( $total_manual_income ) ) ); ?>
        </span>
    </div>

    <div class="acrb-ledger-kpi-card">
        <span class="acrb-ledger-kpi-label">
            <span class="dashicons dashicons-arrow-down-alt"></span>
            <?php esc_html_e( 'Total Expenditures', 'awesome-car-rental' ); ?>
        </span>
        <span class="acrb-ledger-kpi-val acrb-kpi-expense">
            <?php echo esc_html( $format_money( $total_manual_expense ) ); ?>
        </span>
        <span class="acrb-ledger-kpi-sub">
            <?php esc_html_e( 'Fleet maintenance, fuel, insurance & ops', 'awesome-car-rental' ); ?>
        </span>
    </div>

    <div class="acrb-ledger-kpi-card">
        <span class="acrb-ledger-kpi-label">
            <span class="dashicons dashicons-money-alt"></span>
            <?php esc_html_e( 'Net Profitability', 'awesome-car-rental' ); ?>
        </span>
        <span class="acrb-ledger-kpi-val <?php echo $is_profitable ? 'acrb-kpi-profit-pos' : 'acrb-kpi-profit-neg'; ?>">
            <?php echo esc_html( ( $net_profit < 0 ? '-' : '' ) . $format_money( $net_profit ) ); ?>
        </span>
        <span class="acrb-ledger-kpi-sub">
            <?php echo $is_profitable ? esc_html__( 'Operating with positive net cash flow', 'awesome-car-rental' ) : esc_html__( 'Operating with net cash deficit', 'awesome-car-rental' ); ?>
        </span>
    </div>
</div>

<!-- Ledger Transactions Table -->
<div class="acrb-table-wrap">
    <table class="acrb-datatable acrb-ledger-table widefat striped display nowrap">
        <thead>
            <tr>
                <th style="width: 70px;"><?php esc_html_e( 'ID', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Description / Reference', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Type', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Amount', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></th>
                <th class="all acrb-ledger-th-actions" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ( ! empty( $entries ) ) :
                foreach ( $entries as $en ) :
                    $t_id   = absint( $en['id'] );
                    $type   = sanitize_key( $en['type'] ?? 'expense' );
                    $amount = floatval( $en['amount'] ?? 0 );
                    $cat    = esc_html( $en['category'] ?? 'General' );
                    $desc   = esc_html( $en['description'] ?? '—' );
                    $date   = esc_html( $en['transaction_date'] ?? '—' );

                    $is_inc       = ( 'income' === $type );
                    $type_class   = $is_inc ? 'acrb-type-income' : 'acrb-type-expense';
                    $amt_class    = $is_inc ? 'acrb-amt-income' : 'acrb-amt-expense';
                    $order_val    = $is_inc ? $amount : -$amount;
                    $display_amt  = ( $is_inc ? '+' : '-' ) . $format_money( $amount );

                    $view_url = add_query_arg( array( 'view' => $t_id ), $base_url );
                    $edit_url = add_query_arg( array( 'sub' => 'edit', 'entry_id' => $t_id ), $base_url );
                    $del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'entry_id' => $t_id ), $base_url ), 'acrb_del_ledger_' . $t_id );
            ?>
            <tr>
                <td>
                    <code class="acrb-ledger-id-badge">#<?php echo absint( $t_id ); ?></code>
                </td>
                <td>
                    <strong class="acrb-ledger-desc"><?php echo esc_html( $desc ); ?></strong>
                </td>
                <td>
                    <span class="acrb-ledger-type-badge <?php echo esc_attr( $type_class ); ?>">
                        ● <?php echo esc_html( ucfirst( $type ) ); ?>
                    </span>
                </td>
                <td>
                    <span class="acrb-ledger-category-badge"><?php echo esc_html( $cat ); ?></span>
                </td>
                <td data-order="<?php echo esc_attr( (string) $order_val ); ?>">
                    <strong class="acrb-ledger-amt <?php echo esc_attr( $amt_class ); ?>">
                        <?php echo esc_html( $display_amt ); ?>
                    </strong>
                </td>
                <td data-order="<?php echo esc_attr( $date ); ?>" class="acrb-ledger-date">
                    <?php echo esc_html( $date ); ?>
                </td>
                <td class="acrb-ledger-cell-actions">
                    <div class="acrb-ledger-actions-group">
                        <a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit Entry', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Permanently delete this ledger transaction?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else : ?>
            <tr>
                <td colspan="7" class="acrb-ledger-empty">
                    <?php esc_html_e( 'No financial transactions logged in the database.', 'awesome-car-rental' ); ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
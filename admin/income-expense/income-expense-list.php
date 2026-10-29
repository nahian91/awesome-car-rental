<?php
/**
 * Income & Expense List Component (`income-expense-list.php`)
 * Location: admin/income-expense/income-expense-list.php
 *
 * @package Awesome_Car_Rental
 * @since   1.7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_finance  = $wpdb->prefix . 'dev_nahian_income_expense';
$table_bookings = $wpdb->prefix . 'dev_nahian_bookings';
$currency       = get_option( 'acrb_currency', '£' );
$page_slug      = 'awesome_car_rental';
$base_url       = admin_url( 'admin.php?page=' . $page_slug . '&tab=income_expense' );

// 1. CALCULATION STATS
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_manual_expense = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_finance} WHERE type = 'expense'" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$total_manual_income  = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_finance} WHERE type = 'income'" );

// Fetch Confirmed Booking Revenues
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking_revenue = (float) $wpdb->get_var( "SELECT SUM(total_amount) FROM {$table_bookings} WHERE booking_status IN ('confirmed', 'active', 'completed')" );

$grand_total_income = $total_manual_income + $booking_revenue;
$net_profit         = $grand_total_income - $total_manual_expense;
$profit_color       = ( $net_profit >= 0 ) ? '#059669' : '#b91c1c';
?>

<!-- Deletion Success Notice -->
<?php if ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) : ?>
    <div class="notice notice-warning is-dismissible" style="margin: 0 0 20px 0;">
        <p><strong><?php esc_html_e( 'Notice:', 'awesome-car-rental' ); ?></strong> <?php esc_html_e( 'Ledger transaction deleted successfully.', 'awesome-car-rental' ); ?></p>
    </div>
<?php endif; ?>

<!-- FINANCIAL SUMMARY CARDS -->
<div class="acrb-stats-grid">
    <div class="acrb-stat-card">
        <span class="acrb-stat-label"><?php esc_html_e( 'Total Revenue (Rentals + Manual)', 'awesome-car-rental' ); ?></span>
        <span class="acrb-stat-val" style="color:#059669;"><?php echo esc_html( $currency . number_format( $grand_total_income, 2 ) ); ?></span>
    </div>
    <div class="acrb-stat-card">
        <span class="acrb-stat-label"><?php esc_html_e( 'Total Expenses', 'awesome-car-rental' ); ?></span>
        <span class="acrb-stat-val" style="color:#b91c1c;"><?php echo esc_html( $currency . number_format( $total_manual_expense, 2 ) ); ?></span>
    </div>
    <div class="acrb-stat-card">
        <span class="acrb-stat-label"><?php esc_html_e( 'Net Profitability', 'awesome-car-rental' ); ?></span>
        <span class="acrb-stat-val" style="color:<?php echo esc_attr( $profit_color ); ?>;"><?php echo esc_html( $currency . number_format( $net_profit, 2 ) ); ?></span>
    </div>
</div>

<div class="acrb-table-wrap">
    <table class="acrb-datatable widefat striped display nowrap" style="width:100%;">
        <thead>
            <tr>
                <th width="70"><?php esc_html_e( 'ID', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Description', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Type', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Category', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Amount', 'awesome-car-rental' ); ?></th>
                <th><?php esc_html_e( 'Date', 'awesome-car-rental' ); ?></th>
                <th class="all" style="text-align:right; width:110px;" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $entries = $wpdb->get_results( "SELECT * FROM {$table_finance} ORDER BY transaction_date DESC, id DESC", ARRAY_A );

            if ( ! empty( $entries ) ) :
                foreach ( $entries as $en ) :
                    $t_id   = absint( $en['id'] );
                    $type   = sanitize_key( $en['type'] ?? 'expense' );
                    $amount = floatval( $en['amount'] ?? 0 );
                    $cat    = esc_html( $en['category'] ?? 'General' );
                    $desc   = esc_html( $en['description'] ?? '—' );
                    $date   = esc_html( $en['transaction_date'] ?? '—' );
                    
                    $view_url = add_query_arg( array( 'view' => $t_id ), $base_url );
                    $edit_url = add_query_arg( array( 'sub' => 'edit', 'entry_id' => $t_id ), $base_url );
                    $del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'entry_id' => $t_id ), $base_url ), 'acrb_del_ledger_' . $t_id );
            ?>
            <tr>
                <td><code style="font-family:monospace; font-weight:700; color:#475569; background:#f1f5f9; padding:2px 6px; border-radius:4px;">#<?php echo absint( $t_id ); ?></code></td>
                <td><strong><?php echo esc_html( $desc ); ?></strong></td>
                <td>
                    <?php 
                    $badge_bg    = ( 'income' === $type ) ? '#ecfdf5' : '#fee2e2'; 
                    $badge_color = ( 'income' === $type ) ? '#059669' : '#b91c1c';
                    ?>
                    <span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding:3px 9px; border-radius:9999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; display:inline-block; white-space:nowrap;">
                        ● <?php echo esc_html( $type ); ?>
                    </span>
                </td>
                <td><?php echo esc_html( $cat ); ?></td>
                <td data-order="<?php echo esc_attr( 'income' === $type ? (string) $amount : (string) ( -$amount ) ); ?>">
                    <strong style="color:<?php echo 'income' === $type ? '#059669' : '#b91c1c'; ?>; white-space:nowrap; font-size:13.5px;">
                        <?php echo esc_html( ( 'income' === $type ? '+' : '-' ) . $currency . number_format( $amount, 2 ) ); ?>
                    </strong>
                </td>
                <td data-order="<?php echo esc_attr( $date ); ?>"><?php echo esc_html( $date ); ?></td>
                <td style="text-align:right;">
                    <div class="acrb-actions-group">
                        <a href="<?php echo esc_url( $view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                        <a href="<?php echo esc_url( $edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="<?php echo esc_url( $del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this ledger entry?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>" style="color:#b91c1c;">
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else : ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:40px; color:#64748b;">
                    <?php esc_html_e( 'No financial transactions logged in database.', 'awesome-car-rental' ); ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
/**
 * Income & Expense Sub-Navigation Tab Strip (`income-expense-tab.php`)
 * Location: admin/income-expense/income-expense-tab.php
 *
 * @package Awesome_Car_Rental
 * @since   3.0.3
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_slug = 'awesome_car_rental';
$sub_state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';
$view_id   = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0;
$edit_id   = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;

if ( $view_id > 0 ) {
    $sub_state = 'view';
} elseif ( $edit_id > 0 ) {
    $sub_state = 'edit';
}

$tabs = array(
    'list' => array(
        'label' => __( 'All Transactions', 'awesome-car-rental' ),
        'icon'  => 'dashicons-money-alt',
    ),
    'add'  => array(
        'label' => __( 'Record Transaction', 'awesome-car-rental' ),
        'icon'  => 'dashicons-plus-alt2',
    ),
);
?>

<style>
    .acrb-ledger-nav-wrapper {
        display: flex;
        gap: 8px;
        margin-bottom: 22px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 16px;
        flex-wrap: wrap;
        box-sizing: border-box;
    }
    .acrb-ledger-nav-item {
        background: #ffffff;
        color: #475569;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: 9px 16px;
        font-weight: 600;
        font-size: 13px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.02);
    }
    .acrb-ledger-nav-item .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
        color: #64748b;
        transition: color 0.15s ease;
    }
    .acrb-ledger-nav-item:hover {
        background: #f8fafc;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .acrb-ledger-nav-item:hover .dashicons {
        color: #0f172a;
    }
    .acrb-ledger-nav-item.is-active {
        background: #eff6ff;
        color: #4f46e5;
        border-color: #c7d2fe;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.12);
    }
    .acrb-ledger-nav-item.is-active .dashicons {
        color: #4f46e5;
    }
    .acrb-ledger-nav-label {
        line-height: 1;
    }
</style>

<nav class="acrb-ledger-nav-wrapper">
    <?php foreach ( $tabs as $k => $data ) :
        $url          = add_query_arg( array( 'page' => $page_slug, 'tab' => 'income_expense', 'sub' => $k ), admin_url( 'admin.php' ) );
        $is_active    = ( 'list' === $k && in_array( $sub_state, array( 'list', 'view', 'edit' ), true ) ) || ( $sub_state === $k );
        $active_class = $is_active ? ' is-active' : '';
    ?>
        <a href="<?php echo esc_url( $url ); ?>" class="acrb-ledger-nav-item<?php echo esc_attr( $active_class ); ?>">
            <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>"></span>
            <span class="acrb-ledger-nav-label"><?php echo esc_html( $data['label'] ); ?></span>
        </a>
    <?php endforeach; ?>
</nav>
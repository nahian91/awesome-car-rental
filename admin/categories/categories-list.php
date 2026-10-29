<?php
/**
 * Categories List Component (`categories-list.php`)
 * Location: admin/categories/categories-list.php
 *
 * @package Awesome_Car_Rental
 * @since   2.4.6
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$acrb_table_categories = $wpdb->prefix . 'dev_nahian_categories';
$acrb_table_cars       = $wpdb->prefix . 'dev_nahian_cars';
$acrb_base_url         = admin_url( 'admin.php?page=awesome_car_rental&tab=categories' );
?>

<style>
	.acrb-table-wrap {
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		overflow: hidden;
		box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
		margin-top: 16px;
	}
	.acrb-cat-table {
		width: 100%;
		border-collapse: collapse;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	}
	.acrb-cat-th-icon {
		width: 60px;
	}
	.acrb-cat-th-actions {
		text-align: right;
		width: 130px;
	}
	.acrb-cat-thumb {
		width: 40px;
		height: 40px;
		border-radius: 8px;
		object-fit: cover;
		border: 1px solid #e2e8f0;
		display: block;
	}
	.acrb-cat-thumb-placeholder {
		width: 40px;
		height: 40px;
		border-radius: 8px;
		background: #f1f5f9;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #64748b;
	}
	.acrb-cat-thumb-placeholder .dashicons {
		font-size: 18px;
		width: 18px;
		height: 18px;
	}
	.acrb-cat-title {
		color: #0f172a;
		font-size: 14px;
		font-weight: 700;
	}
	.acrb-cat-slug {
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
		color: #64748b;
		background: #f1f5f9;
		padding: 3px 8px;
		border-radius: 6px;
		font-size: 12px;
	}
	.acrb-cat-badge {
		display: inline-block;
		background: #eff6ff;
		color: #3b82f6;
		padding: 4px 10px;
		border-radius: 9999px;
		font-size: 11.5px;
		font-weight: 700;
	}
	.acrb-cat-cell-actions {
		text-align: right;
	}
	.acrb-actions-group {
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
		border-radius: 6px;
		color: #475569;
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		text-decoration: none;
		transition: all 0.15s ease;
	}
	.acrb-btn-action:hover {
		background: #f1f5f9;
		color: #0f172a;
		border-color: #cbd5e1;
	}
	.acrb-btn-action .dashicons {
		font-size: 16px;
		width: 16px;
		height: 16px;
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
	.acrb-cat-empty {
		text-align: center;
		padding: 48px 20px;
		color: #64748b;
		font-size: 14px;
	}
</style>

<div class="acrb-table-wrap">
	<table class="acrb-datatable acrb-cat-table widefat striped display nowrap">
		<thead>
			<tr>
				<th class="acrb-cat-th-icon" data-orderable="false"><?php esc_html_e( 'Icon', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Category Name', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Slug', 'awesome-car-rental' ); ?></th>
				<th><?php esc_html_e( 'Fleet Count', 'awesome-car-rental' ); ?></th>
				<th class="all acrb-cat-th-actions" data-orderable="false"><?php esc_html_e( 'Actions', 'awesome-car-rental' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$acrb_categories = $wpdb->get_results( "SELECT * FROM {$acrb_table_categories} ORDER BY id DESC", ARRAY_A );

			if ( ! empty( $acrb_categories ) ) :
				foreach ( $acrb_categories as $acrb_cat ) :
					$acrb_c_id   = absint( $acrb_cat['id'] );
					$acrb_c_name = esc_html( $acrb_cat['name'] );
					$acrb_c_slug = esc_html( $acrb_cat['slug'] );
					$acrb_c_img  = esc_url( $acrb_cat['image'] ?? '' );
					
					// Count cars belonging to this category.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$acrb_car_count = (int) $wpdb->get_var(
						$wpdb->prepare(
							// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							"SELECT COUNT(*) FROM {$acrb_table_cars} WHERE category = %s",
							$acrb_c_name
						)
					);

					$acrb_view_url = add_query_arg( array( 'sub' => 'view', 'cat_id' => $acrb_c_id ), $acrb_base_url );
					$acrb_edit_url = add_query_arg( array( 'sub' => 'edit', 'cat_id' => $acrb_c_id ), $acrb_base_url );
					$acrb_del_url  = wp_nonce_url( add_query_arg( array( 'sub' => 'delete', 'cat_id' => $acrb_c_id ), $acrb_base_url ), 'acrb_del_cat_' . $acrb_c_id );
			?>
			<tr>
				<td>
					<?php if ( ! empty( $acrb_c_img ) ) : ?>
						<img src="<?php echo esc_url( $acrb_c_img ); ?>" alt="<?php echo esc_attr( $acrb_c_name ); ?>" class="acrb-cat-thumb">
					<?php else : ?>
						<div class="acrb-cat-thumb-placeholder">
							<span class="dashicons dashicons-category"></span>
						</div>
					<?php endif; ?>
				</td>
				<td><strong class="acrb-cat-title"><?php echo esc_html( $acrb_c_name ); ?></strong></td>
				<td><code class="acrb-cat-slug"><?php echo esc_html( $acrb_c_slug ); ?></code></td>
				<td>
					<span class="acrb-cat-badge">
						<?php echo esc_html( (string) $acrb_car_count ); ?> <?php esc_html_e( 'Vehicles', 'awesome-car-rental' ); ?>
					</span>
				</td>
				<td class="acrb-cat-cell-actions">
					<div class="acrb-actions-group">
						<a href="<?php echo esc_url( $acrb_view_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'View Details', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</a>
						<a href="<?php echo esc_url( $acrb_edit_url ); ?>" class="acrb-btn-action" title="<?php esc_attr_e( 'Edit', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-edit"></span>
						</a>
						<a href="<?php echo esc_url( $acrb_del_url ); ?>" class="acrb-btn-action acrb-btn-delete" onclick="return confirm('<?php esc_attr_e( 'Delete this vehicle category?', 'awesome-car-rental' ); ?>');" title="<?php esc_attr_e( 'Delete', 'awesome-car-rental' ); ?>">
							<span class="dashicons dashicons-trash"></span>
						</a>
					</div>
				</td>
			</tr>
			<?php endforeach; else : ?>
			<tr>
				<td colspan="5" class="acrb-cat-empty">
					<?php esc_html_e( 'No categories found in database. Click "Add Category" above to create one.', 'awesome-car-rental' ); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
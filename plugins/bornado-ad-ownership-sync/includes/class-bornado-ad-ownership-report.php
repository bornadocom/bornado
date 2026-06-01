<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bornado_Ad_Ownership_Report {

	/**
	 * Bootstrap the lightweight admin report.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
	}

	/**
	 * Register a compact report page under Tools.
	 *
	 * @return void
	 */
	public static function register_admin_page() {
		add_management_page(
			'گزارش انتقال آگهی',
			'گزارش انتقال آگهی',
			'manage_options',
			'bornado-ad-ownership-report',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Render the admin report page.
	 *
	 * @return void
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.' ) );
		}

		$rows = self::build_rows();
		?>
		<div class="wrap bornado-ad-ownership-report" dir="rtl">
			<h1>گزارش انتقال مالکیت آگهی</h1>
			<p>این گزارش فقط انتقال‌های واقعی ثبت‌شده را نشان می‌دهد و روی همان لاگ‌های سبک سیستم فعلی ساخته شده است.</p>

			<?php if ( empty( $rows ) ) : ?>
				<div class="notice notice-info"><p>هنوز هیچ انتقال موفقی ثبت نشده است.</p></div>
			<?php else : ?>
				<div class="bornado-ad-ownership-report__meta">
					<span>تعداد حساب‌ها: <strong><?php echo esc_html( number_format_i18n( count( $rows ) ) ); ?></strong></span>
					<span>تعداد کل آگهی‌های منتقل‌شده: <strong><?php echo esc_html( number_format_i18n( self::count_total_transferred( $rows ) ) ); ?></strong></span>
				</div>

				<div class="bornado-ad-ownership-report__list">
					<?php foreach ( $rows as $row ) : ?>
						<div class="bornado-ad-ownership-report__card">
							<div class="bornado-ad-ownership-report__header">
								<div>
									<h2>
										<?php if ( ! empty( $row['user_edit_url'] ) ) : ?>
											<a href="<?php echo esc_url( $row['user_edit_url'] ); ?>"><?php echo esc_html( $row['user_label'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $row['user_label'] ); ?>
										<?php endif; ?>
									</h2>
									<div class="bornado-ad-ownership-report__sub">
										<span>شماره: <?php echo esc_html( $row['phone'] ); ?></span>
										<span>آخرین انتقال: <?php echo esc_html( $row['last_transfer_label'] ); ?></span>
									</div>
								</div>
								<details class="bornado-ad-ownership-report__details">
									<summary>
										<?php echo esc_html( sprintf( '%s آگهی منتقل شده', number_format_i18n( $row['transferred_count'] ) ) ); ?>
									</summary>
									<ul>
										<?php foreach ( $row['ads'] as $ad ) : ?>
											<li>
												<a href="<?php echo esc_url( $ad['permalink'] ); ?>" target="_blank" rel="noopener noreferrer">
													<?php echo esc_html( $ad['title'] ); ?>
												</a>
												<div class="bornado-ad-ownership-report__ad-meta">
													از:
													<?php if ( ! empty( $ad['from_user_edit_url'] ) ) : ?>
														<a href="<?php echo esc_url( $ad['from_user_edit_url'] ); ?>"><?php echo esc_html( $ad['from_user_label'] ); ?></a>
													<?php else : ?>
														<?php echo esc_html( $ad['from_user_label'] ); ?>
													<?php endif; ?>
												</div>
												<?php if ( ! empty( $ad['edit_url'] ) ) : ?>
													<span class="sep">|</span>
													<a href="<?php echo esc_url( $ad['edit_url'] ); ?>">ویرایش</a>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</details>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<style>
			.bornado-ad-ownership-report__meta{display:flex;gap:18px;flex-wrap:wrap;margin:18px 0 22px}
			.bornado-ad-ownership-report__list{display:grid;gap:14px}
			.bornado-ad-ownership-report__card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px 18px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
			.bornado-ad-ownership-report__header{display:flex;justify-content:space-between;gap:18px;align-items:flex-start}
			.bornado-ad-ownership-report__header h2{margin:0 0 8px;font-size:16px;line-height:1.6}
			.bornado-ad-ownership-report__sub{display:flex;gap:14px;flex-wrap:wrap;color:#50575e}
			.bornado-ad-ownership-report__details{min-width:220px}
			.bornado-ad-ownership-report__details summary{cursor:pointer;font-weight:600}
			.bornado-ad-ownership-report__details ul{margin:12px 0 0;padding:0 18px 0 0}
			.bornado-ad-ownership-report__details li{margin:0 0 8px;line-height:1.8}
			.bornado-ad-ownership-report__ad-meta{font-size:12px;color:#50575e}
			.bornado-ad-ownership-report__details .sep{margin:0 6px;color:#8c8f94}
			@media (max-width: 782px){
				.bornado-ad-ownership-report__header{flex-direction:column}
				.bornado-ad-ownership-report__details{min-width:0;width:100%}
			}
		</style>
		<?php
	}

	/**
	 * Build the grouped report rows from existing audit logs.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function build_rows() {
		$log_ids = get_posts(
			array(
				'post_type'      => Bornado_Ad_Ownership_Transfer_Service::LOG_POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 300,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( empty( $log_ids ) ) {
			return array();
		}

		$grouped = array();

		foreach ( $log_ids as $log_id ) {
			$summary = get_post_meta( $log_id, 'bornado_transfer_summary', true );
			if ( ! is_array( $summary ) ) {
				continue;
			}

			$transferred_ids    = ! empty( $summary['transferred_ids'] ) && is_array( $summary['transferred_ids'] )
				? array_map( 'absint', $summary['transferred_ids'] )
				: array();
			$transferred_items  = ! empty( $summary['transferred_items'] ) && is_array( $summary['transferred_items'] )
				? $summary['transferred_items']
				: array();
			$user_id            = ! empty( $summary['user_id'] ) ? absint( $summary['user_id'] ) : 0;

			if ( $user_id <= 0 || empty( $transferred_ids ) ) {
				continue;
			}

			if ( ! isset( $grouped[ $user_id ] ) ) {
				$user = get_userdata( $user_id );
				$grouped[ $user_id ] = array(
					'user_id'             => $user_id,
					'user_label'          => $user instanceof WP_User ? $user->display_name : sprintf( 'کاربر #%s', $user_id ),
					'user_edit_url'       => get_edit_user_link( $user_id ),
					'phone'               => ! empty( $summary['canonical_phone'] ) ? (string) $summary['canonical_phone'] : '',
					'transferred_count'   => 0,
					'last_transfer_ts'    => 0,
					'last_transfer_label' => '',
					'ads'                 => array(),
				);
			}

			$created_gmt = get_post_time( 'U', true, $log_id );
			if ( $created_gmt > (int) $grouped[ $user_id ]['last_transfer_ts'] ) {
				$grouped[ $user_id ]['last_transfer_ts']    = $created_gmt;
				$grouped[ $user_id ]['last_transfer_label'] = $created_gmt > 0 ? wp_date( 'Y/m/d H:i', $created_gmt ) : '';
			}

			foreach ( $transferred_ids as $ad_id ) {
				if ( $ad_id <= 0 || isset( $grouped[ $user_id ]['ads'][ $ad_id ] ) ) {
					continue;
				}

				$item = self::find_transferred_item( $transferred_items, $ad_id );
				$title = get_the_title( $ad_id );
				if ( '' === trim( (string) $title ) ) {
					$title = sprintf( 'آگهی #%s', $ad_id );
				}

				$from_user_id    = ! empty( $item['from_user_id'] ) ? absint( $item['from_user_id'] ) : 0;
				$from_user_label = ! empty( $item['from_user_name'] )
					? (string) $item['from_user_name']
					: 'نامشخص';

				$grouped[ $user_id ]['ads'][ $ad_id ] = array(
					'id'                 => $ad_id,
					'title'              => $title,
					'permalink'          => get_permalink( $ad_id ),
					'edit_url'           => get_edit_post_link( $ad_id, '' ),
					'from_user_id'       => $from_user_id,
					'from_user_label'    => $from_user_label,
					'from_user_edit_url' => $from_user_id > 0 ? get_edit_user_link( $from_user_id ) : '',
				);
			}
		}

		if ( empty( $grouped ) ) {
			return array();
		}

		foreach ( $grouped as $user_id => $row ) {
			$grouped[ $user_id ]['transferred_count'] = count( $row['ads'] );
			$grouped[ $user_id ]['ads']               = array_values( $row['ads'] );
		}

		usort(
			$grouped,
			static function ( $left, $right ) {
				$left_count  = isset( $left['transferred_count'] ) ? (int) $left['transferred_count'] : 0;
				$right_count = isset( $right['transferred_count'] ) ? (int) $right['transferred_count'] : 0;

				if ( $left_count === $right_count ) {
					$left_ts  = isset( $left['last_transfer_ts'] ) ? (int) $left['last_transfer_ts'] : 0;
					$right_ts = isset( $right['last_transfer_ts'] ) ? (int) $right['last_transfer_ts'] : 0;
					return $right_ts <=> $left_ts;
				}

				return $right_count <=> $left_count;
			}
		);

		return $grouped;
	}

	/**
	 * Count the total transferred ads across visible rows.
	 *
	 * @param array<int,array<string,mixed>> $rows Grouped rows.
	 * @return int
	 */
	private static function count_total_transferred( array $rows ) {
		$total = 0;

		foreach ( $rows as $row ) {
			$total += isset( $row['transferred_count'] ) ? (int) $row['transferred_count'] : 0;
		}

		return $total;
	}

	/**
	 * Find one transferred item payload by post ID.
	 *
	 * @param array<int,array<string,mixed>> $items Logged transfer items.
	 * @param int $post_id Listing ID.
	 * @return array<string,mixed>
	 */
	private static function find_transferred_item( array $items, $post_id ) {
		$post_id = absint( $post_id );

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( $post_id === absint( $item['post_id'] ?? 0 ) ) {
				return $item;
			}
		}

		return array();
	}
}

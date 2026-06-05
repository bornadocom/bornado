<?php
/**
 * HTML renderer for the Bornado location picker.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Bornado_Location_Picker_Renderer' ) ) {
	return;
}

final class Bornado_Location_Picker_Renderer {
	/**
	 * Render a picker instance.
	 *
	 * @param array<string,mixed> $args Render options.
	 * @return string
	 */
	public static function render( $args = array() ) {
		try {
			$view = Bornado_Location_Picker_Service::get_component_data( is_array( $args ) ? $args : array() );
		} catch ( Throwable $exception ) {
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'Bornado Location Picker render failed: ' . $exception->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return '';
		}

		if ( ! is_array( $view ) ) {
			return '';
		}

		$classes = array(
			'blp',
			'blp--' . sanitize_html_class( $view['mode'] ),
		);
		if ( ! empty( $view['args']['class_name'] ) ) {
			$classes[] = sanitize_html_class( (string) $view['args']['class_name'] );
		}

		$uses_external_form = ! empty( $view['config']['externalFormSelector'] );
		$render_hidden_input = ! empty( $view['config']['renderHiddenInput'] );
		$input_data_role = ! empty( $view['args']['input_data_role'] ) ? sanitize_key( (string) $view['args']['input_data_role'] ) : '';
		$input_id = ! empty( $view['args']['input_id'] ) ? sanitize_html_class( (string) $view['args']['input_id'] ) : '';

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-blp-root>
			<div class="blp__shell">
				<?php if ( ! empty( $view['args']['show_title'] ) && '' !== (string) $view['args']['title'] ) : ?>
					<div class="blp__title"><?php echo esc_html( $view['args']['title'] ); ?></div>
				<?php endif; ?>

				<?php if ( ! $uses_external_form ) : ?>
					<form
						method="get"
						action="<?php echo esc_url( $view['form_action'] ); ?>"
						class="blp__form"
						data-blp-form
					>
						<input type="hidden" <?php echo '' !== $input_id ? 'id="' . esc_attr( $input_id ) . '"' : ''; ?> name="<?php echo esc_attr( $view['config']['inputName'] ); ?>" value="<?php echo esc_attr( $view['selected']['deepest_term_id'] ); ?>" data-blp-country-input <?php echo '' !== $input_data_role ? 'data-search-role="' . esc_attr( $input_data_role ) . '"' : ''; ?>>
						<?php echo $view['hidden_fields']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</form>
				<?php elseif ( $render_hidden_input ) : ?>
					<input type="hidden" <?php echo '' !== $input_id ? 'id="' . esc_attr( $input_id ) . '"' : ''; ?> name="<?php echo esc_attr( $view['config']['inputName'] ); ?>" value="<?php echo esc_attr( $view['selected']['deepest_term_id'] ); ?>" data-blp-country-input <?php echo '' !== $input_data_role ? 'data-search-role="' . esc_attr( $input_data_role ) . '"' : ''; ?>>
				<?php endif; ?>

				<?php if ( 'inline' !== $view['mode'] ) : ?>
					<button
						type="button"
						class="blp__trigger"
						data-blp-trigger
						aria-expanded="false"
						aria-controls="<?php echo esc_attr( $view['id'] ); ?>"
					>
						<span class="blp__trigger-copy">
							<span class="blp__trigger-label"><?php echo esc_html( $view['args']['button_label'] ); ?></span>
							<span class="blp__summary" data-blp-summary><?php echo esc_html( $view['summary'] ); ?></span>
						</span>
						<span class="blp__trigger-icon" aria-hidden="true">
							<svg width="20" height="20" viewBox="0 0 24 24" focusable="false"><path d="M12 2a7 7 0 00-7 7c0 5.14 7 13 7 13s7-7.86 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/></svg>
						</span>
					</button>
				<?php endif; ?>

				<div class="blp__backdrop" data-blp-backdrop hidden></div>

				<div
					id="<?php echo esc_attr( $view['id'] ); ?>"
					class="blp__panel"
					data-blp-panel
					<?php echo 'inline' !== $view['mode'] ? 'hidden' : ''; ?>
				>
					<?php if ( 'inline' !== $view['mode'] ) : ?>
						<button type="button" class="blp__close" data-blp-close aria-label="<?php esc_attr_e( 'بستن', 'bornado-location-picker' ); ?>">
							<span aria-hidden="true">&times;</span>
						</button>
					<?php endif; ?>

					<div class="blp__columns">
						<section class="blp__column blp__column--countries" aria-labelledby="<?php echo esc_attr( $view['id'] ); ?>-countries-heading">
							<div class="blp__section-head">
								<h3 id="<?php echo esc_attr( $view['id'] ); ?>-countries-heading" class="blp__section-title"><?php echo esc_html( $view['args']['country_heading'] ); ?></h3>
								<label class="blp__sr-only" for="<?php echo esc_attr( $view['id'] ); ?>-country-search"><?php echo esc_html( $view['args']['search_label'] ); ?></label>
								<input
									type="search"
									id="<?php echo esc_attr( $view['id'] ); ?>-country-search"
									class="blp__search"
									data-blp-country-search
									placeholder="<?php echo esc_attr( $view['args']['search_label'] ); ?>"
								>
							</div>
							<div class="blp__list" data-blp-country-list>
								<?php self::render_country_item(
									array(
										'id'       => 0,
										'label'    => (string) $view['args']['reset_label'],
										'url'      => $view['search_actions']['all_countries_action'],
										'kind'     => 'reset',
										'parentId' => 0,
									),
									empty( $view['selected']['country']['id'] )
								); ?>
								<?php foreach ( $view['countries'] as $country ) : ?>
									<?php self::render_country_item( $country, ! empty( $view['selected']['country']['id'] ) && (int) $view['selected']['country']['id'] === (int) $country['id'] ); ?>
								<?php endforeach; ?>
							</div>
						</section>

						<section class="blp__column blp__column--cities" aria-labelledby="<?php echo esc_attr( $view['id'] ); ?>-cities-heading">
							<div class="blp__section-head">
								<h3 id="<?php echo esc_attr( $view['id'] ); ?>-cities-heading" class="blp__section-title"><?php echo esc_html( $view['args']['city_heading'] ); ?></h3>
								<label class="blp__sr-only" for="<?php echo esc_attr( $view['id'] ); ?>-city-search"><?php echo esc_html( $view['args']['city_label'] ); ?></label>
								<input
									type="search"
									id="<?php echo esc_attr( $view['id'] ); ?>-city-search"
									class="blp__search"
									data-blp-city-search
									placeholder="<?php echo esc_attr( $view['args']['city_label'] ); ?>"
									<?php echo empty( $view['selected']['country']['id'] ) ? 'disabled' : ''; ?>
								>
							</div>

							<div class="blp__list" data-blp-city-list>
								<?php if ( ! empty( $view['cities'] ) ) : ?>
									<?php foreach ( $view['cities'] as $city ) : ?>
										<?php self::render_city_item( $city, ! empty( $view['selected']['city']['id'] ) && (int) $view['selected']['city']['id'] === (int) $city['id'] ); ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</section>
					</div>

					<div class="blp__footer">
						<?php if ( ! empty( $view['args']['show_footer_reset'] ) ) : ?>
						<button type="button" class="blp__secondary" data-blp-reset><?php echo esc_html( $view['args']['reset_label'] ); ?></button>
						<?php endif; ?>
						<button type="button" class="blp__primary" data-blp-apply><?php echo esc_html( $view['args']['submit_label'] ); ?></button>
					</div>
				</div>

				<?php self::render_noscript_markup( $view ); ?>

				<script type="application/json" class="blp__config">
					<?php echo wp_json_encode( $view['config'] ); ?>
				</script>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a selectable country item.
	 *
	 * @param array<string,mixed> $country Country payload.
	 * @param bool                $selected Whether the item is selected.
	 * @return void
	 */
	private static function render_country_item( $country, $selected = false ) {
		$classes = array( 'blp__item', 'blp__item--country' );
		if ( $selected ) {
			$classes[] = 'is-selected';
		}
		?>
		<button
			type="button"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-blp-country
			data-id="<?php echo esc_attr( $country['id'] ); ?>"
			data-url="<?php echo esc_url( $country['url'] ); ?>"
			data-kind="<?php echo esc_attr( $country['kind'] ); ?>"
			data-label="<?php echo esc_attr( $country['label'] ); ?>"
		>
			<span class="blp__item-copy">
				<span class="blp__item-label"><?php echo esc_html( $country['label'] ); ?></span>
			</span>
			<span class="blp__check" aria-hidden="true"></span>
		</button>
		<?php
	}

	/**
	 * Render a selectable city item.
	 *
	 * @param array<string,mixed> $city City payload.
	 * @param bool                $selected Whether the item is selected.
	 * @return void
	 */
	private static function render_city_item( $city, $selected = false ) {
		$classes = array( 'blp__item', 'blp__item--city' );
		if ( $selected ) {
			$classes[] = 'is-selected';
		}
		?>
		<button
			type="button"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-blp-city
			data-id="<?php echo esc_attr( $city['id'] ); ?>"
			data-parent-id="<?php echo esc_attr( $city['parentId'] ); ?>"
			data-url="<?php echo esc_url( $city['url'] ); ?>"
			data-kind="<?php echo esc_attr( $city['kind'] ); ?>"
			data-label="<?php echo esc_attr( $city['label'] ); ?>"
		>
			<span class="blp__item-copy">
				<span class="blp__item-label"><?php echo esc_html( $city['label'] ); ?></span>
			</span>
			<span class="blp__check" aria-hidden="true"></span>
		</button>
		<?php
	}

	/**
	 * Render a lightweight no-JS fallback.
	 *
	 * @param array<string,mixed> $view View model.
	 * @return void
	 */
	private static function render_noscript_markup( $view ) {
		$country_url = ! empty( $view['selected']['country']['url'] ) ? (string) $view['selected']['country']['url'] : $view['search_actions']['all_countries_action'];
		?>
		<noscript>
			<div class="blp__noscript">
				<p><?php esc_html_e( 'در حالت بدون جاوااسکریپت می‌توانید از لینک‌های زیر استفاده کنید.', 'bornado-location-picker' ); ?></p>
				<p>
					<a href="<?php echo esc_url( $view['search_actions']['all_countries_action'] ); ?>"><?php echo esc_html( $view['args']['reset_label'] ); ?></a>
					<?php if ( ! empty( $view['selected']['country']['label'] ) ) : ?>
						|
						<a href="<?php echo esc_url( $country_url ); ?>"><?php echo esc_html( $view['selected']['country']['label'] ); ?></a>
					<?php endif; ?>
				</p>
				<?php if ( ! empty( $view['cities'] ) ) : ?>
					<ul class="blp__noscript-list">
						<?php foreach ( $view['cities'] as $city ) : ?>
							<li><a href="<?php echo esc_url( $city['url'] ); ?>"><?php echo esc_html( $city['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</noscript>
		<?php
	}
}

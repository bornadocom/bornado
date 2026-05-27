<?php
/**
 * Classic widget wrapper for the Bornado location picker.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Widget' ) || class_exists( 'Bornado_Location_Picker_Widget' ) ) {
	return;
}

class Bornado_Location_Picker_Widget extends WP_Widget {
	/**
	 * Register widget metadata.
	 */
	public function __construct() {
		parent::__construct(
			'bornado_location_picker_widget',
			__( 'Bornado Location Picker', 'bornado-location-picker' ),
			array(
				'classname'   => 'bornado_location_picker_widget',
				'description' => __( 'Reusable country/city picker for Bornado search flows.', 'bornado-location-picker' ),
			)
		);
	}

	/**
	 * Render the widget on the front-end.
	 *
	 * @param array<string,mixed> $args Widget wrapper args.
	 * @param array<string,mixed> $instance Saved settings.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? (string) apply_filters( 'widget_title', $instance['title'] ) : '';
		$mode  = ! empty( $instance['mode'] ) ? (string) $instance['mode'] : 'compact';

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo Bornado_Location_Picker_Plugin::render(
			array(
				'mode'         => $mode,
				'button_label' => __( 'انتخاب کشور و شهر', 'bornado-location-picker' ),
			)
		); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Widget settings form.
	 *
	 * @param array<string,mixed> $instance Saved settings.
	 * @return void
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? (string) $instance['title'] : '';
		$mode  = isset( $instance['mode'] ) ? (string) $instance['mode'] : 'compact';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'عنوان:', 'bornado-location-picker' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>"><?php esc_html_e( 'حالت نمایش:', 'bornado-location-picker' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mode' ) ); ?>">
				<option value="compact" <?php selected( $mode, 'compact' ); ?>><?php esc_html_e( 'Compact', 'bornado-location-picker' ); ?></option>
				<option value="inline" <?php selected( $mode, 'inline' ); ?>><?php esc_html_e( 'Inline', 'bornado-location-picker' ); ?></option>
				<option value="header" <?php selected( $mode, 'header' ); ?>><?php esc_html_e( 'Header', 'bornado-location-picker' ); ?></option>
			</select>
		</p>
		<?php
	}

	/**
	 * Persist sanitized widget settings.
	 *
	 * @param array<string,mixed> $new_instance New values.
	 * @param array<string,mixed> $old_instance Previous values.
	 * @return array<string,mixed>
	 */
	public function update( $new_instance, $old_instance ) {
		unset( $old_instance );

		return array(
			'title' => ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '',
			'mode'  => ! empty( $new_instance['mode'] ) ? sanitize_key( $new_instance['mode'] ) : 'compact',
		);
	}
}

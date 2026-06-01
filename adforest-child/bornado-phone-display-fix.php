<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bornado_inline_phone_markup' ) ) {
	/**
	 * Wrap an international phone so the plus sign stays at the start in RTL text.
	 *
	 * @param string $phone Canonical phone number (e.g. +447946801433).
	 * @return string Safe HTML fragment.
	 */
	function bornado_inline_phone_markup( $phone ) {
		$phone = trim( (string) $phone );

		if ( '' === $phone ) {
			return '';
		}

		return sprintf(
			'<bdi dir="ltr" class="bornado-inline-phone">%s</bdi>',
			esc_html( $phone )
		);
	}
}

if ( ! function_exists( 'bornado_enqueue_phone_display_fix' ) ) {
	/**
	 * Keep international phone numbers visually stable inside RTL layouts.
	 *
	 * Stored numbers already use a canonical +countrycode format. This CSS
	 * only fixes bidi rendering so the plus sign stays at the start.
	 *
	 * @return void
	 */
	function bornado_enqueue_phone_display_fix() {
		$handle = 'bornado-phone-display-fix';
		$css    = '
.style_2_ph,
.sb-phonenumber,
.phone-number,
.contact-info li:first-child p:last-child,
.adt-seller-detail-sidebar li .phone-number,
.adt-seller-detail-sidebar li .sb-phonenumber,
.bornado-inline-phone,
bdi.bornado-inline-phone {
	display: inline-block;
	direction: ltr;
	unicode-bidi: isolate;
	text-align: left;
}

.bornado-auth-modal__subtitle .bornado-inline-phone,
.bornado-auth-modal__notice .bornado-inline-phone,
.bornad-claim-note .bornado-inline-phone,
.bornado-phone-helper-text .bornado-inline-phone {
	display: inline-block;
	direction: ltr;
	unicode-bidi: isolate;
	text-align: left;
}

#sb_user_contact,
#sb_ph_number,
input[name="ad_contact_number"],
input[name="phone_number"],
input[name="sb_ph_number"],
input[name="sb_reg_contact"],
input[name="adforest_reg_number"],
input[name="sb_reg_phone"],
select[name="bornado_phone_dial_code"],
select[name="phone_dial_code"],
.bornado-phone-country-select,
.bornado-auth-country-select,
.bornad-claim-modal input[readonly] {
	direction: ltr;
	unicode-bidi: plaintext;
	text-align: left;
}

.bornado-phone-helper-text {
	display: block;
	direction: rtl;
	text-align: right;
}
';

		wp_register_style( $handle, false, array(), null );
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, $css );
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_phone_display_fix', 140 );

if ( ! function_exists( 'bornado_enqueue_phone_display_fix_scripts' ) ) {
	/**
	 * Expose inline-phone markup helper for frontend scripts (auth modal subtitles).
	 *
	 * @return void
	 */
	function bornado_enqueue_phone_display_fix_scripts() {
		if ( ! wp_script_is( 'bornado-auth-modal', 'registered' ) ) {
			return;
		}

		$inline_js = <<<'JS'
window.bornadoWrapInlinePhone = function (phone) {
	var value = String(phone || '').trim();
	if (!value) {
		return '';
	}
	var node = document.createElement('span');
	node.textContent = value;
	return '<bdi dir="ltr" class="bornado-inline-phone">' + node.innerHTML + '</bdi>';
};
JS;

		wp_add_inline_script( 'bornado-auth-modal', $inline_js, 'before' );
	}
}
add_action( 'wp_enqueue_scripts', 'bornado_enqueue_phone_display_fix_scripts', 141 );

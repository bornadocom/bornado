<?php
/**
 * Normalize Persian/Arabic numerals for numeric-like inputs.
 *
 * Keeps free text intact, but canonicalizes values that are clearly numeric-like
 * or belong to known numeric fields so AdForest validation/storage stays stable.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_convert_locale_digits_to_latin')) {
    /**
     * Convert Persian/Arabic digits and punctuation to Latin equivalents.
     *
     * @param string $value Raw string.
     * @return string
     */
    function bornado_convert_locale_digits_to_latin($value)
    {
        $value = (string) $value;

        return strtr(
            $value,
            array(
                '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
                '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                '٫' => '.',
                '٬' => ',',
                '،' => ',',
                '−' => '-',
                '–' => '-',
                '—' => '-',
            )
        );
    }
}

if (!function_exists('bornado_is_numeric_like_submission_value')) {
    /**
     * Whether a value is composed only of digits / numeric separators.
     *
     * @param mixed $value Raw value.
     * @return bool
     */
    function bornado_is_numeric_like_submission_value($value)
    {
        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);
        if ($value === '') {
            return false;
        }

        return 1 === preg_match('/^[0-9۰-۹٠-٩\s\+\-\(\)\/\\\\:.,،٫٬]+$/u', $value);
    }
}

if (!function_exists('bornado_is_price_like_field_name')) {
    /**
     * @param string $field_name Field name.
     * @return bool
     */
    function bornado_is_price_like_field_name($field_name)
    {
        $field_name = strtolower((string) $field_name);

        return in_array(
            $field_name,
            array(
                'ad_price',
                'ad_price_from',
                'ad_price_to',
                'bid_amount',
                'min_price',
                'max_price',
            ),
            true
        );
    }
}

if (!function_exists('bornado_is_phone_like_field_name')) {
    /**
     * @param string $field_name Field name.
     * @return bool
     */
    function bornado_is_phone_like_field_name($field_name)
    {
        $field_name = strtolower((string) $field_name);

        return in_array(
            $field_name,
            array(
                'ad_contact_number',
                'sb_reg_contact',
                'sb_reg_phone',
                'adforest_reg_number',
                'sb_contact',
                'phone',
                'mobile',
            ),
            true
        );
    }
}

if (!function_exists('bornado_is_decimal_like_field_name')) {
    /**
     * @param string $field_name Field name.
     * @return bool
     */
    function bornado_is_decimal_like_field_name($field_name)
    {
        $field_name = strtolower((string) $field_name);

        return in_array(
            $field_name,
            array(
                'ad_map_lat',
                'ad_map_long',
                'lat',
                'long',
                'longitude',
                'longitude_id',
                'latitude',
                'radius',
            ),
            true
        );
    }
}

if (!function_exists('bornado_normalize_price_like_string')) {
    /**
     * Canonicalize price-like input for storage/validation.
     *
     * @param string $value Raw value.
     * @return string
     */
    function bornado_normalize_price_like_string($value)
    {
        $value = bornado_convert_locale_digits_to_latin($value);
        $value = preg_replace('/[\s,]+/u', '', $value);
        $value = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_string($value) ? $value : '';
    }
}

if (!function_exists('bornado_normalize_phone_like_string')) {
    /**
     * Canonicalize phone-like input while preserving leading plus.
     *
     * @param string $value Raw value.
     * @return string
     */
    function bornado_normalize_phone_like_string($value)
    {
        $value = bornado_convert_locale_digits_to_latin($value);
        $value = preg_replace('/[\s\-\(\)]+/u', '', $value);
        $value = preg_replace('/(?!^\+)[^0-9]/', '', (string) $value);

        return is_string($value) ? $value : '';
    }
}

if (!function_exists('bornado_normalize_decimal_like_string')) {
    /**
     * Canonicalize decimal-like input such as lat/lon.
     *
     * @param string $value Raw value.
     * @return string
     */
    function bornado_normalize_decimal_like_string($value)
    {
        $value = bornado_convert_locale_digits_to_latin($value);
        $value = preg_replace('/\s+/u', '', $value);
        $value = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_string($value) ? $value : '';
    }
}

if (!function_exists('bornado_normalize_numeric_submission_value')) {
    /**
     * Normalize one numeric-like submission value.
     *
     * @param mixed  $value      Raw value.
     * @param string $field_name Optional field name.
     * @return mixed
     */
    function bornado_normalize_numeric_submission_value($value, $field_name = '')
    {
        if (is_array($value)) {
            foreach ($value as $child_key => $child_value) {
                $value[$child_key] = bornado_normalize_numeric_submission_value($child_value, is_string($child_key) ? $child_key : $field_name);
            }

            return $value;
        }

        if (!is_string($value) || $value === '') {
            return $value;
        }

        $field_name = strtolower((string) $field_name);

        if (bornado_is_price_like_field_name($field_name)) {
            return bornado_normalize_price_like_string($value);
        }

        if (bornado_is_phone_like_field_name($field_name)) {
            return bornado_normalize_phone_like_string($value);
        }

        if (bornado_is_decimal_like_field_name($field_name)) {
            return bornado_normalize_decimal_like_string($value);
        }

        if (bornado_is_numeric_like_submission_value($value)) {
            return bornado_convert_locale_digits_to_latin($value);
        }

        return $value;
    }
}

if (!function_exists('bornado_normalize_numeric_submission_array')) {
    /**
     * Recursively normalize numeric-like values inside one request array.
     *
     * @param array $data Raw request data.
     * @return array
     */
    function bornado_normalize_numeric_submission_array(array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = bornado_normalize_numeric_submission_value($value, is_string($key) ? $key : '');
        }

        return $data;
    }
}

if (!function_exists('bornado_normalize_encoded_request_payload')) {
    /**
     * Normalize a URL-encoded payload stored inside one POST field.
     *
     * @param string $payload_key POST field containing encoded payload.
     * @return void
     */
    function bornado_normalize_encoded_request_payload($payload_key)
    {
        if (empty($_POST[$payload_key]) || !is_string($_POST[$payload_key])) {
            return;
        }

        $raw_payload = wp_unslash($_POST[$payload_key]);
        if (!is_string($raw_payload) || $raw_payload === '') {
            return;
        }

        $params = array();
        parse_str($raw_payload, $params);
        if (!is_array($params) || empty($params)) {
            return;
        }

        $params = bornado_normalize_numeric_submission_array($params);
        $_POST[$payload_key] = wp_slash(http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    }
}

if (!function_exists('bornado_bootstrap_numeric_input_normalization')) {
    /**
     * Normalize numeric-like user input early in the request lifecycle.
     *
     * @return void
     */
    function bornado_bootstrap_numeric_input_normalization()
    {
        if (!empty($_GET) && is_array($_GET)) {
            $_GET = bornado_normalize_numeric_submission_array($_GET);
        }

        if (empty($_POST) || !is_array($_POST)) {
            return;
        }

        $_POST = bornado_normalize_numeric_submission_array($_POST);

        foreach (array('sb_data', 'form_data', 'submit_alert_data', 'formdata') as $payload_key) {
            bornado_normalize_encoded_request_payload($payload_key);
        }
    }

    add_action('init', 'bornado_bootstrap_numeric_input_normalization', -20);
}

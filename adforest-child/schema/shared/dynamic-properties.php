<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_get_single_ad_field_map')) {
    /**
     * Optional slug => schema property map for dynamic category template fields.
     *
     * @param array<string,mixed> $context
     * @return array<string,string>
     */
    function bornado_schema_manager_get_single_ad_field_map(array $context)
    {
        $map = array(
            'mileage'           => 'mileageFromOdometer',
            'kilometer'         => 'mileageFromOdometer',
            'kilometers'        => 'mileageFromOdometer',
            'km'                => 'mileageFromOdometer',
            'kilometrage'       => 'mileageFromOdometer',
            'year'              => 'vehicleModelDate',
            'model_year'        => 'vehicleModelDate',
            'vehicle_year'      => 'vehicleModelDate',
            'sal_sakht'         => 'vehicleModelDate',
            'make'              => 'brand',
            'brand'             => 'brand',
            'manufacturer'      => 'brand',
            'model'             => 'model',
            'fuel'              => 'fuelType',
            'fuel_type'         => 'fuelType',
            'transmission'      => 'vehicleTransmission',
            'gearbox'           => 'vehicleTransmission',
            'color'             => 'color',
            'bedrooms'          => 'numberOfRooms',
            'bedroom'           => 'numberOfRooms',
            'rooms'             => 'numberOfRooms',
            'tedad_khab'        => 'numberOfRooms',
            'no_of_bedrooms'    => 'numberOfRooms',
            'bathrooms'         => 'numberOfBathroomsTotal',
            'bathroom'          => 'numberOfBathroomsTotal',
            'tedad_hammam'      => 'numberOfBathroomsTotal',
            'floor_size'        => 'floorSize',
            'property_size'     => 'floorSize',
            'area'              => 'floorSize',
            'metraj'            => 'floorSize',
            'service_type'      => 'serviceType',
            'noe_khedmat'       => 'serviceType',
            'salary'            => 'baseSalary',
            'job_type'          => 'employmentType',
            'employment_type'   => 'employmentType',
        );

        return (array) apply_filters('bornado_schema_manager_single_ad_field_map', $map, $context);
    }
}

if (!function_exists('bornado_schema_manager_normalize_dynamic_field_value')) {
    /**
     * Normalize dynamic field values into schema-friendly scalars/strings.
     *
     * @param mixed $value
     * @return string|int|float|null
     */
    function bornado_schema_manager_normalize_dynamic_field_value($value)
    {
        if (is_array($value)) {
            $parts = array();
            foreach ($value as $item) {
                $normalized = bornado_schema_manager_normalize_dynamic_field_value($item);
                if ($normalized !== null && $normalized !== '') {
                    $parts[] = (string) $normalized;
                }
            }
            $joined = bornado_schema_manager_normalize_schema_text(implode(', ', $parts));

            return $joined === '' ? null : $joined;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (
            (strpos($raw, '[') === 0 && substr($raw, -1) === ']')
            || (strpos($raw, '{') === 0 && substr($raw, -1) === '}')
        ) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return bornado_schema_manager_normalize_dynamic_field_value($decoded);
            }
        }

        $number = bornado_schema_manager_parse_schema_number($raw);
        if ($number !== null && preg_match('/^[\d\s.,\-]+$/u', $raw)) {
            return $number;
        }

        $text = bornado_schema_manager_normalize_schema_text($raw);

        return $text === '' ? null : $text;
    }
}

if (!function_exists('bornado_schema_manager_collect_single_ad_dynamic_fields')) {
    /**
     * Read dynamic category-template fields for an ad.
     *
     * @param array<string,mixed> $context
     * @return array<int,array{slug:string,title:string,value:mixed}>
     */
    function bornado_schema_manager_collect_single_ad_dynamic_fields(array $context)
    {
        $post_id = isset($context['post_id']) ? (int) $context['post_id'] : 0;
        if ($post_id < 1 || !function_exists('adforest_dynamic_templateID') || !function_exists('sb_dynamic_form_data')) {
            return array();
        }

        $category_term = null;
        if (!empty($context['deepest_category']) && $context['deepest_category'] instanceof WP_Term) {
            $category_term = $context['deepest_category'];
        } elseif (!empty($context['root_category']) && $context['root_category'] instanceof WP_Term) {
            $category_term = $context['root_category'];
        }

        if (!($category_term instanceof WP_Term)) {
            return array();
        }

        $template_id = adforest_dynamic_templateID((int) $category_term->term_id);
        if (empty($template_id)) {
            return array();
        }

        $result = get_term_meta((int) $template_id, '_sb_dynamic_form_fields', true);
        $rows = sb_dynamic_form_data($result);
        if (!is_array($rows) || empty($rows)) {
            return array();
        }

        $fields = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $slug = isset($row['slugs']) ? sanitize_title((string) $row['slugs']) : '';
            $title = isset($row['titles']) ? bornado_schema_manager_normalize_schema_text($row['titles']) : '';
            if ($slug === '' || $title === '') {
                continue;
            }

            $raw_value = get_post_meta($post_id, '_adforest_tpl_field_' . $slug, true);
            $value = bornado_schema_manager_normalize_dynamic_field_value($raw_value);
            if ($value === null || $value === '') {
                continue;
            }

            $fields[] = array(
                'slug'  => $slug,
                'title' => $title,
                'value' => $value,
            );
        }

        return $fields;
    }
}

if (!function_exists('bornado_schema_manager_resolve_dynamic_field_property')) {
    /**
     * Resolve a schema property from field slug and/or Persian/English title.
     *
     * @param string              $slug
     * @param string              $title
     * @param array<string,string> $map
     * @return string
     */
    function bornado_schema_manager_resolve_dynamic_field_property($slug, $title, array $map)
    {
        $slug = sanitize_title((string) $slug);
        if ($slug !== '' && isset($map[$slug])) {
            return (string) $map[$slug];
        }

        $title_norm = bornado_schema_manager_normalize_schema_text($title);
        $title_key = function_exists('mb_strtolower')
            ? mb_strtolower($title_norm, 'UTF-8')
            : strtolower($title_norm);

        $title_map = array(
            'سال ساخت'           => 'vehicleModelDate',
            'سال'                => 'vehicleModelDate',
            'year'               => 'vehicleModelDate',
            'model year'         => 'vehicleModelDate',
            'کیلومتر'            => 'mileageFromOdometer',
            'کارکرد'             => 'mileageFromOdometer',
            'mileage'            => 'mileageFromOdometer',
            'برند'               => 'brand',
            'سازنده'             => 'brand',
            'brand'              => 'brand',
            'make'               => 'brand',
            'مدل'                => 'model',
            'model'              => 'model',
            'سوخت'               => 'fuelType',
            'fuel'               => 'fuelType',
            'گیربکس'             => 'vehicleTransmission',
            'transmission'       => 'vehicleTransmission',
            'رنگ'                => 'color',
            'color'              => 'color',
            'تعداد خواب'         => 'numberOfRooms',
            'خواب'               => 'numberOfRooms',
            'اتاق خواب'          => 'numberOfRooms',
            'bedrooms'           => 'numberOfRooms',
            'تعداد حمام'         => 'numberOfBathroomsTotal',
            'حمام'               => 'numberOfBathroomsTotal',
            'bathrooms'          => 'numberOfBathroomsTotal',
            'متراژ'              => 'floorSize',
            'مساحت'              => 'floorSize',
            'area'               => 'floorSize',
            'نوع خدمت'           => 'serviceType',
            'service type'       => 'serviceType',
            'نوع شغل'            => 'employmentType',
            'حقوق'               => 'baseSalary',
            'salary'             => 'baseSalary',
        );

        $title_map = (array) apply_filters('bornado_schema_manager_single_ad_field_title_map', $title_map, $slug, $title);

        return isset($title_map[$title_key]) ? (string) $title_map[$title_key] : '';
    }
}

if (!function_exists('bornado_schema_manager_apply_mapped_dynamic_fields')) {
    /**
     * Apply known dynamic-field mappings onto a schema entity.
     *
     * @param array<string,mixed> $entity
     * @param array<string,mixed> $context
     * @param array<int,array{slug:string,title:string,value:mixed}> $fields
     * @return array{entity:array<string,mixed>,remaining:array<int,array{slug:string,title:string,value:mixed}>}
     */
    function bornado_schema_manager_apply_mapped_dynamic_fields(array $entity, array $context, array $fields)
    {
        $map = bornado_schema_manager_get_single_ad_field_map($context);
        $remaining = array();

        foreach ($fields as $field) {
            $slug = isset($field['slug']) ? (string) $field['slug'] : '';
            $title = isset($field['title']) ? (string) $field['title'] : '';
            $value = isset($field['value']) ? $field['value'] : null;
            if (($slug === '' && $title === '') || $value === null || $value === '') {
                continue;
            }

            $property = bornado_schema_manager_resolve_dynamic_field_property($slug, $title, $map);
            if ($property === '' || isset($entity[$property])) {
                $remaining[] = $field;
                continue;
            }

            if ($property === 'brand') {
                $entity[$property] = array(
                    '@type' => 'Brand',
                    'name'  => is_scalar($value) ? (string) $value : bornado_schema_manager_normalize_schema_text(wp_json_encode($value)),
                );
                continue;
            }

            if ($property === 'serviceType' || $property === 'model' || $property === 'fuelType' || $property === 'vehicleTransmission' || $property === 'color' || $property === 'employmentType') {
                $entity[$property] = is_scalar($value) ? (string) $value : bornado_schema_manager_normalize_schema_text(wp_json_encode($value));
                continue;
            }

            if ($property === 'mileageFromOdometer') {
                $entity[$property] = array(
                    '@type'    => 'QuantitativeValue',
                    'value'    => $value,
                    'unitCode' => 'KMT',
                );
                continue;
            }

            if ($property === 'floorSize') {
                $entity[$property] = array(
                    '@type'    => 'QuantitativeValue',
                    'value'    => $value,
                    'unitText' => 'm²',
                );
                continue;
            }

            if ($property === 'baseSalary') {
                $salary = array(
                    '@type' => 'MonetaryAmount',
                    'value' => $value,
                );
                if (!empty($context['currency'])) {
                    $salary['currency'] = (string) $context['currency'];
                }
                $entity[$property] = $salary;
                continue;
            }

            $entity[$property] = $value;
        }

        return array(
            'entity'    => $entity,
            'remaining' => $remaining,
        );
    }
}

if (!function_exists('bornado_schema_manager_build_additional_properties')) {
    /**
     * Build PropertyValue nodes for unmapped dynamic fields.
     *
     * @param array<int,array{slug:string,title:string,value:mixed}> $fields
     * @return array<int,array<string,mixed>>
     */
    function bornado_schema_manager_build_additional_properties(array $fields)
    {
        $properties = array();

        foreach ($fields as $field) {
            $title = isset($field['title']) ? (string) $field['title'] : '';
            $value = isset($field['value']) ? $field['value'] : null;
            if ($title === '' || $value === null || $value === '') {
                continue;
            }

            $properties[] = array(
                '@type' => 'PropertyValue',
                'name'  => $title,
                'value' => $value,
            );
        }

        return $properties;
    }
}

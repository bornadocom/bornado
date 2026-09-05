<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_build_single_ad_place_entity')) {
    /**
     * Build Place / PostalAddress for a single ad.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_single_ad_place_entity(array $context)
    {
        $country_term = !empty($context['country_term']) && $context['country_term'] instanceof WP_Term
            ? $context['country_term']
            : null;
        $city_term = !empty($context['city_term']) && $context['city_term'] instanceof WP_Term
            ? $context['city_term']
            : null;

        if (!($country_term instanceof WP_Term) && !($city_term instanceof WP_Term) && empty($context['location_label'])) {
            return array();
        }

        $canonical_url = isset($context['canonical_url']) ? (string) $context['canonical_url'] : '';
        $place_id = !empty($context['ids']['place']) ? (string) $context['ids']['place'] : bornado_schema_manager_get_ad_place_id($canonical_url);

        $name_parts = array();
        if ($city_term instanceof WP_Term) {
            $name_parts[] = $city_term->name;
        }
        if ($country_term instanceof WP_Term) {
            $name_parts[] = $country_term->name;
        }
        if (empty($name_parts) && !empty($context['location_label'])) {
            $name_parts[] = (string) $context['location_label'];
        }

        $place = array(
            '@type' => 'Place',
            '@id'   => $place_id,
            'name'  => implode(', ', $name_parts),
        );

        $address = array(
            '@type' => 'PostalAddress',
        );

        if ($city_term instanceof WP_Term) {
            $address['addressLocality'] = $city_term->name;
        }

        if ($country_term instanceof WP_Term) {
            $country_payload = array(
                '@type' => 'Country',
                'name'  => $country_term->name,
            );
            $country_code = '';
            if (function_exists('bornado_get_country_data')) {
                $country_data = (array) bornado_get_country_data($country_term);
                $country_code = !empty($country_data['country_code']) ? (string) $country_data['country_code'] : '';
            }
            if ($country_code !== '') {
                $country_payload['alternateName'] = $country_code;
                $country_payload['identifier'] = $country_code;
            }
            $address['addressCountry'] = $country_payload;
        }

        if (!empty($context['location_label'])) {
            $address['streetAddress'] = (string) $context['location_label'];
        }

        if (count($address) > 1) {
            $place['address'] = $address;
        }

        if (
            isset($context['latitude'], $context['longitude'])
            && $context['latitude'] !== null
            && $context['longitude'] !== null
            && abs((float) $context['latitude']) <= 90
            && abs((float) $context['longitude']) <= 180
        ) {
            $place['geo'] = array(
                '@type'     => 'GeoCoordinates',
                'latitude'  => $context['latitude'],
                'longitude' => $context['longitude'],
            );
        }

        return $place;
    }
}

if (!function_exists('bornado_schema_manager_build_single_ad_image_entities')) {
    /**
     * Build ImageObject nodes for the ad gallery.
     *
     * @param array<string,mixed> $context
     * @return array<int,array<string,mixed>>
     */
    function bornado_schema_manager_build_single_ad_image_entities(array $context)
    {
        $canonical_url = isset($context['canonical_url']) ? (string) $context['canonical_url'] : '';
        $urls = isset($context['image_urls']) && is_array($context['image_urls']) ? $context['image_urls'] : array();
        $entities = array();
        $index = 0;

        foreach ($urls as $url) {
            $url = (string) $url;
            if ($url === '') {
                continue;
            }
            $index++;
            $entities[] = array(
                '@type'      => 'ImageObject',
                '@id'        => bornado_schema_manager_get_ad_image_id($canonical_url, $index),
                'url'        => $url,
                'contentUrl' => $url,
                'caption'    => isset($context['title']) ? (string) $context['title'] : '',
            );
        }

        return $entities;
    }
}

if (!function_exists('bornado_schema_manager_build_single_ad_seller_entity')) {
    /**
     * Build a privacy-safe seller/poster Person node.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_single_ad_seller_entity(array $context)
    {
        $name = isset($context['poster_name']) ? bornado_schema_manager_normalize_schema_text($context['poster_name']) : '';
        if ($name === '') {
            return array();
        }

        return array(
            '@type' => 'Person',
            'name'  => $name,
        );
    }
}

if (!function_exists('bornado_schema_manager_entity_supports_offers_property')) {
    /**
     * Whether schema.org defines an `offers` property on this entity type.
     *
     * @param array<string,mixed> $entity
     * @return bool
     */
    function bornado_schema_manager_entity_supports_offers_property(array $entity)
    {
        return bornado_schema_entity_has_type(
            $entity,
            array('Product', 'Vehicle', 'Service', 'CreativeWork')
        );
    }
}

if (!function_exists('bornado_schema_manager_append_additional_property')) {
    /**
     * Append a PropertyValue without duplicating the same name.
     *
     * @param array<string,mixed> $entity
     * @param string              $name
     * @param mixed               $value
     * @return array<string,mixed>
     */
    function bornado_schema_manager_append_additional_property(array $entity, $name, $value)
    {
        $name = bornado_schema_manager_normalize_schema_text($name);
        $value = is_scalar($value)
            ? bornado_schema_manager_normalize_schema_text((string) $value)
            : bornado_schema_manager_normalize_schema_text(wp_json_encode($value));

        if ($name === '' || $value === '') {
            return $entity;
        }

        $existing = isset($entity['additionalProperty']) && is_array($entity['additionalProperty'])
            ? $entity['additionalProperty']
            : array();

        foreach ($existing as $row) {
            if (is_array($row) && isset($row['name']) && (string) $row['name'] === $name) {
                return $entity;
            }
        }

        $existing[] = array(
            '@type' => 'PropertyValue',
            'name'  => $name,
            'value' => $value,
        );
        $entity['additionalProperty'] = $existing;

        return $entity;
    }
}

if (!function_exists('bornado_schema_manager_build_single_ad_base_entity')) {
    /**
     * Shared fields for the primary ad entity before vertical enrichment.
     *
     * Only emits properties that are valid for the chosen schema.org type.
     *
     * @param array<string,mixed> $context
     * @param string              $default_type
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_single_ad_base_entity(array $context, $default_type = 'CreativeWork')
    {
        $canonical_url = isset($context['canonical_url']) ? (string) $context['canonical_url'] : '';
        $ad_id = !empty($context['ids']['ad']) ? (string) $context['ids']['ad'] : bornado_schema_manager_get_ad_entity_id($canonical_url);
        $webpage_id = !empty($context['ids']['webpage']) ? (string) $context['ids']['webpage'] : bornado_schema_manager_get_item_page_id($canonical_url);
        $type = (string) $default_type;

        $entity = array(
            '@type' => $type,
            '@id'   => $ad_id,
            'url'   => $canonical_url,
            'name'  => isset($context['title']) ? (string) $context['title'] : '',
        );

        if (!empty($context['description'])) {
            $entity['description'] = (string) $context['description'];
        }

        if ($webpage_id !== '') {
            $entity['mainEntityOfPage'] = bornado_schema_manager_get_ref($webpage_id);
        }

        // Product.category is valid; Service/Accommodation/CreativeWork use other signals.
        if (
            in_array($type, array('Product', 'Vehicle'), true)
            && !empty($context['deepest_category'])
            && $context['deepest_category'] instanceof WP_Term
        ) {
            $entity['category'] = $context['deepest_category']->name;
        } elseif (
            in_array($type, array('Product', 'Vehicle'), true)
            && !empty($context['root_category'])
            && $context['root_category'] instanceof WP_Term
        ) {
            $entity['category'] = $context['root_category']->name;
        }

        if (
            !empty($context['condition_schema'])
            && in_array($type, array('Product', 'Vehicle'), true)
        ) {
            $entity['itemCondition'] = (string) $context['condition_schema'];
        }

        if (in_array($type, array('CreativeWork'), true)) {
            if (!empty($context['date_published'])) {
                $entity['datePublished'] = (string) $context['date_published'];
            }
            if (!empty($context['date_modified'])) {
                $entity['dateModified'] = (string) $context['date_modified'];
            }
            if (!empty($context['in_language'])) {
                $entity['inLanguage'] = (string) $context['in_language'];
            }

            $about = array();
            if (!empty($context['country_term']) && $context['country_term'] instanceof WP_Term) {
                $about[] = array('@type' => 'Place', 'name' => $context['country_term']->name);
            }
            if (!empty($context['city_term']) && $context['city_term'] instanceof WP_Term) {
                $about[] = array('@type' => 'Place', 'name' => $context['city_term']->name);
            }
            if (!empty($context['deepest_category']) && $context['deepest_category'] instanceof WP_Term) {
                $about[] = array('@type' => 'Thing', 'name' => $context['deepest_category']->name);
            }
            if (!empty($about)) {
                $entity['about'] = $about;
            }

            if (!empty($context['ids']['place'])) {
                $entity['contentLocation'] = bornado_schema_manager_get_ref((string) $context['ids']['place']);
            }

            $seller = bornado_schema_manager_build_single_ad_seller_entity($context);
            if (!empty($seller)) {
                $entity['author'] = $seller;
            }
        }

        if (in_array($type, array('Accommodation', 'House', 'Apartment', 'Room'), true)) {
            if (!empty($context['ids']['place'])) {
                $entity['containedInPlace'] = bornado_schema_manager_get_ref((string) $context['ids']['place']);
            }
        }

        return $entity;
    }
}

if (!function_exists('bornado_schema_manager_sanitize_single_ad_entity_properties')) {
    /**
     * Strip type-incompatible properties as a final safety net.
     *
     * @param array<string,mixed> $entity
     * @return array<string,mixed>
     */
    function bornado_schema_manager_sanitize_single_ad_entity_properties(array $entity)
    {
        $type = isset($entity['@type']) ? (string) $entity['@type'] : '';

        // Never emit non-URL additionalType values; validators reject free-text there.
        if (isset($entity['additionalType']) && is_string($entity['additionalType'])) {
            $additional_type = trim($entity['additionalType']);
            if ($additional_type === '' || !preg_match('#^https?://#i', $additional_type)) {
                $entity = bornado_schema_manager_append_additional_property($entity, __('Ad type', 'adforest'), $additional_type);
                unset($entity['additionalType']);
            }
        }

        $product_like_forbidden = array(
            'datePosted',
            'datePublished',
            'dateModified',
            'inLanguage',
            'about',
            'availableAtOrFrom',
            'contentLocation',
            'containedInPlace',
            'seller',
            'author',
            'accommodationCategory',
            'numberOfRooms',
            'numberOfBathroomsTotal',
            'floorSize',
            'serviceType',
        );

        if (in_array($type, array('Product', 'Vehicle'), true)) {
            foreach ($product_like_forbidden as $key) {
                unset($entity[$key]);
            }
        }

        if ($type === 'Service') {
            foreach (array(
                'datePosted',
                'datePublished',
                'dateModified',
                'inLanguage',
                'about',
                'availableAtOrFrom',
                'contentLocation',
                'seller',
                'author',
                'itemCondition',
                'category',
                'accommodationCategory',
                'numberOfRooms',
                'numberOfBathroomsTotal',
                'floorSize',
                'vehicleModelDate',
                'brand',
                'model',
            ) as $key) {
                unset($entity[$key]);
            }
        }

        if (in_array($type, array('Accommodation', 'House', 'Apartment', 'Room'), true)) {
            foreach (array(
                'datePosted',
                'datePublished',
                'dateModified',
                'inLanguage',
                'about',
                'availableAtOrFrom',
                'contentLocation',
                'seller',
                'author',
                'itemCondition',
                'category',
                'accommodationCategory',
                'offers',
                'serviceType',
                'vehicleModelDate',
                'brand',
                'model',
                'additionalType',
            ) as $key) {
                unset($entity[$key]);
            }
        }

        if ($type === 'CreativeWork') {
            foreach (array(
                'availableAtOrFrom',
                'datePosted',
                'itemCondition',
                'seller',
                'category',
                'accommodationCategory',
                'numberOfRooms',
                'numberOfBathroomsTotal',
                'floorSize',
                'serviceType',
                'vehicleModelDate',
                'brand',
                'model',
            ) as $key) {
                unset($entity[$key]);
            }
        }

        if ($type === 'JobPosting') {
            foreach (array(
                'datePublished',
                'dateModified',
                'inLanguage',
                'about',
                'availableAtOrFrom',
                'contentLocation',
                'seller',
                'author',
                'category',
                'itemCondition',
                'offers',
                'accommodationCategory',
                'numberOfRooms',
                'serviceType',
            ) as $key) {
                unset($entity[$key]);
            }
        }

        // Legacy types that validators reject or misuse.
        if (in_array($type, array('ClassifiedAd', 'RealEstateListing'), true)) {
            $entity['@type'] = $type === 'RealEstateListing' ? 'Accommodation' : 'CreativeWork';
            return bornado_schema_manager_sanitize_single_ad_entity_properties($entity);
        }

        return $entity;
    }
}

if (!function_exists('bornado_schema_manager_get_single_ad_vertical_builder')) {
    /**
     * Resolve the vertical builder callback for a single ad.
     *
     * @param string $vertical_key
     * @return string
     */
    function bornado_schema_manager_get_single_ad_vertical_builder($vertical_key)
    {
        $builders = array(
            'items'     => 'bornado_schema_manager_build_items_single_ad_entity',
            'vehicles'  => 'bornado_schema_manager_build_vehicles_single_ad_entity',
            'property'  => 'bornado_schema_manager_build_property_single_ad_entity',
            'jobs'      => 'bornado_schema_manager_build_jobs_single_ad_entity',
            'services'  => 'bornado_schema_manager_build_services_single_ad_entity',
            'community' => 'bornado_schema_manager_build_community_single_ad_entity',
        );

        $builders = (array) apply_filters('bornado_schema_manager_single_ad_vertical_builders', $builders);
        $vertical_key = (string) $vertical_key;

        return isset($builders[$vertical_key]) && is_string($builders[$vertical_key])
            ? $builders[$vertical_key]
            : '';
    }
}

if (!function_exists('bornado_schema_manager_build_single_ad_main_entity')) {
    /**
     * Build the primary classified entity for the current ad context.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_single_ad_main_entity(array $context)
    {
        $vertical_key = isset($context['vertical_key']) ? (string) $context['vertical_key'] : '';
        $builder = bornado_schema_manager_get_single_ad_vertical_builder($vertical_key);

        if ($builder !== '' && function_exists($builder)) {
            $entity = $builder($context);
        } else {
            $entity = bornado_schema_manager_build_single_ad_base_entity($context, 'CreativeWork');
        }

        if (!is_array($entity) || empty($entity)) {
            $entity = bornado_schema_manager_build_single_ad_base_entity($context, 'CreativeWork');
        }

        $fields = bornado_schema_manager_collect_single_ad_dynamic_fields($context);
        $mapped = bornado_schema_manager_apply_mapped_dynamic_fields($entity, $context, $fields);
        $entity = $mapped['entity'];
        $additional = bornado_schema_manager_build_additional_properties($mapped['remaining']);
        if (!empty($additional)) {
            $entity['additionalProperty'] = isset($entity['additionalProperty']) && is_array($entity['additionalProperty'])
                ? array_merge($entity['additionalProperty'], $additional)
                : $additional;
        }

        // Specialize Accommodation subtype from mapped/remaining property-type values.
        if (bornado_schema_entity_has_type($entity, array('Accommodation', 'House', 'Apartment'))) {
            $property_kind = '';
            if (!empty($entity['additionalProperty']) && is_array($entity['additionalProperty'])) {
                foreach ($entity['additionalProperty'] as $prop) {
                    if (!is_array($prop) || empty($prop['name']) || empty($prop['value'])) {
                        continue;
                    }
                    $prop_name = bornado_schema_manager_normalize_schema_text($prop['name']);
                    if (in_array($prop_name, array('نوع ملک', 'Property type', 'property type'), true)) {
                        $property_kind = bornado_schema_manager_normalize_schema_text($prop['value']);
                        break;
                    }
                }
            }
            $kind_l = function_exists('mb_strtolower') ? mb_strtolower($property_kind, 'UTF-8') : strtolower($property_kind);
            if ($kind_l !== '' && (strpos($kind_l, 'آپارتمان') !== false || strpos($kind_l, 'apartment') !== false || strpos($kind_l, 'flat') !== false)) {
                $entity['@type'] = 'Apartment';
            } elseif ($kind_l !== '' && (strpos($kind_l, 'خانه') !== false || strpos($kind_l, 'house') !== false || strpos($kind_l, 'ویلا') !== false || strpos($kind_l, 'villa') !== false)) {
                $entity['@type'] = 'House';
            } else {
                $entity['@type'] = 'Accommodation';
            }

            if (!empty($context['ids']['place'])) {
                $entity['containedInPlace'] = bornado_schema_manager_get_ref((string) $context['ids']['place']);
            }
        }

        if (
            !empty($context['ids']['place'])
            && bornado_schema_entity_has_type($entity, array('CreativeWork'))
        ) {
            $entity['contentLocation'] = bornado_schema_manager_get_ref((string) $context['ids']['place']);
        }

        $image_refs = array();
        $image_urls = isset($context['image_urls']) && is_array($context['image_urls']) ? $context['image_urls'] : array();
        foreach (array_keys($image_urls) as $index) {
            $image_refs[] = bornado_schema_manager_get_ref(
                bornado_schema_manager_get_ad_image_id((string) $context['canonical_url'], (int) $index + 1)
            );
        }
        if (!empty($image_refs)) {
            $entity['image'] = count($image_refs) === 1 ? $image_refs[0] : $image_refs;
        }

        if (
            function_exists('bornado_schema_manager_ad_should_emit_offer')
            && bornado_schema_manager_ad_should_emit_offer($context)
            && !empty($context['ids']['offer'])
            && bornado_schema_manager_entity_supports_offers_property($entity)
            && !bornado_schema_entity_has_type($entity, array('JobPosting'))
        ) {
            $entity['offers'] = bornado_schema_manager_get_ref((string) $context['ids']['offer']);
        }

        $entity = bornado_schema_manager_sanitize_single_ad_entity_properties($entity);

        return apply_filters('bornado_schema_manager_single_ad_main_entity', $entity, $context);
    }
}

if (!function_exists('bornado_schema_manager_extend_single_ad_with_main_entity_graph')) {
    /**
     * Append main entity, offer, place, and image nodes to the Rank Math graph.
     *
     * @param array<int|string,mixed> $data
     * @param array<string,mixed>     $route_context
     * @param string                  $page_type
     * @return array<int|string,mixed>
     */
    function bornado_schema_manager_extend_single_ad_with_main_entity_graph(array $data, array $route_context, $page_type = 'single_ad')
    {
        unset($route_context, $page_type);

        $context = bornado_schema_manager_get_single_ad_context();
        if (empty($context)) {
            return $data;
        }

        $main_entity = bornado_schema_manager_build_single_ad_main_entity($context);
        if (!empty($main_entity)) {
            $data['bornado_single_ad_entity'] = $main_entity;
        }

        $offer = bornado_schema_manager_build_single_ad_offer_entity($context);
        $skip_offer = isset($data['bornado_single_ad_entity'])
            && is_array($data['bornado_single_ad_entity'])
            && bornado_schema_entity_has_type($data['bornado_single_ad_entity'], array('JobPosting'));

        // Accommodation has no `offers` property, but Offer.itemOffered -> Accommodation is valid.
        if (!empty($offer) && !$skip_offer) {
            $data['bornado_single_ad_offer'] = $offer;
        }

        $place = bornado_schema_manager_build_single_ad_place_entity($context);
        if (!empty($place)) {
            $data['bornado_single_ad_place'] = $place;
        }

        foreach (bornado_schema_manager_build_single_ad_image_entities($context) as $index => $image_entity) {
            $data['bornado_single_ad_image_' . ($index + 1)] = $image_entity;
        }

        return $data;
    }
}

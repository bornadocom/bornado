<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_ad_has_numeric_offer')) {
    /**
     * Whether the ad context has a concrete numeric commercial offer.
     *
     * @param array<string,mixed> $context
     * @return bool
     */
    function bornado_schema_manager_ad_has_numeric_offer(array $context)
    {
        $price_type = isset($context['price_type']) ? strtolower(trim((string) $context['price_type'])) : '';

        if ($price_type === 'free') {
            return true;
        }

        if ($price_type === 'range') {
            return isset($context['price_from']) && $context['price_from'] !== null
                || isset($context['price_to']) && $context['price_to'] !== null;
        }

        if (in_array($price_type, array('negotiable', 'on_call', 'no_price', 'auction'), true)) {
            return false;
        }

        return isset($context['price']) && $context['price'] !== null;
    }
}

if (!function_exists('bornado_schema_manager_ad_should_emit_offer')) {
    /**
     * Whether an Offer node should be emitted for AI/SEO commercial clarity.
     *
     * Numeric/free/range prices always emit a priced Offer.
     * Commercial verticals without a concrete number emit an Offer without inventing a price,
     * except when price is explicitly absent (`no_price`) or auction-only.
     *
     * @param array<string,mixed> $context
     * @return bool
     */
    function bornado_schema_manager_ad_should_emit_offer(array $context)
    {
        if (bornado_schema_manager_ad_has_numeric_offer($context)) {
            return true;
        }

        $vertical_key = isset($context['vertical_key']) ? (string) $context['vertical_key'] : '';
        if (!in_array($vertical_key, array('items', 'vehicles', 'property', 'services'), true)) {
            return false;
        }

        $price_type = isset($context['price_type']) ? strtolower(trim((string) $context['price_type'])) : '';

        if (in_array($price_type, array('no_price', 'auction'), true)) {
            return false;
        }

        // Negotiable/on-call, blank type, or Fixed/other without a usable number.
        return true;
    }
}

if (!function_exists('bornado_schema_manager_build_single_ad_offer_entity')) {
    /**
     * Build an Offer node for a single ad.
     *
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_single_ad_offer_entity(array $context)
    {
        if (!bornado_schema_manager_ad_should_emit_offer($context)) {
            return array();
        }

        $canonical_url = isset($context['canonical_url']) ? (string) $context['canonical_url'] : '';
        $offer_id = !empty($context['ids']['offer']) ? (string) $context['ids']['offer'] : bornado_schema_manager_get_ad_offer_id($canonical_url);
        $ad_id = !empty($context['ids']['ad']) ? (string) $context['ids']['ad'] : bornado_schema_manager_get_ad_entity_id($canonical_url);
        $price_type = isset($context['price_type']) ? strtolower(trim((string) $context['price_type'])) : '';
        $currency = isset($context['currency']) ? (string) $context['currency'] : '';
        $has_numeric = bornado_schema_manager_ad_has_numeric_offer($context);

        $offer = array(
            '@type' => 'Offer',
            '@id'   => $offer_id,
            'url'   => $canonical_url,
        );

        if ($ad_id !== '') {
            $offer['itemOffered'] = bornado_schema_manager_get_ref($ad_id);
        }

        if (!empty($context['availability'])) {
            $offer['availability'] = (string) $context['availability'];
        }

        if (!empty($context['ids']['place'])) {
            $offer['availableAtOrFrom'] = bornado_schema_manager_get_ref((string) $context['ids']['place']);
        }

        $seller = bornado_schema_manager_build_single_ad_seller_entity($context);
        if (!empty($seller)) {
            $offer['seller'] = $seller;
        }

        if ($has_numeric) {
            if ($currency !== '') {
                $offer['priceCurrency'] = $currency;
            }

            if ($price_type === 'free') {
                $offer['price'] = 0;
            } elseif ($price_type === 'range') {
                $spec = array(
                    '@type'     => 'UnitPriceSpecification',
                    'priceType' => 'https://schema.org/SalePrice',
                );
                if ($currency !== '') {
                    $spec['priceCurrency'] = $currency;
                }
                if (isset($context['price_from']) && $context['price_from'] !== null) {
                    $spec['minPrice'] = $context['price_from'];
                }
                if (isset($context['price_to']) && $context['price_to'] !== null) {
                    $spec['maxPrice'] = $context['price_to'];
                }
                if (isset($context['price']) && $context['price'] !== null && !isset($spec['minPrice'])) {
                    $spec['price'] = $context['price'];
                }
                $offer['priceSpecification'] = $spec;
            } elseif (isset($context['price']) && $context['price'] !== null) {
                $offer['price'] = $context['price'];
            }
        } else {
            // Negotiable / on-call: keep commercial Offer, never invent a number.
            $offer['priceSpecification'] = array(
                '@type'       => 'UnitPriceSpecification',
                'priceType'   => 'https://schema.org/SalePrice',
                'description' => $price_type === 'on_call'
                    ? bornado_schema_manager_normalize_schema_text(__('Price on call', 'adforest'))
                    : bornado_schema_manager_normalize_schema_text(__('Negotiable', 'adforest')),
            );
            if ($currency !== '') {
                $offer['priceSpecification']['priceCurrency'] = $currency;
            }
        }

        return $offer;
    }
}

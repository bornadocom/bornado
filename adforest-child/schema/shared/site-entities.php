<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_manager_get_site_language_tag')) {
    /**
     * Resolve the current frontend language tag for schema output.
     *
     * @return string
     */
    function bornado_schema_manager_get_site_language_tag()
    {
        if (function_exists('bornado_frontend_language_tag')) {
            $language_tag = (string) bornado_frontend_language_tag();
            if ($language_tag !== '') {
                return $language_tag;
            }
        }

        $locale = function_exists('determine_locale') ? (string) determine_locale() : (string) get_locale();

        return str_replace('_', '-', trim($locale));
    }
}

if (!function_exists('bornado_schema_manager_get_site_alternate_name')) {
    /**
     * Resolve a human-friendly alternate brand name.
     *
     * @return string
     */
    function bornado_schema_manager_get_site_alternate_name()
    {
        $site_name = bornado_schema_manager_get_site_name();
        $default   = strcasecmp($site_name, 'Bornado') === 0 ? 'برنادو' : '';

        return trim((string) apply_filters('bornado_schema_manager_site_alternate_name', $default, $site_name));
    }
}

if (!function_exists('bornado_schema_manager_get_site_description')) {
    /**
     * Resolve the canonical site/brand description.
     *
     * @return string
     */
    function bornado_schema_manager_get_site_description()
    {
        $description = trim(wp_specialchars_decode(wp_strip_all_tags((string) get_bloginfo('description')), ENT_QUOTES));

        return trim((string) apply_filters('bornado_schema_manager_site_description', $description));
    }
}

if (!function_exists('bornado_schema_manager_get_site_social_profiles')) {
    /**
     * Resolve sameAs social/profile URLs for the brand entity.
     *
     * @return array<int,string>
     */
    function bornado_schema_manager_get_site_social_profiles()
    {
        $profiles = (array) apply_filters('bornado_schema_manager_site_social_profiles', array());
        $urls = array();

        foreach ($profiles as $profile) {
            $url = esc_url_raw((string) $profile);
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }
}

if (!function_exists('bornado_schema_manager_get_logo_graph_id')) {
    /**
     * Stable @id for the brand logo node.
     *
     * @return string
     */
    function bornado_schema_manager_get_logo_graph_id()
    {
        return untrailingslashit(home_url('/')) . '/#logo';
    }
}

if (!function_exists('bornado_schema_manager_get_site_logo_data')) {
    /**
     * Resolve the best-available site logo metadata.
     *
     * @return array<string,mixed>
     */
    function bornado_schema_manager_get_site_logo_data()
    {
        static $logo_data = null;

        if (is_array($logo_data)) {
            return $logo_data;
        }

        $url = '';
        $attachment_id = 0;

        $preferred_logo_url = trim((string) apply_filters(
            'bornado_schema_manager_preferred_logo_url',
            'https://bornado.com/wp-content/uploads/2026/06/Bornado.png'
        ));

        if ($preferred_logo_url !== '') {
            $url = esc_url_raw($preferred_logo_url);
            if ($url !== '' && function_exists('attachment_url_to_postid')) {
                $attachment_id = (int) attachment_url_to_postid($url);
            }
        }

        global $adforest_theme;
        if ($url === '' && isset($adforest_theme) && is_array($adforest_theme)) {
            foreach (array('sb_site_logo', 'sb_home_logo', 'sb_site_logo_mobile') as $option_key) {
                $option = isset($adforest_theme[$option_key]) && is_array($adforest_theme[$option_key])
                    ? $adforest_theme[$option_key]
                    : array();
                $candidate_url = !empty($option['url']) ? esc_url_raw((string) $option['url']) : '';
                $candidate_id = !empty($option['id']) ? (int) $option['id'] : 0;

                if ($candidate_url !== '') {
                    $url = $candidate_url;
                    $attachment_id = $candidate_id;
                    break;
                }
            }
        }

        if ($url === '') {
            $site_icon_id = (int) get_option('site_icon');
            if ($site_icon_id > 0) {
                $attachment_id = $site_icon_id;
                $icon_url = wp_get_attachment_image_url($site_icon_id, 'full');
                if (is_string($icon_url) && $icon_url !== '') {
                    $url = esc_url_raw($icon_url);
                }
            }
        }

        $width = 0;
        $height = 0;
        if ($attachment_id > 0) {
            $metadata = wp_get_attachment_metadata($attachment_id);
            if (is_array($metadata)) {
                $width = !empty($metadata['width']) ? (int) $metadata['width'] : 0;
                $height = !empty($metadata['height']) ? (int) $metadata['height'] : 0;
            }
        }

        $logo_data = array(
            'url' => $url,
            'attachment_id' => $attachment_id,
            'width' => $width,
            'height' => $height,
        );

        return $logo_data;
    }
}

if (!function_exists('bornado_schema_manager_build_site_logo_entity')) {
    /**
     * Build the shared ImageObject for the site logo.
     *
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_site_logo_entity()
    {
        $logo_data = bornado_schema_manager_get_site_logo_data();
        if (empty($logo_data['url']) || !is_string($logo_data['url'])) {
            return array();
        }

        $entity = array(
            '@type'      => 'ImageObject',
            '@id'        => bornado_schema_manager_get_logo_graph_id(),
            'url'        => (string) $logo_data['url'],
            'contentUrl' => (string) $logo_data['url'],
            'caption'    => bornado_schema_manager_get_site_name(),
        );

        $language_tag = bornado_schema_manager_get_site_language_tag();
        if ($language_tag !== '') {
            $entity['inLanguage'] = $language_tag;
        }

        if (!empty($logo_data['width'])) {
            $entity['width'] = (int) $logo_data['width'];
        }

        if (!empty($logo_data['height'])) {
            $entity['height'] = (int) $logo_data['height'];
        }

        return $entity;
    }
}

if (!function_exists('bornado_schema_manager_build_site_search_action')) {
    /**
     * Build the shared site search action for the WebSite node.
     *
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_site_search_action()
    {
        return array(
            '@type' => 'SearchAction',
            'target' => array(
                '@type' => 'EntryPoint',
                'urlTemplate' => home_url('/?s={search_term_string}'),
            ),
            'query-input' => array(
                '@type' => 'PropertyValueSpecification',
                'valueRequired' => 'http://schema.org/True',
                'valueName' => 'search_term_string',
            ),
        );
    }
}

if (!function_exists('bornado_schema_manager_build_shared_website_entity')) {
    /**
     * Build the shared WebSite node used across the graph.
     *
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_shared_website_entity()
    {
        $graph_refs = bornado_schema_manager_get_base_graph_refs();
        $description = bornado_schema_manager_get_site_description();
        $alternate_name = bornado_schema_manager_get_site_alternate_name();
        $language_tag = bornado_schema_manager_get_site_language_tag();

        $entity = array(
            '@type' => 'WebSite',
            '@id'   => (string) $graph_refs['website']['@id'],
            'url'   => (string) $graph_refs['website']['url'],
            'name'  => (string) $graph_refs['website']['name'],
            'publisher' => bornado_schema_manager_get_ref((string) $graph_refs['publisher']['@id']),
            'potentialAction' => bornado_schema_manager_build_site_search_action(),
        );

        if ($alternate_name !== '') {
            $entity['alternateName'] = $alternate_name;
        }

        if ($description !== '') {
            $entity['description'] = $description;
        }

        if ($language_tag !== '') {
            $entity['inLanguage'] = $language_tag;
        }

        return $entity;
    }
}

if (!function_exists('bornado_schema_manager_build_shared_organization_entity')) {
    /**
     * Build the shared Organization node used across the graph.
     *
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_shared_organization_entity()
    {
        $graph_refs = bornado_schema_manager_get_base_graph_refs();
        $description = bornado_schema_manager_get_site_description();
        $alternate_name = bornado_schema_manager_get_site_alternate_name();
        $social_profiles = bornado_schema_manager_get_site_social_profiles();
        $logo_entity = bornado_schema_manager_build_site_logo_entity();

        $entity = array(
            '@type' => 'Organization',
            '@id'   => (string) $graph_refs['publisher']['@id'],
            'url'   => (string) $graph_refs['publisher']['url'],
            'name'  => (string) $graph_refs['publisher']['name'],
        );

        if ($alternate_name !== '') {
            $entity['alternateName'] = $alternate_name;
        }

        if ($description !== '') {
            $entity['description'] = $description;
        }

        if (!empty($logo_entity)) {
            $entity['logo'] = bornado_schema_manager_get_ref((string) $logo_entity['@id']);
        }

        if (!empty($social_profiles)) {
            $entity['sameAs'] = $social_profiles;
        }

        return $entity;
    }
}

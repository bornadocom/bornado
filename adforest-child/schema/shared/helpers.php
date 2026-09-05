<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_schema_entity_has_type')) {
    /**
     * Check whether a schema graph node contains one of the expected types.
     *
     * @param mixed $entity
     * @param array<int,string> $expected_types
     * @return bool
     */
    function bornado_schema_entity_has_type($entity, array $expected_types)
    {
        if (!is_array($entity) || empty($entity['@type'])) {
            return false;
        }

        $types = is_array($entity['@type']) ? $entity['@type'] : array($entity['@type']);
        foreach ($types as $type) {
            if (in_array($type, $expected_types, true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bornado_schema_manager_get_site_name')) {
    /**
     * Normalized site name for schema output.
     *
     * @return string
     */
    function bornado_schema_manager_get_site_name()
    {
        return trim(wp_specialchars_decode(wp_strip_all_tags((string) get_bloginfo('name')), ENT_QUOTES));
    }
}

if (!function_exists('bornado_schema_manager_get_current_seo_title')) {
    /**
     * Resolve the final frontend SEO title.
     *
     * @return string
     */
    function bornado_schema_manager_get_current_seo_title()
    {
        $title = '';

        if (class_exists('\RankMath\Paper\Paper') && method_exists('\RankMath\Paper\Paper', 'get')) {
            $paper = \RankMath\Paper\Paper::get();
            if (is_object($paper) && method_exists($paper, 'get_title')) {
                $title = (string) $paper->get_title();
            }
        }

        if ($title === '') {
            $title = (string) wp_get_document_title();
        }

        return trim(preg_replace('/\s+/u', ' ', wp_specialchars_decode(wp_strip_all_tags($title), ENT_QUOTES)));
    }
}

if (!function_exists('bornado_schema_manager_get_current_meta_description')) {
    /**
     * Resolve the final frontend meta description when available.
     *
     * @return string
     */
    function bornado_schema_manager_get_current_meta_description()
    {
        $description = '';

        if (class_exists('\RankMath\Paper\Paper') && method_exists('\RankMath\Paper\Paper', 'get')) {
            $paper = \RankMath\Paper\Paper::get();
            if (is_object($paper) && method_exists($paper, 'get_description')) {
                $description = (string) $paper->get_description();
            }
        }

        if ($description === '') {
            $description = (string) get_bloginfo('description');
        }

        return trim(preg_replace('/\s+/u', ' ', wp_specialchars_decode(wp_strip_all_tags($description), ENT_QUOTES)));
    }
}

if (!function_exists('bornado_schema_manager_get_collection_headline')) {
    /**
     * Resolve the visible collection-page headline.
     *
     * @return string
     */
    function bornado_schema_manager_get_collection_headline()
    {
        if (function_exists('bornado_listing_seo_get_copy')) {
            $copy = bornado_listing_seo_get_copy();
            if (!empty($copy['h1']) && is_string($copy['h1'])) {
                return $copy['h1'];
            }
        }

        if (function_exists('bornado_get_ad_search_seo_heading_title')) {
            $headline = (string) bornado_get_ad_search_seo_heading_title();
            if ($headline !== '') {
                return $headline;
            }
        }

        return bornado_schema_manager_get_current_seo_title();
    }
}

if (!function_exists('bornado_schema_manager_get_current_canonical_url')) {
    /**
     * Resolve the canonical URL for the active page.
     *
     * @return string
     */
    function bornado_schema_manager_get_current_canonical_url()
    {
        if (function_exists('bornado_get_current_canonical_url_for_hreflang')) {
            return (string) bornado_get_current_canonical_url_for_hreflang();
        }

        return '';
    }
}

if (!function_exists('bornado_schema_manager_get_base_graph_refs')) {
    /**
     * Stable graph references shared by page schemas.
     *
     * @return array<string,array<string,string>>
     */
    function bornado_schema_manager_get_base_graph_refs()
    {
        $home_url  = home_url('/');
        $site_name = bornado_schema_manager_get_site_name();

        return array(
            'website' => array(
                '@type' => 'WebSite',
                '@id'   => untrailingslashit($home_url) . '/#website',
                'url'   => $home_url,
                'name'  => $site_name,
            ),
            'publisher' => array(
                '@type' => 'Organization',
                '@id'   => untrailingslashit($home_url) . '/#organization',
                'url'   => $home_url,
                'name'  => $site_name,
            ),
        );
    }
}

if (!function_exists('bornado_schema_manager_get_ref')) {
    /**
     * Return a compact JSON-LD reference object by @id.
     *
     * @param string $id
     * @return array<string,string>
     */
    function bornado_schema_manager_get_ref($id)
    {
        $id = (string) $id;

        return $id === '' ? array() : array('@id' => $id);
    }
}

if (!function_exists('bornado_schema_manager_get_collection_page_id')) {
    /**
     * Stable @id for the primary collection page node.
     *
     * @param string $canonical_url
     * @return string
     */
    function bornado_schema_manager_get_collection_page_id($canonical_url)
    {
        $canonical_url = (string) $canonical_url;

        return $canonical_url === '' ? '' : untrailingslashit($canonical_url) . '/#collectionpage';
    }
}

if (!function_exists('bornado_schema_manager_get_breadcrumb_id')) {
    /**
     * Stable @id for a page breadcrumb node.
     *
     * @param string $canonical_url
     * @return string
     */
    function bornado_schema_manager_get_breadcrumb_id($canonical_url)
    {
        $canonical_url = (string) $canonical_url;

        return $canonical_url === '' ? '' : untrailingslashit($canonical_url) . '/#breadcrumb';
    }
}

if (!function_exists('bornado_schema_manager_get_item_list_id')) {
    /**
     * Stable @id for a page result list node.
     *
     * @param string $canonical_url
     * @return string
     */
    function bornado_schema_manager_get_item_list_id($canonical_url)
    {
        $canonical_url = (string) $canonical_url;

        return $canonical_url === '' ? '' : untrailingslashit($canonical_url) . '/#itemlist';
    }
}

if (!function_exists('bornado_schema_manager_build_collection_page_entity_base')) {
    /**
     * Build the shared CollectionPage payload used by collection page handlers.
     *
     * @param string $page_type
     * @param array<string,mixed> $route_context
     * @param array<string,mixed> $entity
     * @return array<string,mixed>
     */
    function bornado_schema_manager_build_collection_page_entity_base($page_type, array $route_context, array $entity = array())
    {
        $canonical_url    = bornado_schema_manager_get_current_canonical_url();
        $seo_title        = bornado_schema_manager_get_current_seo_title();
        $headline         = bornado_schema_manager_get_collection_headline();
        $meta_description = bornado_schema_manager_get_current_meta_description();
        $graph_refs       = bornado_schema_manager_get_base_graph_refs();
        $about_entities   = function_exists('bornado_schema_manager_build_about_entities')
            ? bornado_schema_manager_build_about_entities($page_type, $route_context)
            : array();
        $content_location = function_exists('bornado_schema_manager_build_content_location')
            ? bornado_schema_manager_build_content_location($route_context)
            : array();

        $entity['@type'] = 'CollectionPage';

        if ($canonical_url !== '') {
            $entity['@id'] = bornado_schema_manager_get_collection_page_id($canonical_url);
            $entity['url'] = $canonical_url;
        }

        if ($seo_title !== '') {
            $entity['name'] = $seo_title;
        }

        if ($headline !== '') {
            $entity['headline'] = $headline;
        }

        if ($meta_description !== '') {
            $entity['description'] = $meta_description;
        }

        if (function_exists('bornado_frontend_language_tag')) {
            $language_tag = (string) bornado_frontend_language_tag();
            if ($language_tag !== '') {
                $entity['inLanguage'] = $language_tag;
            }
        }

        $entity['isPartOf'] = bornado_schema_manager_get_ref($graph_refs['website']['@id']);
        $entity['publisher'] = bornado_schema_manager_get_ref($graph_refs['publisher']['@id']);

        if (function_exists('bornado_schema_manager_page_uses_shared_breadcrumb') && bornado_schema_manager_page_uses_shared_breadcrumb($page_type)) {
            $entity['breadcrumb'] = bornado_schema_manager_get_ref(
                bornado_schema_manager_get_breadcrumb_id($canonical_url)
            );
        }

        if (!empty($about_entities)) {
            $entity['about'] = $about_entities;
        }

        if (!empty($content_location)) {
            $entity['contentLocation'] = $content_location;
        }

        return $entity;
    }
}

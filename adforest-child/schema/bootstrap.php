<?php
if (!defined('ABSPATH')) {
    exit;
}

$bornado_schema_shared_helpers = __DIR__ . '/shared/helpers.php';
if (file_exists($bornado_schema_shared_helpers)) {
    require_once $bornado_schema_shared_helpers;
}

$bornado_schema_shared_context = __DIR__ . '/shared/context.php';
if (file_exists($bornado_schema_shared_context)) {
    require_once $bornado_schema_shared_context;
}

$bornado_schema_shared_site_entities = __DIR__ . '/shared/site-entities.php';
if (file_exists($bornado_schema_shared_site_entities)) {
    require_once $bornado_schema_shared_site_entities;
}

$bornado_schema_shared_category = __DIR__ . '/shared/category.php';
if (file_exists($bornado_schema_shared_category)) {
    require_once $bornado_schema_shared_category;
}

$bornado_schema_shared_breadcrumb = __DIR__ . '/shared/breadcrumb.php';
if (file_exists($bornado_schema_shared_breadcrumb)) {
    require_once $bornado_schema_shared_breadcrumb;
}

$bornado_schema_shared_item_list = __DIR__ . '/shared/item-list.php';
if (file_exists($bornado_schema_shared_item_list)) {
    require_once $bornado_schema_shared_item_list;
}

$bornado_schema_shared_ad_context = __DIR__ . '/shared/ad-context.php';
if (file_exists($bornado_schema_shared_ad_context)) {
    require_once $bornado_schema_shared_ad_context;
}

$bornado_schema_shared_offer = __DIR__ . '/shared/offer.php';
if (file_exists($bornado_schema_shared_offer)) {
    require_once $bornado_schema_shared_offer;
}

$bornado_schema_shared_dynamic_properties = __DIR__ . '/shared/dynamic-properties.php';
if (file_exists($bornado_schema_shared_dynamic_properties)) {
    require_once $bornado_schema_shared_dynamic_properties;
}

$bornado_schema_home_collection = __DIR__ . '/pages/home-collection/collection-page.php';
if (file_exists($bornado_schema_home_collection)) {
    require_once $bornado_schema_home_collection;
}

$bornado_schema_home_collection_item_list = __DIR__ . '/pages/home-collection/item-list.php';
if (file_exists($bornado_schema_home_collection_item_list)) {
    require_once $bornado_schema_home_collection_item_list;
}

$bornado_schema_country_collection = __DIR__ . '/pages/country-collection/collection-page.php';
if (file_exists($bornado_schema_country_collection)) {
    require_once $bornado_schema_country_collection;
}

$bornado_schema_country_collection_item_list = __DIR__ . '/pages/country-collection/item-list.php';
if (file_exists($bornado_schema_country_collection_item_list)) {
    require_once $bornado_schema_country_collection_item_list;
}

$bornado_schema_city_collection = __DIR__ . '/pages/city-collection/collection-page.php';
if (file_exists($bornado_schema_city_collection)) {
    require_once $bornado_schema_city_collection;
}

$bornado_schema_city_collection_item_list = __DIR__ . '/pages/city-collection/item-list.php';
if (file_exists($bornado_schema_city_collection_item_list)) {
    require_once $bornado_schema_city_collection_item_list;
}

$bornado_schema_shape_category_root_collection = __DIR__ . '/shapes/category-root/collection-page.php';
if (file_exists($bornado_schema_shape_category_root_collection)) {
    require_once $bornado_schema_shape_category_root_collection;
}

$bornado_schema_shape_category_root_item_list = __DIR__ . '/shapes/category-root/item-list.php';
if (file_exists($bornado_schema_shape_category_root_item_list)) {
    require_once $bornado_schema_shape_category_root_item_list;
}

$bornado_schema_shape_category_country_collection = __DIR__ . '/shapes/category-country/collection-page.php';
if (file_exists($bornado_schema_shape_category_country_collection)) {
    require_once $bornado_schema_shape_category_country_collection;
}

$bornado_schema_shape_category_country_item_list = __DIR__ . '/shapes/category-country/item-list.php';
if (file_exists($bornado_schema_shape_category_country_item_list)) {
    require_once $bornado_schema_shape_category_country_item_list;
}

$bornado_schema_shape_category_country_city_collection = __DIR__ . '/shapes/category-country-city/collection-page.php';
if (file_exists($bornado_schema_shape_category_country_city_collection)) {
    require_once $bornado_schema_shape_category_country_city_collection;
}

$bornado_schema_shape_category_country_city_item_list = __DIR__ . '/shapes/category-country-city/item-list.php';
if (file_exists($bornado_schema_shape_category_country_city_item_list)) {
    require_once $bornado_schema_shape_category_country_city_item_list;
}

$bornado_schema_single_ad_item_page = __DIR__ . '/pages/single-ad/item-page.php';
if (file_exists($bornado_schema_single_ad_item_page)) {
    require_once $bornado_schema_single_ad_item_page;
}

$bornado_schema_single_ad_breadcrumb = __DIR__ . '/pages/single-ad/breadcrumb.php';
if (file_exists($bornado_schema_single_ad_breadcrumb)) {
    require_once $bornado_schema_single_ad_breadcrumb;
}

$bornado_schema_single_ad_main_entity = __DIR__ . '/pages/single-ad/main-entity.php';
if (file_exists($bornado_schema_single_ad_main_entity)) {
    require_once $bornado_schema_single_ad_main_entity;
}

$bornado_schema_geo_guide_web_page = __DIR__ . '/pages/geo-guide/web-page.php';
if (file_exists($bornado_schema_geo_guide_web_page)) {
    require_once $bornado_schema_geo_guide_web_page;
}

$bornado_schema_geo_guide_extend = __DIR__ . '/pages/geo-guide/extend.php';
if (file_exists($bornado_schema_geo_guide_extend)) {
    require_once $bornado_schema_geo_guide_extend;
}

$bornado_schema_vertical_property = __DIR__ . '/verticals/property/enrich.php';
if (file_exists($bornado_schema_vertical_property)) {
    require_once $bornado_schema_vertical_property;
}

$bornado_schema_vertical_property_single = __DIR__ . '/verticals/property/single.php';
if (file_exists($bornado_schema_vertical_property_single)) {
    require_once $bornado_schema_vertical_property_single;
}

$bornado_schema_vertical_jobs = __DIR__ . '/verticals/jobs/enrich.php';
if (file_exists($bornado_schema_vertical_jobs)) {
    require_once $bornado_schema_vertical_jobs;
}

$bornado_schema_vertical_jobs_single = __DIR__ . '/verticals/jobs/single.php';
if (file_exists($bornado_schema_vertical_jobs_single)) {
    require_once $bornado_schema_vertical_jobs_single;
}

$bornado_schema_vertical_vehicles = __DIR__ . '/verticals/vehicles/enrich.php';
if (file_exists($bornado_schema_vertical_vehicles)) {
    require_once $bornado_schema_vertical_vehicles;
}

$bornado_schema_vertical_vehicles_single = __DIR__ . '/verticals/vehicles/single.php';
if (file_exists($bornado_schema_vertical_vehicles_single)) {
    require_once $bornado_schema_vertical_vehicles_single;
}

$bornado_schema_vertical_items = __DIR__ . '/verticals/items/enrich.php';
if (file_exists($bornado_schema_vertical_items)) {
    require_once $bornado_schema_vertical_items;
}

$bornado_schema_vertical_items_single = __DIR__ . '/verticals/items/single.php';
if (file_exists($bornado_schema_vertical_items_single)) {
    require_once $bornado_schema_vertical_items_single;
}

$bornado_schema_vertical_community = __DIR__ . '/verticals/community/enrich.php';
if (file_exists($bornado_schema_vertical_community)) {
    require_once $bornado_schema_vertical_community;
}

$bornado_schema_vertical_community_single = __DIR__ . '/verticals/community/single.php';
if (file_exists($bornado_schema_vertical_community_single)) {
    require_once $bornado_schema_vertical_community_single;
}

$bornado_schema_vertical_services = __DIR__ . '/verticals/services/enrich.php';
if (file_exists($bornado_schema_vertical_services)) {
    require_once $bornado_schema_vertical_services;
}

$bornado_schema_vertical_services_single = __DIR__ . '/verticals/services/single.php';
if (file_exists($bornado_schema_vertical_services_single)) {
    require_once $bornado_schema_vertical_services_single;
}

$bornado_schema_shared_graph = __DIR__ . '/shared/graph.php';
if (file_exists($bornado_schema_shared_graph)) {
    require_once $bornado_schema_shared_graph;
}

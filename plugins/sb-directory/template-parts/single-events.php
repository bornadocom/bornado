<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
defined('ABSPATH') || exit;

if (post_password_required()) {
    echo get_the_password_form(); // WPCS: XSS ok.
    return;
}
get_header();
adforest_custom_breadcrumbs();

$event_id = get_the_id();

if (!class_exists('Post_Views_Counter')) {
    adforest_setPostViews($event_id);
} else {
    $job_views = pvc_get_post_views(get_the_ID());
}

sb_load_template_parts('content', 'single-event');

get_footer();

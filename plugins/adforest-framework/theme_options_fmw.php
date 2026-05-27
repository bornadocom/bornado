<?php 
if (get_option('adforest_theme') == "") {
    $sb_option_name = 'adforest_theme';

    if (class_exists('Redux')) {
        /*Header Options*/
        // Redux::setOption($sb_option_name, 'sb_site_logo', array('url' => SB_THEMEURL_PLUGIN . 'images/logo.png'));
        Redux::setOption($sb_option_name, 'sb_site_logo_light', array('url' => SB_THEMEURL_PLUGIN . 'images/logo.png'));
        Redux::setOption($sb_option_name, 'sb_enable_top_bar', '0');
        Redux::setOption($sb_option_name, 'admin_bar', '1');
        Redux::setOption($sb_option_name, 'theme_color', 'defualt');
        Redux::setOption($sb_option_name, 'sb_header', 'white');
        Redux::setOption($sb_option_name, 'sb_sticky_header', '0');
        Redux::setOption($sb_option_name, 'scroll_to_top', '1');
        Redux::setOption($sb_option_name, 'sell_button', '1');
        Redux::setOption($sb_option_name, 'sb_top_bar', '1');
        Redux::setOption($sb_option_name, 'top_bar_pages', '');
        Redux::setOption($sb_option_name, 'sb_sign_in_page', '');
        Redux::setOption($sb_option_name, 'sb_sign_up_page', '');
        Redux::setOption($sb_option_name, 'sb_profile_page', '');
        Redux::setOption($sb_option_name, 'sb_post_ad_page', '');
        Redux::setOption($sb_option_name, 'sb_color_plate', '0');
        Redux::setOption($sb_option_name, 'sb_pre_loader', '0');
        Redux::setOption($sb_option_name, 'ad_in_menu', '0');
        Redux::setOption($sb_option_name, 'sb_rtl', '0');
        Redux::setOption($sb_option_name, 'sb_admin_translate', '0');
        Redux::setOption($sb_option_name, 'search_in_header', '1');


        // Social Media
        Redux::setOption($sb_option_name, 'social_media', array(
            'Facebook' => '',
            'Twitter' => '',
            'Linked In' => '',
            'Google +' => '',
            'YouTube' => '',
            'Vimeo' => '',
            'Pinterest' => '',
            'Tumblr' => '',
            'Instagram' => '',
            'Reddit' => '',
            'Flickr' => '',
            'StumbleUpon' => '',
            'Delicious' => '',
            'dribble' => '',
            'behance' => '',
            'DeviantART' => '',
        ));

        // Social Media Coming Soon
        Redux::setOption($sb_option_name, 'social_media_soon', array(
            'Facebook' => '',
            'Twitter' => '',
            'Linked In' => '',
            'Google +' => '',
            'YouTube' => '',
            'Vimeo' => '',
            'Pinterest' => '',
            'Tumblr' => '',
            'Instagram' => '',
            'Reddit' => '',
            'Flickr' => '',
            'StumbleUpon' => '',
            'Delicious' => '',
            'dribble' => '',
            'behance' => '',
            'DeviantART' => '',
        ));

        // Footer Options
        Redux::setOption($sb_option_name, 'footer_style', '1');
        Redux::setOption($sb_option_name, 'footer_options', '');
        Redux::setOption($sb_option_name, 'footer_bg', array('url' => SB_THEMEURL_PLUGIN . 'images/footer.jpg'));
        Redux::setOption($sb_option_name, 'footer_site_logo', array('url' => SB_THEMEURL_PLUGIN . 'images/logo-1.png'));
        Redux::setOption($sb_option_name, 'footer_logo', array('url' => trailingslashit(get_template_directory_uri()) . 'images/logo.png'));
        Redux::setOption($sb_option_name, 'footer_text_under_logo', 'Aoluptas sit aspernatur aut odit aut fugit, sed elits quias horisa hinoe magni magni dolores eos qui ratione volust luptatem sequised.');
        Redux::setOption($sb_option_name, 'section_2_title', 'Hot Links');
        Redux::setOption($sb_option_name, 'footer_post_numbers', '2');
        Redux::setOption($sb_option_name, 'section_3_title', 'Recent Posts');
        Redux::setOption($sb_option_name, 'sb_footer_pages', array('2'));
        Redux::setOption($sb_option_name, 'section_4_title', 'Quick Links');
        Redux::setOption($sb_option_name, 'sb_footer_links', array('2'));
        Redux::setOption($sb_option_name, 'footer-contact-details', array(
            'Address' => '75 Blue Street, PK 54000',
            'Phone' => '(+92) 12 345 6879',
            'Fax' => '(+92) 98 765 4321',
            'Email' => 'contact@scriptsbundle.com',
            'Timing' => 'Mon-Fri 12:00pm - 12:00am'
        ));

        Redux::setOption($sb_option_name, 'footer_android_app', '');
        Redux::setOption($sb_option_name, 'footer_ios_app', '');
        Redux::setOption($sb_option_name, 'section_3_text', 'We may send you information about related events, webinars, products and services which we believe.');
        Redux::setOption($sb_option_name, 'section_3_mc', '0');
        Redux::setOption($sb_option_name, 'mailchimp_footer_list_id', '');
        Redux::setOption($sb_option_name, 'sb_footer', 'Copyright 2016 &copy; Theme Created By <a href="https://themeforest.net/user/scriptsbundle/portfolio">ScriptsBundle</a> All Rights Reserved.');
        Redux::setOption($sb_option_name, 'footer_js_and_css', '');
        Redux::setOption($sb_option_name, 'footer_4_bg', 'gray');


        // BreadCrumb
        Redux::setOption($sb_option_name, 'breadcrumb_bg', array('url' => SB_THEMEURL_PLUGIN . 'images/breadcrumb.jpg'));


        // Blog 
        Redux::setOption($sb_option_name, 'sb_blog_page_title', 'Blog Posts');
        Redux::setOption($sb_option_name, 'sb_blog_single_title', 'Blog Details');
        Redux::setOption($sb_option_name, 'blog_sidebar', 'right');
        Redux::setOption($sb_option_name, 'enable_share_post', '1');

        // Ad Post 
        Redux::setOption($sb_option_name, 'communication_mode', 'both');
        Redux::setOption($sb_option_name, 'sb_send_email_on_message', '1');
        Redux::setOption($sb_option_name, 'sb_send_email_on_ad_post', '1');
        Redux::setOption($sb_option_name, 'ad_post_email_value', get_option('admin_email'));
        Redux::setOption($sb_option_name, 'sb_currency', 'USD');

        Redux::setOption($sb_option_name, 'sb_allow_ads', '1');
        Redux::setOption($sb_option_name, 'sb_free_ads_limit', '-1');


        Redux::setOption($sb_option_name, 'admin_allow_unlimited_ads', '1');
        Redux::setOption($sb_option_name, 'sb_allow_featured_ads', '1');
        Redux::setOption($sb_option_name, 'sb_featured_ads_limit', '1');
        Redux::setOption($sb_option_name, 'sb_package_validity', '-1');


        Redux::setOption($sb_option_name, 'sb_upload_limit', '5');
        Redux::setOption($sb_option_name, 'sb_upload_size', '819200-800kb');
        Redux::setOption($sb_option_name, 'sb_ad_approval', 'auto');
        Redux::setOption($sb_option_name, 'sb_update_approval', 'auto');
        Redux::setOption($sb_option_name, 'sb_ad_update_notice', 'Hey, be careful you are updating this AD.');
        Redux::setOption($sb_option_name, 'bad_words_filter', '');
        Redux::setOption($sb_option_name, 'bad_words_replace', '');

        Redux::setOption($sb_option_name, 'ad_layout_style', '1');
        Redux::setOption($sb_option_name, 'ad_slider_type', '1');
        Redux::setOption($sb_option_name, 'style_ad_720_1', '');
        Redux::setOption($sb_option_name, 'style_ad_720_2', '');
        Redux::setOption($sb_option_name, 'style_ad_160_1', '');
        Redux::setOption($sb_option_name, 'style_ad_160_2', '');
        Redux::setOption($sb_option_name, 'report_options', 'Spam|Offensive|Duplicated|Fake');

        Redux::setOption($sb_option_name, 'featured_expiry', '7');
        Redux::setOption($sb_option_name, 'sb_packages_page', '');
        Redux::setOption($sb_option_name, 'report_limit', '10');
        Redux::setOption($sb_option_name, 'report_action', '1');
        Redux::setOption($sb_option_name, 'report_email', get_option('admin_email'));

        Redux::setOption($sb_option_name, 'Related_ads_on', '1');
        Redux::setOption($sb_option_name, 'share_ads_on', '1');
        Redux::setOption($sb_option_name, 'sb_related_ads_title', 'Similiar Ads');
        Redux::setOption($sb_option_name, 'related_ad_style', '1');
        Redux::setOption($sb_option_name, 'max_ads', '5');
        Redux::setOption($sb_option_name, 'default_related_image', array('url' => SB_THEMEURL_PLUGIN . 'images/no-image.jpg'));
        Redux::setOption($sb_option_name, 'tips_title', 'Safety tips for deal');
        Redux::setOption($sb_option_name, 'tips_for_ad', '<ol>
							 <li>Use a safe location to meet seller</li>
							 <li>Avoid cash transactions</li>
							 <li>Beware of unrealistic offers</li>
						  </ol>
	');

        Redux::setOption($sb_option_name, 'sb_search_page', '');
        Redux::setOption($sb_option_name, 'search_layout', 'grid_1');
        Redux::setOption($sb_option_name, 'search_bg', 'gray');
        Redux::setOption($sb_option_name, 'search_res_bg', 'white-bg');
        Redux::setOption($sb_option_name, 'feature_on_search', '1');
        Redux::setOption($sb_option_name, 'max_ads_feature', '5');
        Redux::setOption($sb_option_name, 'feature_ads_title', 'Featured Ads');
        Redux::setOption($sb_option_name, 'search_ad_720_1', '');
        Redux::setOption($sb_option_name, 'search_ad_720_2', '');


        // Contact Info
        Redux::setOption($sb_option_name, 'sb_timing', 'Mon - Sat: 09.00 - 19.00');
        Redux::setOption($sb_option_name, 'sb_phone', '+(789) 675 978');
        Redux::setOption($sb_option_name, 'sb_email', 'support@glixentech.com');
        Redux::setOption($sb_option_name, 'sb_address', 'Link Road, Lahore, Pakistan');
        Redux::setOption($sb_option_name, 'sb_fax', '(880) 777 4444');
        Redux::setOption($sb_option_name, 'sb_site_logo', array('url' => SB_THEMEURL_PLUGIN . 'images/logo.png'));

        // Comming Soon
        Redux::setOption($sb_option_name, 'sb_comming_soon_logo', array('url' => SB_THEMEURL_PLUGIN . 'images/logo.png'));
        Redux::setOption($sb_option_name, 'sb_comming_soon_mode', 0);
        Redux::setOption($sb_option_name, 'sb_comming_soon_date', '2017/06/28');
        Redux::setOption($sb_option_name, 'coming_soon_notify', '0');
        Redux::setOption($sb_option_name, 'mailchimp_notify_list_id', '');
        Redux::setOption($sb_option_name, 'sb_comming_soon_title', "Our website is under construction.");

        // W00 Commerce
        Redux::setOption($sb_option_name, 'shop_view', 'grid');
        Redux::setOption($sb_option_name, 'sb_shop_page_title', 'Shop');
        Redux::setOption($sb_option_name, 'sb_shop_single_title', 'Product Details');
        Redux::setOption($sb_option_name, 'enable_share', '1');
        Redux::setOption($sb_option_name, 'sb_woo_related_products', '1');
        Redux::setOption($sb_option_name, 'single_shop_view', 'without_sidebar');
        Redux::setOption($sb_option_name, 'sb_bread_crumb_enable_shop', '1');
        Redux::setOption($sb_option_name, 'sb_bread_crumb_shop', array('url' => SB_THEMEURL_PLUGIN . 'images/bredcrumb.jpg'));
        Redux::setOption($sb_option_name, 'sb_woo_related_products_title', 'Related Products');
        Redux::setOption($sb_option_name, 'sb_woo_related_products_description', 'You may like also.');

        // API Settings
        Redux::setOption($sb_option_name, 'google_api_key', '');
        Redux::setOption($sb_option_name, 'google_api_secret', '');
        Redux::setOption($sb_option_name, 'gmap_api_key', 'AIzaSyB_La6qmewwbVnTZu5mn3tVrtu6oMaSXaI');
        Redux::setOption($sb_option_name, 'mailchimp_api_key', '');
        Redux::setOption($sb_option_name, 'fb_api_key', '');
        Redux::setOption($sb_option_name, 'gmail_api_key', '');
        Redux::setOption($sb_option_name, 'hotmail_api_key', '');
        Redux::setOption($sb_option_name, 'linked_api_key', '');
        Redux::setOption($sb_option_name, 'redirect_uri', '');

        // Modern Design Settings
        Redux::setOption($sb_option_name, 'design_type', 'modern');
        Redux::setOption($sb_option_name, 'ad_layout_style_modern', '3');
        Redux::setOption($sb_option_name, 'search_design', 'sidebar');
        Redux::setOption($sb_option_name, 'search_ad_layout', 'grid_1');
    }
}
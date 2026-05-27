<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

echo "i am event info";

echo "<br>";


$event_id   =  get_the_id();

echo get_post_meta($event_id, 'sb_pro_event_status', '1');
echo get_post_meta($event_id, 'sb_pro_event_contact', true);
echo get_post_meta($event_id, 'sb_pro_event_email', true);
echo get_post_meta($event_id, 'sb_pro_event_start_date', true);
echo get_post_meta($event_id, 'sb_pro_event_end_date', true);
echo get_post_meta($event_id, 'sb_pro_event_venue', true);
echo get_post_meta($event_id, 'sb_pro_event_lat', true);
echo get_post_meta($event_id, 'sb_pro_event_long', true);
echo get_post_meta($event_id, 'sb_pro_event_listing_id', true);

 $event_cat  = wp_get_post_terms($event_id ,'l_event_cat');
 
 
 print_r($event_cat[0]->name);


echo "<br>";

<?php
global $adforest_theme ;
$event_id   =  get_the_ID();
$is_allow_map =  isset($adforest_theme['sb_pro_event_map'])  ?    $adforest_theme['sb_pro_event_map']  : "";
$map_type  =   $adforest_theme['map-setings-map-type']  ? $adforest_theme['map-setings-map-type']  :  'leafletjs_map';      //  leafletjs_map or google_map
$sb_pro_event_lat  =   get_post_meta($event_id , 'sb_pro_event_lat' , true);
$sb_pro_event_long  =   get_post_meta($event_id , 'sb_pro_event_long' , true);
$venue  =   get_post_meta($event_id , 'sb_pro_event_venue' , true);
if($is_allow_map  && $sb_pro_event_lat != ""  && $sb_pro_event_long != ""){  ?>
  <div id="event-location-container" class="location-box">
    <h4 class="sub-title"><?php echo esc_html__("Location:", "sb_pro"); ?></h4>
    <div class="map-box">
         <input type="hidden" id="event_latt" value="<?php echo esc_attr($sb_pro_event_lat);?>" />
         <input type="hidden" id="event_long" value="<?php echo esc_attr
         ($sb_pro_event_long) ?>" />
        <div id="event_detail_map"></div>
    </div>
    <span><i class="fas fa-location-arrow"></i> <?php echo esc_html($venue ) ?></span>
</div>
    <?php }

